<?php

namespace Tests\Feature;

use App\Enums\ProjectSharePermission;
use App\Enums\TeamRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_policy_maps_team_roles_to_capabilities(): void
    {
        [$owner, $admin, $creator, $reader] = User::factory()->count(4)->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
        $team->members()->attach($creator, ['role' => TeamRole::Creator->value]);
        $team->members()->attach($reader, ['role' => TeamRole::ReadOnly->value]);

        $this->assertTrue($owner->can('delete', $team));
        $this->assertTrue($admin->can('updateMember', $team));
        $this->assertTrue($admin->can('manageWorkspaces', $team));

        $this->assertTrue($creator->can('view', $team));
        $this->assertTrue($creator->can('createProject', $team));
        $this->assertFalse($creator->can('update', $team));
        $this->assertFalse($creator->can('inviteMember', $team));

        $this->assertTrue($reader->can('view', $team));
        $this->assertFalse($reader->can('createProject', $team));
        $this->assertFalse($reader->can('update', $team));
    }

    public function test_workspace_policy_allows_team_read_and_workspace_specific_write(): void
    {
        [$admin, $reader, $workspaceMember] = User::factory()->count(3)->create();
        $team = Team::factory()->create();
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);

        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
        $team->members()->attach($reader, ['role' => TeamRole::ReadOnly->value]);
        $team->members()->attach($workspaceMember, ['role' => TeamRole::ReadOnly->value]);
        $workspace->members()->attach($workspaceMember, ['role' => WorkspaceRole::Member->value]);

        $this->assertTrue($admin->can('update', $workspace));
        $this->assertTrue($reader->can('view', $workspace));
        $this->assertFalse($reader->can('createProject', $workspace));

        $this->assertTrue($workspaceMember->can('view', $workspace));
        $this->assertTrue($workspaceMember->can('createProject', $workspace));
        $this->assertFalse($workspaceMember->can('deleteProject', $workspace));
    }

    public function test_workspace_policy_denies_stale_workspace_membership_without_team_membership(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);

        $workspace->members()->attach($user, ['role' => WorkspaceRole::Member->value]);

        $this->assertFalse($user->can('view', $workspace));
        $this->assertFalse($user->can('createProject', $workspace));
    }

    public function test_project_policy_limits_creator_write_access_to_owned_projects(): void
    {
        [$owner, $creator, $reader] = User::factory()->count(3)->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($creator, ['role' => TeamRole::Creator->value]);
        $team->members()->attach($reader, ['role' => TeamRole::ReadOnly->value]);

        $creatorProject = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'created_by' => $creator->id,
        ]);
        $ownerProject = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'created_by' => $owner->id,
        ]);

        $this->assertTrue($reader->can('view', $ownerProject));
        $this->assertFalse($reader->can('write', $ownerProject));
        $this->assertFalse($reader->can('deploy', $ownerProject));

        $this->assertTrue($creator->can('update', $creatorProject));
        $this->assertTrue($creator->can('delete', $creatorProject));
        $this->assertTrue($creator->can('deploy', $creatorProject));
        $this->assertTrue($creator->can('share', $creatorProject));

        $this->assertTrue($creator->can('view', $ownerProject));
        $this->assertFalse($creator->can('update', $ownerProject));
        $this->assertFalse($creator->can('delete', $ownerProject));
        $this->assertFalse($creator->can('deploy', $ownerProject));
        $this->assertFalse($creator->can('share', $ownerProject));
    }

    public function test_project_share_policy_grants_write_without_deploy_delete_or_share(): void
    {
        [$owner, $recipient] = User::factory()->count(2)->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'created_by' => $owner->id,
        ]);
        $share = ProjectShare::factory()->forUser($recipient)->write()->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
        ]);

        $this->assertTrue($owner->can('create', [ProjectShare::class, $project]));
        $this->assertTrue($owner->can('update', $share));
        $this->assertTrue($recipient->can('view', $project));
        $this->assertTrue($recipient->can('write', $project));
        $this->assertTrue($recipient->can('update', $project));
        $this->assertFalse($recipient->can('deploy', $project));
        $this->assertFalse($recipient->can('delete', $project));
        $this->assertFalse($recipient->can('share', $project));
        $this->assertFalse($recipient->can('create', [ProjectShare::class, $project]));
    }

    public function test_read_project_share_does_not_grant_project_write(): void
    {
        [$owner, $recipient] = User::factory()->count(2)->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'created_by' => $owner->id,
        ]);

        ProjectShare::factory()->forUser($recipient)->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
            'permission' => ProjectSharePermission::Read,
        ]);

        $this->assertTrue($recipient->can('view', $project));
        $this->assertFalse($recipient->can('write', $project));
        $this->assertFalse($recipient->can('update', $project));
    }
}
