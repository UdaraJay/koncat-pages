<?php

namespace Tests\Feature\Settings;

use App\Enums\TeamRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UsageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_usage_page_shows_account_usage_and_deployment_limits(): void
    {
        config([
            'matterpipe.quotas.user_projects' => 3,
            'matterpipe.quotas.deployment_files' => 12,
            'matterpipe.quotas.deployment_bytes' => 8192,
            'matterpipe.quotas.deployment_file_bytes' => 4096,
        ]);

        $user = User::factory()->create();
        $personalTeam = $user->personalTeam();

        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $personalTeam->id,
            'slug' => 'archived-personal',
        ])->delete();

        Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'hosting_team_id' => $personalTeam->id,
            'name' => 'Personal App',
            'slug' => 'personal-app',
        ]);

        $this
            ->actingAs($user)
            ->get(route('usage.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/usage')
                ->where('usage.account.projects.used', 2)
                ->where('usage.account.projects.limit', 3)
                ->where('usage.deploymentLimits.files', 12)
                ->where('usage.deploymentLimits.bytes', 8192)
                ->where('usage.deploymentLimits.fileBytes', 4096)
                ->missing('usage.projectResources'),
            );
    }

    public function test_team_owner_sees_current_team_totals(): void
    {
        config([
            'matterpipe.quotas.team_projects' => 4,
            'matterpipe.quotas.team_workspaces' => 2,
            'matterpipe.quotas.workspace_projects' => 3,
        ]);

        $user = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Product']);
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
        $user->switchTeam($team);

        $workspace = Workspace::factory()->create([
            'team_id' => $team->id,
            'name' => 'Roadmaps',
        ]);

        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'slug' => 'archived-team',
        ])->delete();
        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $workspace->id,
            'hosting_team_id' => $team->id,
            'slug' => 'workspace-project',
        ]);

        $this
            ->actingAs($user)
            ->get(route('usage.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/usage')
                ->where('usage.team.name', 'Product')
                ->where('usage.team.canSeeTeamTotals', true)
                ->where('usage.team.projects.used', 2)
                ->where('usage.team.projects.limit', 4)
                ->where('usage.team.workspaces.used', 1)
                ->where('usage.team.workspaces.limit', 2)
                ->where('usage.team.visibleWorkspaces.0.name', 'Roadmaps')
                ->where('usage.team.visibleWorkspaces.0.projects.used', 1)
                ->where('usage.team.visibleWorkspaces.0.projects.limit', 3),
            );
    }

    public function test_regular_team_member_only_sees_visible_team_usage(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Design']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $member->switchTeam($team);

        $visibleWorkspace = Workspace::factory()->create([
            'team_id' => $team->id,
            'name' => 'Visible',
        ]);
        $visibleWorkspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);
        $hiddenWorkspace = Workspace::factory()->create([
            'team_id' => $team->id,
            'name' => 'Hidden',
        ]);

        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $visibleWorkspace->id,
            'hosting_team_id' => $team->id,
            'name' => 'Visible App',
            'slug' => 'visible-app',
        ]);
        Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'workspace_id' => $hiddenWorkspace->id,
            'hosting_team_id' => $team->id,
            'name' => 'Hidden App',
            'slug' => 'hidden-app',
        ]);

        $this
            ->actingAs($member)
            ->get(route('usage.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/usage')
                ->where('usage.team.canSeeTeamTotals', false)
                ->missing('usage.team.projects')
                ->missing('usage.team.workspaces')
                ->has('usage.team.visibleWorkspaces', 1)
                ->where('usage.team.visibleWorkspaces.0.name', 'Visible')
                ->missing('usage.projectResources'),
            );
    }
}
