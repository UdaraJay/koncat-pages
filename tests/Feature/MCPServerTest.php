<?php

namespace Tests\Feature;

use App\Mcp\Servers\MCPServer;
use App\Mcp\Tools\DeployProjectTool;
use App\Mcp\Tools\FetchProjectTool;
use App\Models\Project;
use App\Models\User;
use App\Models\UserApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            ->assertName('deploy-project')
            ->assertStructuredContent(fn ($json) => $json
                ->where('action', 'created')
                ->where('project.slug', 'agent-app')
                ->where('project.url', 'http://agent-team.localhost/agent-app')
                ->where('deployment.fileCount', 2)
                ->where('deployment.totalBytes', strlen('<h1>Hello agent</h1>') + strlen("binary\0content"))
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

    public function test_mcp_route_rejects_missing_invalid_and_expired_tokens(): void
    {
        $this->postJson('/mcp', $this->toolCallPayload())
            ->assertUnauthorized();

        $this->withToken('mp_invalid')
            ->postJson('/mcp', $this->toolCallPayload())
            ->assertUnauthorized();

        $user = User::factory()->create();
        $plainTextToken = UserApiToken::makePlainTextToken();
        $user->apiTokens()->create([
            'name' => 'Expired',
            'token_hash' => UserApiToken::hashToken($plainTextToken),
            'expires_at' => now()->subMinute(),
        ]);

        $this->withToken($plainTextToken)
            ->postJson('/mcp', $this->toolCallPayload())
            ->assertUnauthorized();
    }

    public function test_mcp_route_accepts_valid_bearer_token_and_deploys_project(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.hosting_scheme' => 'http',
        ]);

        $user = User::factory()->create();
        $user->personalTeam()->update(['subdomain' => 'route-team']);
        $plainTextToken = UserApiToken::makePlainTextToken();
        $token = $user->apiTokens()->create([
            'name' => 'Agent',
            'token_hash' => UserApiToken::hashToken($plainTextToken),
        ]);

        $this->withToken($plainTextToken)
            ->postJson('/mcp', $this->toolCallPayload([
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

        $this->assertNotNull($token->fresh()->last_used_at);
        $this->assertDatabaseHas('projects', [
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'slug' => 'route-app',
        ]);
    }

    public function test_fetch_project_tool_returns_current_deployment_files_from_flexible_url(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.hosting_scheme' => 'http',
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
            'url' => 'agent-team.localhost:8000/agent-app/__matterpipe/render/assets/app.bin?cache=1#preview',
        ])
            ->assertOk()
            ->assertName('fetch-project')
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
            'url' => 'agent-team.localhost:8000/agent-app/__matterpipe/render',
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
        config(['matterpipe.hosting_domain' => 'localhost']);

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
    }

    protected function toolCallPayload(array $arguments = []): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'deploy-project',
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
