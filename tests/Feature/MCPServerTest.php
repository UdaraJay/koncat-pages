<?php

namespace Tests\Feature;

use App\Mcp\Servers\MCPServer;
use App\Mcp\Tools\DeployProjectTool;
use App\Mcp\Tools\FetchProjectTool;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;
use ZipArchive;

class MCPServerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_deploy_project_tool_creates_personal_project_and_returns_hosted_url(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.hosting_scheme' => 'http',
            'matterpipe.render_domain' => 'render.localhost',
            'matterpipe.render_scheme' => 'http',
        ]);

        $user = User::factory()->create();
        $user->personalTeam()->update(['subdomain' => 'agent-team']);

        $response = MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'name' => 'Agent App',
            'slug' => 'agent-app',
            'description' => 'Created by an agent.',
            'files' => [
                ['path' => 'index.html', 'content' => '<h1>Hello agent</h1>'],
                ['path' => 'assets/app.bin', 'base64' => base64_encode("binary\0content")],
            ],
        ]);

        $response
            ->assertOk()
            ->assertName('publish')
            ->assertStructuredContent(fn ($json) => $json
                ->where('action', 'created')
                ->where('project.slug', 'agent-app')
                ->where('project.url', 'http://agent-team.localhost/agent-app')
                ->where('deployment.fileCount', 2)
                ->where('deployment.totalBytes', strlen('<h1>Hello agent</h1>') + strlen("binary\0content"))
                ->where('deployment.securityScan.status', 'passed')
                ->where('deployment.securityScan.riskScore', 0)
                ->has('project.id')
                ->has('deployment.id')
                ->has('deployment.deployedAt')
            );

        $project = Project::firstWhere('slug', 'agent-app');

        $this->assertNotNull($project);
        $this->assertSame(User::class, $project->owner_type);
        $this->assertSame($user->id, $project->owner_id);
        $this->assertNull($project->workspace_id);
        $this->assertNotNull($project->current_deployment_id);
    }

    public function test_mcp_route_rejects_unauthenticated_requests_with_oauth_challenge(): void
    {
        $resourceMetadataUrl = route('mcp.oauth.protected-resource.nested', ['path' => 'mcp']);

        $this->postJson('/mcp', $this->toolCallPayload())
            ->assertUnauthorized()
            ->assertHeader(
                'WWW-Authenticate',
                'Bearer realm="mcp", resource_metadata="'.$resourceMetadataUrl.'"'
            );
    }

    public function test_mcp_oauth_metadata_and_dynamic_client_registration_are_available(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource/mcp')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp'))
            ->assertJsonPath('authorization_servers.0', url('/'))
            ->assertJsonPath('scopes_supported.0', 'mcp:use');

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('issuer', url('/'))
            ->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'))
            ->assertJsonPath('token_endpoint', route('passport.token'))
            ->assertJsonPath('registration_endpoint', url('/oauth/register'))
            ->assertJsonPath('response_types_supported.0', 'code')
            ->assertJsonPath('code_challenge_methods_supported.0', 'S256')
            ->assertJsonPath('grant_types_supported.0', 'authorization_code')
            ->assertJsonPath('scopes_supported.0', 'mcp:use');

        $this->postJson('/oauth/register', [
            'client_name' => 'Claude Desktop',
            'redirect_uris' => ['http://localhost:6274/oauth/callback'],
        ])
            ->assertCreated()
            ->assertJsonPath('grant_types.0', 'authorization_code')
            ->assertJsonPath('grant_types.1', 'refresh_token')
            ->assertJsonPath('response_types.0', 'code')
            ->assertJsonPath('redirect_uris.0', 'http://localhost:6274/oauth/callback')
            ->assertJsonPath('scope', 'mcp:use')
            ->assertJsonPath('token_endpoint_auth_method', 'none')
            ->assertJsonStructure(['client_id']);

        $this->assertDatabaseHas('oauth_clients', [
            'name' => 'Claude Desktop',
            'secret' => null,
            'revoked' => false,
        ]);
    }

    public function test_passport_authorization_page_approves_mcp_oauth_clients(): void
    {
        $user = User::factory()->create();
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            name: 'Claude Desktop',
            redirectUris: ['http://localhost:6274/oauth/callback'],
            confidential: false,
        );

        $this->actingAs($user);

        $this
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => 'http://localhost:6274/oauth/callback',
                'response_type' => 'code',
                'scope' => 'mcp:use',
                'state' => 'test-state',
                'code_challenge' => str_repeat('a', 43),
                'code_challenge_method' => 'S256',
            ]))
            ->assertOk()
            ->assertSee('Authorize Claude Desktop')
            ->assertSee('Use MCP server');

        $authToken = session('authToken');

        $this->assertIsString($authToken);

        $response = $this->post('/oauth/authorize', [
            'client_id' => $client->id,
            'auth_token' => $authToken,
        ]);

        $response->assertRedirect();

        $redirectUrl = $response->headers->get('Location');

        $this->assertIsString($redirectUrl);
        $this->assertStringStartsWith('http://localhost:6274/oauth/callback?', $redirectUrl);
        $this->assertStringContainsString('code=', $redirectUrl);
        $this->assertStringContainsString('state=test-state', $redirectUrl);
        $this->assertDatabaseHas('oauth_auth_codes', [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'revoked' => false,
        ]);
    }

    public function test_mcp_route_rejects_passport_tokens_without_mcp_scope(): void
    {
        $user = User::factory()->create();

        Passport::actingAs($user);

        $this->postJson('/mcp', $this->toolCallPayload())
            ->assertForbidden();
    }

    public function test_mcp_route_accepts_passport_token_with_mcp_scope_and_deploys_project(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.hosting_scheme' => 'http',
            'matterpipe.render_domain' => 'render.localhost',
            'matterpipe.render_scheme' => 'http',
        ]);

        $user = User::factory()->create();
        $user->personalTeam()->update(['subdomain' => 'route-team']);

        Passport::actingAs($user, ['mcp:use']);

        $this->postJson('/mcp', $this->toolCallPayload([
            'name' => 'Route App',
            'slug' => 'route-app',
            'files' => [
                ['path' => 'index.html', 'content' => 'hello'],
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('result.structuredContent.action', 'created')
            ->assertJsonPath('result.structuredContent.project.slug', 'route-app')
            ->assertJsonPath('result.structuredContent.project.url', 'http://route-team.localhost/route-app');

        $this->assertDatabaseHas('projects', [
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'slug' => 'route-app',
        ]);
    }

    public function test_mcp_route_advertises_publish_and_fetch_tools(): void
    {
        $user = User::factory()->create();

        Passport::actingAs($user, ['mcp:use']);

        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertOk();

        $toolNames = collect($response->json('result.tools'))
            ->pluck('name')
            ->all();

        $this->assertSame(['publish', 'fetch'], $toolNames);
        $this->assertNotContains('deploy-project', $toolNames);
        $this->assertNotContains('fetch-project', $toolNames);
    }

    public function test_fetch_project_tool_returns_current_deployment_files_from_flexible_url(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.hosting_scheme' => 'http',
            'matterpipe.render_domain' => 'render.localhost',
            'matterpipe.render_scheme' => 'http',
        ]);

        $user = User::factory()->create();
        $user->personalTeam()->update(['subdomain' => 'agent-team']);

        MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'name' => 'Agent App',
            'slug' => 'agent-app',
            'description' => 'Created by an agent.',
            'files' => [
                ['path' => 'index.html', 'content' => '<h1>Hello agent</h1>'],
                ['path' => 'assets/app.bin', 'base64' => base64_encode("binary\0content")],
            ],
        ])->assertOk();

        MCPServer::actingAs($user)->tool(FetchProjectTool::class, [
            'url' => 'agent-team.render.localhost:8000/agent-app/assets/app.bin?cache=1#preview',
        ])
            ->assertOk()
            ->assertName('fetch')
            ->assertStructuredContent(fn ($json) => $json
                ->where('project.name', 'Agent App')
                ->where('project.slug', 'agent-app')
                ->where('project.description', 'Created by an agent.')
                ->where('project.url', 'http://agent-team.localhost/agent-app')
                ->where('deployment.fileCount', 2)
                ->where('deployment.totalBytes', strlen('<h1>Hello agent</h1>') + strlen("binary\0content"))
                ->has('project.id')
                ->has('deployment.id')
                ->has('deployment.deployedAt')
                ->has('files', 2)
                ->where('files.0.path', 'index.html')
                ->where('files.0.size', strlen('<h1>Hello agent</h1>'))
                ->where('files.0.encoding', 'utf-8')
                ->where('files.0.content', '<h1>Hello agent</h1>')
                ->where('files.1.path', 'assets/app.bin')
                ->where('files.1.size', strlen("binary\0content"))
                ->where('files.1.encoding', 'base64')
                ->where('files.1.base64', base64_encode("binary\0content"))
            );
    }

    public function test_deploy_project_tool_updates_existing_project_when_url_is_provided(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.hosting_scheme' => 'http',
            'matterpipe.render_domain' => 'render.localhost',
            'matterpipe.render_scheme' => 'http',
        ]);

        $user = User::factory()->create();
        $user->personalTeam()->update(['subdomain' => 'agent-team']);

        MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'name' => 'Agent App',
            'slug' => 'agent-app',
            'files' => [
                ['path' => 'index.html', 'content' => 'before'],
            ],
        ])->assertOk();

        $project = Project::firstWhere('slug', 'agent-app');
        $originalDeploymentId = $project->current_deployment_id;

        MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'url' => 'agent-team.render.localhost:8000/agent-app/index.html',
            'name' => 'Updated Agent App',
            'description' => 'Updated by an agent.',
            'files' => [
                ['path' => 'index.html', 'content' => 'after'],
                ['path' => 'assets/app.js', 'content' => 'console.log("after")'],
            ],
        ])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json
                ->where('action', 'updated')
                ->where('project.id', $project->id)
                ->where('project.slug', 'agent-app')
                ->where('project.url', 'http://agent-team.localhost/agent-app')
                ->where('deployment.fileCount', 2)
                ->where('deployment.totalBytes', strlen('after') + strlen('console.log("after")'))
                ->has('deployment.id')
                ->has('deployment.deployedAt')
            );

        $project->refresh();

        $this->assertSame('Updated Agent App', $project->name);
        $this->assertSame('Updated by an agent.', $project->description);
        $this->assertNotSame($originalDeploymentId, $project->current_deployment_id);
        $this->assertSame(1, Project::count());

        MCPServer::actingAs($user)->tool(FetchProjectTool::class, [
            'url' => 'agent-team.localhost/agent-app',
        ])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json
                ->where('files.0.path', 'index.html')
                ->where('files.0.content', 'after')
                ->where('files.1.path', 'assets/app.js')
                ->where('files.1.content', 'console.log("after")')
                ->etc()
            );
    }

    public function test_deploy_project_tool_blocks_high_risk_security_findings_when_updating(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.hosting_scheme' => 'http',
        ]);

        $user = User::factory()->create();
        $user->personalTeam()->update(['subdomain' => 'secure-team']);

        MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'name' => 'Secure App',
            'slug' => 'secure-app',
            'files' => [
                ['path' => 'index.html', 'content' => 'safe'],
            ],
        ])->assertOk();

        $project = Project::firstWhere('slug', 'secure-app');
        $originalDeploymentId = $project->current_deployment_id;

        MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'url' => 'secure-team.localhost/secure-app',
            'name' => 'Should Not Persist',
            'files' => [
                ['path' => 'index.html', 'content' => '<script>eval("bad")</script>'],
            ],
        ])->assertHasErrors(['Deployment blocked by security scan: no-eval at index.html#inline-script:1:1.']);

        $project->refresh();

        $this->assertSame('Secure App', $project->name);
        $this->assertSame($originalDeploymentId, $project->current_deployment_id);
        $this->assertDatabaseHas('deployment_security_scans', [
            'project_id' => $project->id,
            'deployment_id' => null,
            'user_id' => $user->id,
            'status' => 'blocked',
            'highest_severity' => 'high',
        ]);
    }

    public function test_deploy_project_tool_requires_deploy_access_when_updating(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.hosting_scheme' => 'http',
        ]);

        $owner = User::factory()->create();
        $owner->personalTeam()->update(['subdomain' => 'owner-team']);
        $otherUser = User::factory()->create();

        MCPServer::actingAs($owner)->tool(DeployProjectTool::class, [
            'name' => 'Private App',
            'slug' => 'private-app',
            'files' => [
                ['path' => 'index.html', 'content' => 'secret'],
            ],
        ])->assertOk();

        MCPServer::actingAs($otherUser)->tool(DeployProjectTool::class, [
            'url' => 'owner-team.localhost/private-app',
            'files' => [
                ['path' => 'index.html', 'content' => 'changed'],
            ],
        ])->assertHasErrors(['You do not have permission to deploy this project.']);
    }

    public function test_fetch_project_tool_rejects_invalid_urls(): void
    {
        config([
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.render_domain' => 'localhost',
        ]);

        $user = User::factory()->create();

        MCPServer::actingAs($user)->tool(FetchProjectTool::class, [
            'url' => 'agent-team.localhost',
        ])->assertHasErrors(['The hosted project URL must include both a team subdomain and project path.']);

        MCPServer::actingAs($user)->tool(FetchProjectTool::class, [
            'url' => 'https://agent-team.example.com/agent-app',
        ])->assertHasErrors(['The hosted project URL must use the localhost domain.']);
    }

    public function test_fetch_project_tool_requires_project_access(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.hosting_scheme' => 'http',
        ]);

        $owner = User::factory()->create();
        $owner->personalTeam()->update(['subdomain' => 'owner-team']);
        $otherUser = User::factory()->create();

        MCPServer::actingAs($owner)->tool(DeployProjectTool::class, [
            'name' => 'Private App',
            'slug' => 'private-app',
            'files' => [
                ['path' => 'index.html', 'content' => 'secret'],
            ],
        ])->assertOk();

        MCPServer::actingAs($otherUser)->tool(FetchProjectTool::class, [
            'url' => 'owner-team.localhost/private-app',
        ])->assertHasErrors(['You do not have access to this project.']);
    }

    public function test_deploy_project_tool_rejects_duplicate_slug(): void
    {
        $this->skipWithoutZip();

        $user = User::factory()->create();
        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'slug' => 'taken',
        ]);

        MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'name' => 'Taken',
            'slug' => 'taken',
            'files' => [
                ['path' => 'index.html', 'content' => 'hello'],
            ],
        ])->assertHasErrors(['The slug has already been taken.']);
    }

    public function test_deploy_project_tool_rejects_missing_index_file(): void
    {
        $this->skipWithoutZip();

        $user = User::factory()->create();

        MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'name' => 'No Index',
            'files' => [
                ['path' => 'about.html', 'content' => 'hello'],
            ],
        ])->assertHasErrors(['The deployment archive must include an index.html file.']);

        $this->assertDatabaseMissing('projects', [
            'name' => 'No Index',
        ]);
    }

    public function test_deploy_project_tool_rejects_unsafe_and_reserved_paths(): void
    {
        $this->skipWithoutZip();

        $user = User::factory()->create();

        foreach (['../index.html', '/index.html', '.env', '__matterpipe/config.json'] as $path) {
            MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
                'name' => 'Unsafe',
                'files' => [
                    ['path' => $path, 'content' => 'hello'],
                ],
            ])->assertHasErrors();
        }
    }

    public function test_deploy_project_tool_enforces_deployment_quotas(): void
    {
        $this->skipWithoutZip();
        config([
            'matterpipe.quotas.deployment_files' => 1,
            'matterpipe.quotas.deployment_bytes' => 1024,
            'matterpipe.quotas.deployment_file_bytes' => 1024,
        ]);

        $user = User::factory()->create();

        MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'name' => 'Too Many Files',
            'files' => [
                ['path' => 'index.html', 'content' => 'hello'],
                ['path' => 'app.js', 'content' => 'alert(1)'],
            ],
        ])->assertHasErrors(['This deployment has too many files.']);

        config(['matterpipe.quotas.deployment_files' => 10]);
        config(['matterpipe.quotas.deployment_bytes' => 10]);

        MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'name' => 'Too Large',
            'files' => [
                ['path' => 'index.html', 'content' => str_repeat('x', 11)],
            ],
        ])->assertHasErrors(['This deployment is too large.']);

        config(['matterpipe.quotas.deployment_bytes' => 1024]);
        config(['matterpipe.quotas.deployment_file_bytes' => 10]);

        MCPServer::actingAs($user)->tool(DeployProjectTool::class, [
            'name' => 'One File Too Large',
            'files' => [
                ['path' => 'index.html', 'base64' => base64_encode(str_repeat('x', 11))],
            ],
        ])->assertHasErrors(['This deployment contains a file that is too large.']);
    }

    protected function toolCallPayload(array $arguments = []): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'publish',
                'arguments' => $arguments ?: [
                    'name' => 'Agent App',
                    'files' => [
                        ['path' => 'index.html', 'content' => 'hello'],
                    ],
                ],
            ],
        ];
    }

    protected function skipWithoutZip(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The Zip extension is required for MCP deployment tests.');
        }
    }
}
