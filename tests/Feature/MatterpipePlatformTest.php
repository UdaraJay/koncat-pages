<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Enums\WorkspaceRole;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\UserApiToken;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

class MatterpipePlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_team_owner_can_create_a_workspace_with_ulid_primary_key(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $response = $this
            ->actingAs($user)
            ->post(route('workspaces.store', $team), [
                'name' => 'Design Systems',
            ]);

        $workspace = Workspace::firstWhere('name', 'Design Systems');

        $response->assertRedirect(route('workspaces.show', [$team, $workspace]));
        $this->assertNotNull($workspace);
        $this->assertSame(26, strlen($workspace->id));
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Owner->value,
        ]);
    }

    public function test_workspace_membership_gates_workspace_pages(): void
    {
        [$owner, $member, $outsider] = User::factory()->count(3)->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $team->members()->attach($outsider, ['role' => TeamRole::Member->value]);

        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

        $this
            ->actingAs($member)
            ->get(route('workspaces.show', [$team, $workspace]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('workspaces/show')
                ->where('workspace.name', $workspace->name),
            );

        $this
            ->actingAs($outsider)
            ->get(route('workspaces.show', [$team, $workspace]))
            ->assertForbidden();
    }

    public function test_workspace_member_can_create_project_with_team_scoped_path(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $workspace->members()->attach($user, ['role' => WorkspaceRole::Member->value]);

        $this
            ->actingAs($user)
            ->post(route('workspaces.projects.store', [$team, $workspace]), [
                'name' => 'Lunch Poll',
                'slug' => 'lunch-poll',
            ])
            ->assertRedirect(route('workspaces.show', [$team, $workspace]));

        $this->assertDatabaseHas('projects', [
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $workspace->id,
            'name' => 'Lunch Poll',
            'slug' => 'lunch-poll',
        ]);
    }

    public function test_project_paths_are_scoped_to_hosting_team(): void
    {
        [$firstUser, $secondUser] = User::factory()->count(2)->create();

        $this
            ->actingAs($firstUser)
            ->post(route('projects.store'), [
                'owner_type' => 'user',
                'name' => 'Demo',
                'slug' => 'demo',
            ])
            ->assertRedirect(route('dashboard'));

        $this
            ->actingAs($secondUser)
            ->post(route('projects.store'), [
                'owner_type' => 'user',
                'name' => 'Demo',
                'slug' => 'demo',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(2, Project::where('slug', 'demo')->count());
    }

    public function test_duplicate_project_path_is_rejected_within_same_hosting_team(): void
    {
        $user = User::factory()->create();

        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'slug' => 'demo',
        ]);

        $this
            ->actingAs($user)
            ->post(route('projects.store'), [
                'owner_type' => 'user',
                'name' => 'Demo',
                'slug' => 'demo',
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_project_path_defaults_to_compact_generated_value(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('projects.store'), [
                'owner_type' => 'user',
                'name' => 'Tiny Canvas',
            ])
            ->assertRedirect(route('dashboard'));

        $project = Project::firstWhere('name', 'Tiny Canvas');

        $this->assertNotNull($project);
        $this->assertMatchesRegularExpression('/^[a-z]{6}-[0-9]{4}$/', $project->slug);
        $this->assertSame($user->personalTeam()->id, $project->hosting_team_id);
    }

    public function test_user_can_create_a_personal_project_from_the_gallery(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('projects.store'), [
                'owner_type' => 'user',
                'name' => 'Tiny Canvas',
                'slug' => 'tiny-canvas',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('projects', [
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'workspace_id' => null,
            'created_by' => $user->id,
            'name' => 'Tiny Canvas',
            'slug' => 'tiny-canvas',
        ]);
    }

    public function test_team_owner_can_create_a_team_project_without_a_workspace(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
        $user->switchTeam($team);

        $this
            ->actingAs($user)
            ->post(route('projects.store'), [
                'owner_type' => 'team',
                'team_id' => $team->id,
                'name' => 'Shared Canvas',
                'slug' => 'shared-canvas',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('projects', [
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => null,
            'created_by' => $user->id,
            'name' => 'Shared Canvas',
            'slug' => 'shared-canvas',
        ]);
    }

    public function test_project_owner_can_archive_and_restore_a_personal_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'created_by' => $user->id,
            'slug' => 'archive-me',
        ]);

        $this
            ->actingAs($user)
            ->delete(route('projects.archive', $project))
            ->assertRedirect();

        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);

        $this
            ->actingAs($user)
            ->post(route('projects.restore', $project))
            ->assertRedirect();

        $this->assertNotSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_project_owner_can_unpublish_a_project_without_deleting_deployments(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'created_by' => $user->id,
            'slug' => 'unpublish-me',
        ]);
        $deployment = Deployment::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => 'deployments/unpublish-me',
            'file_count' => 1,
            'total_bytes' => 128,
            'deployed_at' => now(),
        ]);
        $project->update(['current_deployment_id' => $deployment->id]);

        $this
            ->actingAs($user)
            ->post(route('projects.unpublish', $project))
            ->assertRedirect();

        $project->refresh();

        $this->assertNull($project->current_deployment_id);
        $this->assertDatabaseHas('deployments', [
            'id' => $deployment->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_zip_deployment_is_published_and_served_to_workspace_members(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $user = User::factory()->create();
        $team = $user->currentTeam;
        $team->update(['subdomain' => 'design-team']);
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $workspace->members()->attach($user, ['role' => WorkspaceRole::Member->value]);
        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $workspace->id,
            'name' => 'Team App',
            'slug' => 'team-app',
        ]);

        $this
            ->actingAs($user)
            ->post(route('workspaces.projects.deployments.store', [$team, $workspace, $project]), [
                'archive' => $this->zipUpload(['index.html' => '<h1>Hello</h1>']),
            ])
            ->assertRedirect();

        $project->refresh();

        $this->assertNotNull($project->current_deployment_id);

        $response = $this
            ->actingAs($user)
            ->get('http://design-team.localhost/team-app/index.html')
            ->assertOk();

        $response
            ->assertSee('Koncat')
            ->assertSee('Team App')
            ->assertSee('href="https://localhost/"', false)
            ->assertSee('href="https://localhost/home"', false)
            ->assertSee($user->name)
            ->assertSee('/team-app/__matterpipe/render/index.html', false);

        $rawResponse = $this
            ->actingAs($user)
            ->get('http://design-team.localhost/team-app/__matterpipe/render/index.html')
            ->assertOk();

        $this->assertStringContainsString('Hello', $rawResponse->streamedContent());
    }

    public function test_hosted_apps_require_workspace_membership(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $team = $member->currentTeam;
        $team->update(['subdomain' => 'private-team']);
        $team->members()->attach($outsider, ['role' => TeamRole::Member->value]);
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);
        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $workspace->id,
            'slug' => 'private-app',
        ]);

        $this
            ->actingAs($member)
            ->post(route('workspaces.projects.deployments.store', [$team, $workspace, $project]), [
                'archive' => $this->zipUpload(['index.html' => 'private']),
            ]);

        $this
            ->actingAs($outsider)
            ->get('http://private-team.localhost/private-app/')
            ->assertForbidden();
    }

    public function test_personal_project_is_deployed_and_served_to_its_owner(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $owner->personalTeam()->update(['subdomain' => 'personal-team']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'slug' => 'personal-canvas',
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.deployments.store', $project), [
                'archive' => $this->zipUpload(['index.html' => 'personal']),
            ])
            ->assertRedirect();

        $response = $this
            ->actingAs($owner)
            ->get('http://personal-team.localhost/personal-canvas/')
            ->assertOk();

        $response
            ->assertSee('Koncat')
            ->assertSee('href="https://localhost/"', false)
            ->assertSee('href="https://localhost/home"', false)
            ->assertSee($owner->name)
            ->assertSee('personal-canvas/__matterpipe/render', false);

        $rawResponse = $this
            ->actingAs($owner)
            ->get('http://personal-team.localhost/personal-canvas/__matterpipe/render')
            ->assertOk();

        $this->assertSame('personal', $rawResponse->streamedContent());

        $this
            ->actingAs($outsider)
            ->get('http://personal-team.localhost/personal-canvas/')
            ->assertForbidden();

        $this
            ->actingAs($outsider)
            ->get('http://personal-team.localhost/personal-canvas/__matterpipe/render')
            ->assertForbidden();
    }

    public function test_matterpipe_document_api_crud_is_scoped_to_hosted_project(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        $team->update(['subdomain' => 'docs-team']);
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $workspace->members()->attach($user, ['role' => WorkspaceRole::Member->value]);
        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $workspace->id,
            'slug' => 'docs-app',
        ]);

        $created = $this
            ->actingAs($user)
            ->postJson('http://docs-team.localhost/docs-app/__matterpipe/db/posts', [
                'data' => ['title' => 'Hello'],
            ])
            ->assertCreated()
            ->json();

        $this
            ->actingAs($user)
            ->patchJson('http://docs-team.localhost/docs-app/__matterpipe/db/posts/'.$created['id'], [
                'data' => ['status' => 'draft'],
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Hello')
            ->assertJsonPath('data.status', 'draft');

        $this
            ->actingAs($user)
            ->getJson('http://docs-team.localhost/docs-app/__matterpipe/db/posts')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_api_token_can_deploy_project_zip(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $user = User::factory()->create();
        $team = $user->currentTeam;
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $workspace->members()->attach($user, ['role' => WorkspaceRole::Member->value]);
        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $workspace->id,
            'slug' => 'agent-app',
        ]);
        $plainTextToken = UserApiToken::makePlainTextToken();

        $user->apiTokens()->create([
            'name' => 'Agent',
            'token_hash' => UserApiToken::hashToken($plainTextToken),
        ]);

        $this
            ->withToken($plainTextToken)
            ->post(route('api.projects.deployments.store', $project), [
                'archive' => $this->zipUpload(['index.html' => 'agent']),
            ])
            ->assertCreated()
            ->assertJsonPath('project.slug', 'agent-app');

        $this->assertNotNull($project->fresh()->current_deployment_id);
    }

    protected function zipUpload(array $files): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'matterpipe-test-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return new UploadedFile($path, 'deployment.zip', 'application/zip', null, true);
    }

    protected function skipWithoutZip(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The Zip extension is required for deployment tests.');
        }
    }
}
