<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Enums\WorkspaceRole;
use App\Models\Deployment;
use App\Models\DeploymentSecurityScan;
use App\Models\Project;
use App\Models\ProjectAnalyticsEvent;
use App\Models\ProjectFile;
use App\Models\ProjectShare;
use App\Models\Team;
use App\Models\User;
use App\Models\UserApiToken;
use App\Models\Workspace;
use App\Services\DeploymentPublisher;
use App\Services\MatterpipeRuntimeTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
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
        config([
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.render_domain' => 'render.localhost',
            'matterpipe.render_scheme' => 'http',
        ]);
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

    public function test_workspace_project_payload_includes_project_view_analytics(): void
    {
        $user = User::factory()->create();
        $viewer = User::factory()->create();
        $team = $user->currentTeam;
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $workspace->members()->attach($user, ['role' => WorkspaceRole::Member->value]);
        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $workspace->id,
            'hosting_team_id' => $team->id,
            'name' => 'Workspace Analytics App',
            'slug' => 'workspace-analytics-app',
        ]);

        ProjectAnalyticsEvent::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'event_type' => 'project.view',
            'path' => '/',
            'occurred_at' => now()->subDays(10),
        ]);
        ProjectAnalyticsEvent::query()->create([
            'project_id' => $project->id,
            'user_id' => $viewer->id,
            'event_type' => 'project.view',
            'path' => '/status',
            'occurred_at' => now()->subDays(2),
        ]);
        ProjectShare::factory()->count(3)->create([
            'project_id' => $project->id,
            'shared_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('workspaces.show', [$team, $workspace]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/show')
            ->has('projects', 1)
            ->where('projects.0.name', 'Workspace Analytics App')
            ->where('projects.0.analytics.viewsTotal', 2)
            ->where('projects.0.sharesCount', 3),
        );
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

    public function test_project_owner_can_move_project_between_personal_and_team_workspace(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'created_by' => $user->id,
            'name' => 'Portable App',
            'slug' => 'portable-app',
        ]);

        $this
            ->actingAs($user)
            ->patch(route('projects.move', $project), [
                'owner_type' => 'team',
                'team_id' => $team->id,
                'workspace_id' => $workspace->id,
                'slug' => 'portable-app',
            ])
            ->assertRedirect();

        $project->refresh();

        $this->assertSame(Team::class, $project->owner_type);
        $this->assertSame($team->id, $project->owner_id);
        $this->assertSame($workspace->id, $project->workspace_id);
        $this->assertSame($team->id, $project->hosting_team_id);

        $this
            ->actingAs($user)
            ->patch(route('projects.move', $project), [
                'owner_type' => 'user',
                'slug' => 'portable-app',
            ])
            ->assertRedirect();

        $project->refresh();

        $this->assertSame(User::class, $project->owner_type);
        $this->assertSame($user->id, $project->owner_id);
        $this->assertNull($project->workspace_id);
        $this->assertSame($user->personalTeam()->id, $project->hosting_team_id);
    }

    public function test_project_move_rejects_duplicate_path_in_destination_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'slug' => 'taken',
        ]);

        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'slug' => 'portable-app',
        ]);

        $this
            ->actingAs($user)
            ->patch(route('projects.move', $project), [
                'owner_type' => 'user',
                'slug' => 'taken',
            ])
            ->assertSessionHasErrors('slug');

        $project->refresh();

        $this->assertSame(Team::class, $project->owner_type);
        $this->assertSame($team->id, $project->hosting_team_id);
        $this->assertSame('portable-app', $project->slug);
    }

    public function test_workspace_member_without_delete_permission_cannot_move_project_out(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Member->value]);
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $workspace->members()->attach($user, ['role' => WorkspaceRole::Member->value]);
        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $workspace->id,
            'hosting_team_id' => $team->id,
            'slug' => 'team-app',
        ]);

        $this
            ->actingAs($user)
            ->patch(route('projects.move', $project), [
                'owner_type' => 'user',
                'slug' => 'team-app',
            ])
            ->assertForbidden();

        $project->refresh();

        $this->assertSame(Team::class, $project->owner_type);
        $this->assertSame($workspace->id, $project->workspace_id);
        $this->assertSame($team->id, $project->hosting_team_id);
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

    public function test_project_quotas_count_archived_projects(): void
    {
        config([
            'matterpipe.quotas.user_projects' => 1,
            'matterpipe.quotas.team_projects' => 1,
            'matterpipe.quotas.workspace_projects' => 1,
        ]);

        $personalUser = User::factory()->create();
        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $personalUser->id,
            'hosting_team_id' => $personalUser->personalTeam()->id,
            'slug' => 'archived-personal',
        ])->delete();

        $this
            ->actingAs($personalUser)
            ->post(route('projects.store'), [
                'owner_type' => 'user',
                'name' => 'New Personal',
                'slug' => 'new-personal',
            ])
            ->assertSessionHasErrors(['quota' => 'Your account has reached its project limit.']);

        $teamUser = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($teamUser, ['role' => TeamRole::Owner->value]);
        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'slug' => 'archived-team',
        ])->delete();

        $this
            ->actingAs($teamUser)
            ->post(route('projects.store'), [
                'owner_type' => 'team',
                'team_id' => $team->id,
                'name' => 'New Team',
                'slug' => 'new-team',
            ])
            ->assertSessionHasErrors(['quota' => 'This team has reached its project limit.']);

        $workspaceUser = User::factory()->create();
        $workspaceTeam = Team::factory()->create();
        $workspaceTeam->members()->attach($workspaceUser, ['role' => TeamRole::Owner->value]);
        $workspace = Workspace::factory()->create(['team_id' => $workspaceTeam->id]);
        $workspace->members()->attach($workspaceUser, ['role' => WorkspaceRole::Owner->value]);
        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $workspaceTeam->id,
            'workspace_id' => $workspace->id,
            'hosting_team_id' => $workspaceTeam->id,
            'slug' => 'archived-workspace',
        ])->delete();

        config(['matterpipe.quotas.team_projects' => 10]);

        $this
            ->actingAs($workspaceUser)
            ->post(route('workspaces.projects.store', [$workspaceTeam, $workspace]), [
                'name' => 'New Workspace',
                'slug' => 'new-workspace',
            ])
            ->assertSessionHasErrors(['quota' => 'This workspace has reached its project limit.']);
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
            ->assertSee('sandbox="allow-scripts allow-same-origin allow-forms allow-downloads allow-modals allow-popups"', false)
            ->assertSee('referrerpolicy="no-referrer"', false)
            ->assertSee('http://design-team.render.localhost/team-app/index.html?__matterpipe_render_token=', false);

        $project->refresh();

        $rawResponse = $this
            ->withUnencryptedCookie(MatterpipeRuntimeTokens::RENDER_COOKIE, $this->renderToken($project, $user))
            ->get('http://design-team.render.localhost/team-app/index.html')
            ->assertOk();

        $this->assertStringContainsString('Hello', $rawResponse->streamedContent());
    }

    public function test_zip_deployment_creates_passed_security_scan(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'safe-app',
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.deployments.store', $project), [
                'archive' => $this->zipUpload([
                    'index.html' => '<h1>Safe</h1>',
                    'app.js' => 'console.log("safe")',
                ]),
            ])
            ->assertRedirect();

        $deployment = $project->fresh()->currentDeployment;

        $this->assertNotNull($deployment);
        $this->assertDatabaseHas('deployment_security_scans', [
            'project_id' => $project->id,
            'deployment_id' => $deployment->id,
            'user_id' => $owner->id,
            'status' => 'passed',
            'highest_severity' => null,
            'risk_score' => 0,
            'scanner' => 'builtin',
            'scanner_version' => '1',
        ]);

        $this->assertSame([
            'status' => 'passed',
            'highestSeverity' => null,
            'riskScore' => 0,
            'findingsCount' => 0,
            'scannedAt' => DeploymentSecurityScan::first()->finished_at->toISOString(),
        ], $deployment->securityScanSummary());
    }

    public function test_project_details_include_recent_deployments_for_rollback(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'history-app',
        ]);

        Carbon::setTestNow('2026-06-20 10:00:00');
        $first = app(DeploymentPublisher::class)->publishFiles($project, [
            ['path' => 'index.html', 'contents' => 'first'],
        ], $owner);

        Carbon::setTestNow('2026-06-20 11:00:00');
        $second = app(DeploymentPublisher::class)->publishFiles($project, [
            ['path' => 'index.html', 'contents' => 'second'],
        ], $owner);
        Carbon::setTestNow();

        $this
            ->actingAs($owner)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('projects/show')
                ->where('project.currentDeployment.id', $second->id)
                ->where('deployments.0.id', $second->id)
                ->where('deployments.0.securityScan.status', 'passed')
                ->where('deployments.1.id', $first->id)
                ->where('deployments.1.securityScan.status', 'passed')
            );
    }

    public function test_project_owner_can_rollback_to_a_previous_deployment(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $owner = User::factory()->create();
        $owner->personalTeam()->update(['subdomain' => 'rollback-team']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'rollback-app',
        ]);

        $first = app(DeploymentPublisher::class)->publishFiles($project, [
            ['path' => 'index.html', 'contents' => 'first'],
        ], $owner);
        $second = app(DeploymentPublisher::class)->publishFiles($project, [
            ['path' => 'index.html', 'contents' => 'second'],
        ], $owner);

        $this->assertSame($second->id, $project->fresh()->current_deployment_id);

        $this
            ->actingAs($owner)
            ->post(route('projects.deployments.activate', [$project, $first]))
            ->assertRedirect();

        $this->assertSame($first->id, $project->fresh()->current_deployment_id);

        $rawResponse = $this
            ->withUnencryptedCookie(MatterpipeRuntimeTokens::RENDER_COOKIE, $this->renderToken($project->fresh(), $owner))
            ->get('http://rollback-team.render.localhost/rollback-app/index.html')
            ->assertOk();

        $this->assertSame('first', $rawResponse->streamedContent());
    }

    public function test_project_rollback_rejects_deployments_from_other_projects(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'primary-app',
        ]);
        $otherProject = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'other-app',
        ]);

        $deployment = app(DeploymentPublisher::class)->publishFiles($otherProject, [
            ['path' => 'index.html', 'contents' => 'other'],
        ], $owner);

        $this
            ->actingAs($owner)
            ->post(route('projects.deployments.activate', [$project, $deployment]))
            ->assertNotFound();
    }

    public function test_zip_deployment_blocks_high_risk_security_findings(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'blocked-app',
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.deployments.store', $project), [
                'archive' => $this->zipUpload([
                    'index.html' => '<script>eval("alert(1)")</script>',
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('security');

        $this->assertNull($project->fresh()->current_deployment_id);
        $this->assertDatabaseCount('deployments', 0);
        $this->assertDatabaseHas('deployment_security_scans', [
            'project_id' => $project->id,
            'deployment_id' => null,
            'user_id' => $owner->id,
            'status' => 'blocked',
            'highest_severity' => 'high',
            'risk_score' => 60,
        ]);
        $this->assertSame([], Storage::disk('local')->allFiles());
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

    public function test_top_level_hosted_project_visit_records_project_view_event(): void
    {
        $owner = User::factory()->create();
        $hostingDomain = config('matterpipe.hosting_domain');
        $owner->personalTeam()->update(['subdomain' => 'analytics-team']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'analytics-app',
        ]);
        $deployment = $project->deployments()->create([
            'user_id' => $owner->id,
            'disk' => 'local',
            'path' => 'deployments/analytics-app',
            'file_count' => 1,
            'total_bytes' => 12,
            'deployed_at' => now(),
        ]);
        $project->update(['current_deployment_id' => $deployment->id]);

        $this
            ->actingAs($owner)
            ->get("http://analytics-team.{$hostingDomain}/analytics-app/reports?range=week")
            ->assertOk();

        $this->assertDatabaseHas('project_analytics_events', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'deployment_id' => $deployment->id,
            'event_type' => 'project.view',
            'path' => '/reports',
        ]);
    }

    public function test_raw_hosted_render_requests_do_not_record_project_view_events(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $hostingDomain = config('matterpipe.hosting_domain');
        $owner->personalTeam()->update(['subdomain' => 'raw-analytics-team']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'raw-analytics-app',
        ]);
        Storage::disk('local')->put('deployments/raw-analytics-app/index.html', 'raw');
        $deployment = $project->deployments()->create([
            'user_id' => $owner->id,
            'disk' => 'local',
            'path' => 'deployments/raw-analytics-app',
            'file_count' => 1,
            'total_bytes' => 3,
            'deployed_at' => now(),
        ]);
        $project->update(['current_deployment_id' => $deployment->id]);

        $response = $this
            ->actingAs($owner)
            ->withUnencryptedCookie(MatterpipeRuntimeTokens::RENDER_COOKIE, $this->renderToken($project, $owner))
            ->get('http://raw-analytics-team.render.localhost/raw-analytics-app/index.html')
            ->assertOk();

        $this->assertSame('raw', $response->streamedContent());
        $this->assertDatabaseCount('project_analytics_events', 0);
    }

    public function test_unauthorized_hosted_project_access_does_not_record_project_view_event(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $hostingDomain = config('matterpipe.hosting_domain');
        $owner->personalTeam()->update(['subdomain' => 'private-analytics-team']);
        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'private-analytics-app',
        ]);

        $this
            ->actingAs($outsider)
            ->get("http://private-analytics-team.{$hostingDomain}/private-analytics-app/")
            ->assertForbidden();

        $this->assertDatabaseCount('project_analytics_events', 0);
    }

    public function test_hosted_project_guests_are_redirected_to_primary_domain_login(): void
    {
        $hostingDomain = config('matterpipe.hosting_domain');
        $hostingScheme = config('matterpipe.hosting_scheme') === 'http' ? 'http' : 'https';

        $this
            ->get("http://udara.{$hostingDomain}/essay-example")
            ->assertRedirect("{$hostingScheme}://{$hostingDomain}/login");
    }

    public function test_hosted_project_responses_allow_frames_from_hosting_domain_and_self(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.hosting_scheme' => 'http',
            'matterpipe.storage_disk' => 'local',
        ]);

        $owner = User::factory()->create();
        $owner->personalTeam()->update(['subdomain' => 'frame-team']);
        $hostingDomain = config('matterpipe.hosting_domain');
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'slug' => 'frame-app',
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.deployments.store', $project), [
                'archive' => $this->zipUpload(['index.html' => 'frameable']),
            ])
            ->assertRedirect();

        $policy = "frame-ancestors 'self' http://{$hostingDomain}";

        $this
            ->actingAs($owner)
            ->get("http://frame-team.{$hostingDomain}/frame-app/")
            ->assertOk()
            ->assertHeader('Content-Security-Policy', $policy)
            ->assertHeaderMissing('X-Frame-Options');

        $project->refresh();

        $rawResponse = $this
            ->withUnencryptedCookie(MatterpipeRuntimeTokens::RENDER_COOKIE, $this->renderToken($project, $owner))
            ->get('http://frame-team.render.localhost/frame-app/index.html')
            ->assertOk();

        $rawResponse->assertHeader(
            'Content-Security-Policy',
            "frame-ancestors 'self' http://localhost http://frame-team.localhost; object-src 'none'; base-uri 'none'",
        );
        $this->assertSame('frameable', $rawResponse->streamedContent());
    }

    public function test_zip_deployment_rejects_one_uncompressed_file_over_limit(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.quotas.deployment_bytes' => 1024,
            'matterpipe.quotas.deployment_file_bytes' => 100,
        ]);

        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'large-file-app',
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.deployments.store', $project), [
                'archive' => $this->zipUpload(['index.html' => str_repeat('x', 101)]),
            ])
            ->assertSessionHasErrors(['quota' => 'This deployment contains a file that is too large.']);
    }

    public function test_zip_deployment_rejects_total_uncompressed_bytes_over_limit(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.quotas.deployment_bytes' => 1024,
            'matterpipe.quotas.deployment_file_bytes' => 2048,
        ]);

        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'large-deployment-app',
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.deployments.store', $project), [
                'archive' => $this->zipUpload([
                    'index.html' => str_repeat('x', 700),
                    'app.js' => str_repeat('y', 400),
                ]),
            ])
            ->assertSessionHasErrors(['quota' => 'This deployment is too large.']);
    }

    public function test_hosted_render_url_uses_index_path_for_relative_assets(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $owner = User::factory()->create();
        $owner->personalTeam()->update(['subdomain' => 'asset-team']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'slug' => 'asset-app',
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.deployments.store', $project), [
                'archive' => $this->zipUpload([
                    'index.html' => '<link rel="stylesheet" href="style.css">',
                    'style.css' => 'body { color: red; }',
                ]),
            ])
            ->assertRedirect();

        $this
            ->actingAs($owner)
            ->get('http://asset-team.localhost/asset-app/')
            ->assertOk()
            ->assertSee('http://asset-team.render.localhost/asset-app/index.html?__matterpipe_render_token=', false);

        $project->refresh();

        $assetResponse = $this
            ->withUnencryptedCookie(MatterpipeRuntimeTokens::RENDER_COOKIE, $this->renderToken($project, $owner))
            ->get('http://asset-team.render.localhost/asset-app/style.css')
            ->assertOk();

        $this->assertSame('body { color: red; }', $assetResponse->streamedContent());
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
            ->assertSee('http://personal-team.render.localhost/personal-canvas/index.html?__matterpipe_render_token=', false);

        $project->refresh();

        $rawResponse = $this
            ->withUnencryptedCookie(MatterpipeRuntimeTokens::RENDER_COOKIE, $this->renderToken($project, $owner))
            ->get('http://personal-team.render.localhost/personal-canvas/index.html')
            ->assertOk();

        $this->assertSame('personal', $rawResponse->streamedContent());

        $this
            ->actingAs($outsider)
            ->get('http://personal-team.localhost/personal-canvas/')
            ->assertForbidden();

        $this
            ->actingAs($outsider)
            ->withUnencryptedCookie(MatterpipeRuntimeTokens::RENDER_COOKIE, 'invalid')
            ->get('http://personal-team.render.localhost/personal-canvas/index.html')
            ->assertForbidden();
    }

    public function test_matterpipe_document_api_crud_is_scoped_to_hosted_project(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        $team->update(['subdomain' => 'docs-team']);
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $workspace->members()->attach($user, ['role' => WorkspaceRole::Member->value]);
        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $workspace->id,
            'slug' => 'docs-app',
        ]);

        $this
            ->actingAs($user)
            ->postJson('http://docs-team.localhost/docs-app/__matterpipe/db/posts', [
                'data' => ['title' => 'Session only'],
            ])
            ->assertForbidden();

        $runtimeToken = $this->runtimeToken($project, $user);

        $created = $this
            ->actingAs($user)
            ->withToken($runtimeToken)
            ->postJson('http://docs-team.localhost/docs-app/__matterpipe/db/posts', [
                'data' => ['title' => 'Hello'],
            ])
            ->assertCreated()
            ->json();

        $this
            ->actingAs($user)
            ->withToken($runtimeToken)
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

    public function test_hosted_file_upload_rejects_file_over_upload_limit(): void
    {
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.quotas.project_file_upload_bytes' => 1024,
        ]);

        [$user, $project] = $this->hostedFileProject('upload-limit-team', 'upload-limit-app');

        $this
            ->actingAs($user)
            ->withToken($this->runtimeToken($project, $user))
            ->post('http://upload-limit-team.localhost/upload-limit-app/__matterpipe/files', [
                'file' => UploadedFile::fake()->create('asset.bin', 2),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['file' => 'The uploaded file is too large.']);
    }

    public function test_hosted_file_upload_rejects_project_file_count_over_limit(): void
    {
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.quotas.project_files' => 1,
        ]);

        [$user, $project] = $this->hostedFileProject('file-count-team', 'file-count-app');
        ProjectFile::create([
            'project_id' => $project->id,
            'uploaded_by' => $user->id,
            'disk' => 'local',
            'path' => 'existing.txt',
            'original_name' => 'existing.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
        ]);

        $this
            ->actingAs($user)
            ->withToken($this->runtimeToken($project, $user))
            ->post('http://file-count-team.localhost/file-count-app/__matterpipe/files', [
                'file' => UploadedFile::fake()->create('asset.bin', 1),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['quota' => 'This project has reached its file limit.']);
    }

    public function test_hosted_file_upload_rejects_total_project_file_storage_over_limit(): void
    {
        Storage::fake('local');
        config([
            'matterpipe.storage_disk' => 'local',
            'matterpipe.quotas.project_file_bytes' => 1024,
            'matterpipe.quotas.project_file_upload_bytes' => 1024,
        ]);

        [$user, $project] = $this->hostedFileProject('storage-limit-team', 'storage-limit-app');
        ProjectFile::create([
            'project_id' => $project->id,
            'uploaded_by' => $user->id,
            'disk' => 'local',
            'path' => 'existing.txt',
            'original_name' => 'existing.txt',
            'mime_type' => 'text/plain',
            'size' => 1020,
        ]);

        $this
            ->actingAs($user)
            ->withToken($this->runtimeToken($project, $user))
            ->post('http://storage-limit-team.localhost/storage-limit-app/__matterpipe/files', [
                'file' => UploadedFile::fake()->create('asset.bin', 1),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['quota' => 'This project has reached its file storage limit.']);
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
            ->assertJsonPath('project.slug', 'agent-app')
            ->assertJsonPath('deployment.securityScan.status', 'passed')
            ->assertJsonPath('deployment.securityScan.riskScore', 0);

        $this->assertNotNull($project->fresh()->current_deployment_id);
    }

    public function test_user_api_token_deployment_blocks_high_risk_security_findings(): void
    {
        $this->skipWithoutZip();
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'created_by' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'slug' => 'api-blocked-app',
        ]);
        $plainTextToken = UserApiToken::makePlainTextToken();

        $user->apiTokens()->create([
            'name' => 'Agent',
            'token_hash' => UserApiToken::hashToken($plainTextToken),
        ]);

        $this
            ->withToken($plainTextToken)
            ->post(route('api.projects.deployments.store', $project), [
                'archive' => $this->zipUpload(['index.html' => '<script>document.cookie</script>']),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('security');

        $this->assertNull($project->fresh()->current_deployment_id);
        $this->assertDatabaseHas('deployment_security_scans', [
            'project_id' => $project->id,
            'deployment_id' => null,
            'status' => 'blocked',
            'highest_severity' => 'high',
        ]);
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

    /**
     * @return array{0: User, 1: Project}
     */
    protected function hostedFileProject(string $subdomain, string $slug): array
    {
        $user = User::factory()->create();
        $user->personalTeam()->update(['subdomain' => $subdomain]);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'created_by' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'slug' => $slug,
        ]);

        return [$user, $project];
    }

    protected function skipWithoutZip(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The Zip extension is required for deployment tests.');
        }
    }

    protected function renderToken(Project $project, User $user): string
    {
        return app(MatterpipeRuntimeTokens::class)->makeRenderToken($project, $user);
    }

    protected function runtimeToken(Project $project, User $user): string
    {
        return app(MatterpipeRuntimeTokens::class)->makeRuntimeToken($project, $user);
    }
}
