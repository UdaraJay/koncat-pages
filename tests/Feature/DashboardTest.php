<?php

namespace Tests\Feature;

use App\Enums\ProjectSharePermission;
use App\Enums\TeamRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectAnalyticsEvent;
use App\Models\ProjectShare;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_project_owner_can_visit_project_detail_page()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'created_by' => $user->id,
            'name' => 'Detail App',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('projects.show', $project));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->where('project.id', $project->id)
            ->where('project.name', 'Detail App')
            ->has('moveTargets')
            ->has('projectSharePermissions'));
    }

    public function test_direct_project_share_can_visit_project_detail_page()
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'recipient@example.com']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'created_by' => $owner->id,
            'name' => 'Shared Detail App',
        ]);

        ProjectShare::factory()->forUser($recipient)->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
            'permission' => ProjectSharePermission::Read,
        ]);

        $response = $this
            ->actingAs($recipient)
            ->get(route('projects.show', $project));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->where('project.name', 'Shared Detail App')
            ->where('project.sharePermission', ProjectSharePermission::Read->value)
            ->where('project.canManageShares', false));
    }

    public function test_unrelated_user_cannot_visit_project_detail_page()
    {
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'created_by' => $owner->id,
        ]);

        $this
            ->actingAs($visitor)
            ->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_archived_project_owner_can_visit_project_detail_page()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'created_by' => $user->id,
            'name' => 'Archived Detail App',
        ]);
        $project->delete();
        $archivedAt = $project->deleted_at?->toISOString();

        $response = $this
            ->actingAs($user)
            ->get(route('projects.show', $project));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->where('project.name', 'Archived Detail App')
            ->where('project.deletedAt', $archivedAt)
            ->where('project.canRestore', true));
    }

    public function test_dashboard_project_cards_include_a_preview_url()
    {
        config([
            'matterpipe.hosting_domain' => 'localhost',
            'matterpipe.hosting_scheme' => 'http',
            'matterpipe.render_domain' => 'render.localhost',
            'matterpipe.render_scheme' => 'http',
        ]);

        $user = User::factory()->create();
        $user->personalTeam()->update(['subdomain' => 'preview-team']);

        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'created_by' => $user->id,
            'name' => 'Preview App',
            'slug' => 'preview-app',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('projects', 1)
            ->where('projects.0.name', 'Preview App')
            ->where('projects.0.url', 'http://preview-team.localhost/preview-app')
            ->where('projects.0.previewUrl', fn (string $value): bool => str_starts_with(
                $value,
                'http://render.localhost/preview-team/preview-app/index.html?__matterpipe_render_token=',
            )),
        );
    }

    public function test_dashboard_project_cards_include_project_view_analytics()
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create(['email' => 'viewer@example.com']);
        $quietViewer = User::factory()->create(['email' => 'quiet@example.com']);
        $owner->personalTeam()->update(['subdomain' => 'analytics-dashboard-team']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'name' => 'Analytics App',
            'slug' => 'analytics-dashboard-app',
        ]);

        ProjectAnalyticsEvent::query()->create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'event_type' => 'project.view',
            'path' => '/',
            'occurred_at' => now()->subDays(8),
        ]);
        ProjectAnalyticsEvent::query()->create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'event_type' => 'project.view',
            'path' => '/reports',
            'occurred_at' => now()->subDay(),
        ]);
        ProjectAnalyticsEvent::query()->create([
            'project_id' => $project->id,
            'user_id' => $viewer->id,
            'event_type' => 'project.view',
            'path' => '/reports',
            'occurred_at' => now(),
        ]);
        ProjectShare::factory()->forUser($viewer)->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
        ]);
        ProjectShare::factory()->forUser($quietViewer)->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($owner)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('projects', 1)
            ->where('projects.0.name', 'Analytics App')
            ->where('projects.0.analytics.viewsTotal', 3)
            ->where('projects.0.sharesCount', 2)
            ->where('projects.0.analytics.sharedUsers.0.email', 'viewer@example.com')
            ->where('projects.0.analytics.sharedUsers.0.viewsTotal', 1)
            ->where('projects.0.analytics.sharedUsers.0.pending', false)
            ->where('projects.0.analytics.sharedUsers.1.email', 'quiet@example.com')
            ->where('projects.0.analytics.sharedUsers.1.viewsTotal', 0),
        );
    }

    public function test_project_owner_can_update_project_card_details()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'name' => 'Original App',
            'description' => 'Original description',
        ]);

        $this
            ->actingAs($user)
            ->patch(route('projects.update', $project), [
                'name' => 'Updated App',
                'description' => 'Updated description',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated App',
            'description' => 'Updated description',
        ]);
    }

    public function test_direct_write_project_share_can_update_project_card_details()
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'writer@example.com']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'name' => 'Owner App',
            'description' => 'Owner description',
        ]);

        ProjectShare::factory()->forUser($recipient)->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
            'permission' => ProjectSharePermission::Write,
        ]);

        $this
            ->actingAs($recipient)
            ->patch(route('projects.update', $project), [
                'name' => 'Shared Rename',
                'description' => 'Shared description',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Shared Rename',
            'description' => 'Shared description',
        ]);
    }

    public function test_dashboard_hides_archived_projects_by_default()
    {
        $user = User::factory()->create();

        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'name' => 'Active App',
            'slug' => 'active-app',
        ]);

        $archivedProject = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'name' => 'Archived App',
            'slug' => 'archived-app',
        ]);
        $archivedProject->delete();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('projects', 1)
            ->where('projects.0.name', 'Active App')
            ->where('projectFilters.status', 'active')
        );
    }

    public function test_dashboard_can_show_archived_projects()
    {
        $user = User::factory()->create();

        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'name' => 'Active App',
            'slug' => 'active-app',
        ]);

        $archivedProject = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'name' => 'Archived App',
            'slug' => 'archived-app',
        ]);
        $archivedProject->delete();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard', ['status' => 'archived']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('projects', 1)
            ->where('projects.0.name', 'Archived App')
            ->where('projects.0.canRestore', true)
            ->where('projects.0.canDeletePermanently', true)
            ->where('projectFilters.status', 'archived')
            ->where('projects.0.deletedAt', fn ($value) => $value !== null)
        );
    }

    public function test_dashboard_includes_pending_invitations_for_the_authenticated_user()
    {
        $owner = User::factory()->create(['name' => 'Taylor Otwell']);
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create(['name' => 'Laravel Team']);

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($invitedUser)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('pendingInvitations', 1)
            ->where('pendingInvitations.0.code', $invitation->code)
            ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
            ->where('pendingInvitations.0.team.name', 'Laravel Team')
            ->where('pendingInvitations.0.team.slug', $team->slug)
            ->missing('pendingInvitations.0.teamName'),
        );
    }

    public function test_dashboard_does_not_include_accepted_invitations()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        TeamInvitation::factory()->accepted()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($invitedUser)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('pendingInvitations', 0),
        );
    }

    public function test_dashboard_excludes_expired_invitations_without_deleting_them()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->expired()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($invitedUser)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('pendingInvitations', 0),
        );

        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_dashboard_does_not_include_or_delete_other_users_invitations()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->expired()->create([
            'team_id' => $team->id,
            'email' => 'someone@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($invitedUser)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('pendingInvitations', 0),
        );

        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_personal_home_shows_personal_projects_and_direct_shares_only(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'recipient@example.com']);
        $team = Team::factory()->create(['name' => 'Acme']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($recipient, ['role' => TeamRole::ReadOnly->value]);

        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $recipient->id,
            'hosting_team_id' => $recipient->personalTeam()->id,
            'name' => 'Personal App',
        ]);

        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'workspace_id' => null,
            'name' => 'Team App',
        ]);

        $sharedProject = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'name' => 'Shared App',
        ]);

        ProjectShare::factory()->forUser($recipient)->create([
            'project_id' => $sharedProject->id,
            'shared_by' => $owner->id,
        ]);

        $recipient->switchTeam($recipient->personalTeam());

        $this
            ->actingAs($recipient)
            ->get(route('dashboard', ['sort' => 'name_asc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('homeScope.team.isPersonal', true)
                ->where('homeScope.projectLabel', 'My projects')
                ->has('projects', 1)
                ->where('projects.0.name', 'Personal App')
                ->has('sharedProjects', 1)
                ->where('sharedProjects.0.name', 'Shared App'));
    }

    public function test_read_only_work_team_home_can_view_team_and_workspace_projects(): void
    {
        [$owner, $member] = User::factory()->count(2)->create();
        $team = Team::factory()->create(['name' => 'Acme']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::ReadOnly->value]);
        $member->switchTeam($team);

        $memberWorkspace = Workspace::factory()->create(['team_id' => $team->id]);
        $memberWorkspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);
        $privateWorkspace = Workspace::factory()->create(['team_id' => $team->id]);

        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'workspace_id' => null,
            'name' => 'Team Level App',
        ]);
        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'workspace_id' => $memberWorkspace->id,
            'name' => 'Member Workspace App',
        ]);
        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'workspace_id' => $privateWorkspace->id,
            'name' => 'Private Workspace App',
        ]);

        $this
            ->actingAs($member)
            ->get(route('dashboard', ['sort' => 'name_asc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('homeScope.team.isPersonal', false)
                ->where('homeScope.projectLabel', 'Team projects')
                ->has('projects', 3)
                ->where('projects.0.name', 'Member Workspace App')
                ->where('projects.1.name', 'Private Workspace App')
                ->where('projects.2.name', 'Team Level App')
                ->has('sharedProjects', 0)
                ->has('createOptions.owners', 1)
                ->where('createOptions.owners.0.canCreateProject', false)
                ->has('createOptions.owners.0.workspaces', 1)
                ->where('createOptions.owners.0.workspaces.0.name', $memberWorkspace->name));
    }

    public function test_team_owners_can_see_all_workspace_projects_on_work_team_home(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Acme']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $owner->switchTeam($team);

        $workspace = Workspace::factory()->create(['team_id' => $team->id]);

        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'workspace_id' => $workspace->id,
            'name' => 'Workspace App',
        ]);

        $this
            ->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('projects', 1)
                ->where('projects.0.name', 'Workspace App')
                ->where('createOptions.owners.0.canCreateProject', true));
    }

    public function test_direct_email_shares_for_team_projects_are_superseded_by_team_access(): void
    {
        [$owner, $recipient] = User::factory()->count(2)->create();
        $team = Team::factory()->create(['name' => 'Acme']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($recipient, ['role' => TeamRole::ReadOnly->value]);

        $workspace = Workspace::factory()->create(['team_id' => $team->id]);
        $sharedProject = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'workspace_id' => $workspace->id,
            'name' => 'Directly Shared Workspace App',
        ]);
        ProjectShare::factory()->forUser($recipient)->create([
            'project_id' => $sharedProject->id,
            'shared_by' => $owner->id,
        ]);

        $recipient->switchTeam($team);

        $this
            ->actingAs($recipient)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('projects', 1)
                ->where('projects.0.name', 'Directly Shared Workspace App')
                ->has('sharedProjects', 0));

        $recipient->switchTeam($recipient->personalTeam());

        $this
            ->actingAs($recipient)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('projects', 0)
                ->has('sharedProjects', 0));
    }

    public function test_switching_current_team_changes_home_and_sidebar_projects(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Acme']);
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $user->personalTeam()->id,
            'name' => 'Personal App',
        ]);
        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'workspace_id' => null,
            'name' => 'Team App',
        ]);

        $user->switchTeam($user->personalTeam());

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('projects', 1)
                ->where('projects.0.name', 'Personal App')
                ->has('currentTeamProjects', 1)
                ->where('currentTeamProjects.0.name', 'Personal App'));

        $user->switchTeam($team);

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('projects', 1)
                ->where('projects.0.name', 'Team App')
                ->has('currentTeamProjects', 1)
                ->where('currentTeamProjects.0.name', 'Team App'));
    }
}
