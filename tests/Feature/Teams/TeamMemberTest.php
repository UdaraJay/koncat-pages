<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Enums\WorkspaceRole;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_member_roles_can_be_updated_by_owners()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $response = $this
            ->actingAs($owner)
            ->patch(route('team-settings.members.update', [$team, $member]), [
                'role' => TeamRole::Admin->value,
            ]);

        $response->assertRedirect(route('team-settings.members.index', $team));

        $this->assertEquals(
            TeamRole::Admin->value,
            $team->members()->where('user_id', $member->id)->first()->pivot->role->value,
        );
    }

    public function test_team_member_roles_can_be_updated_by_admins()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('team-settings.members.update', [$team, $member]), [
                'role' => TeamRole::ReadOnly->value,
            ]);

        $response->assertRedirect(route('team-settings.members.index', $team));

        $this->assertEquals(
            TeamRole::ReadOnly->value,
            $team->members()->where('user_id', $member->id)->first()->pivot->role->value,
        );
    }

    public function test_team_member_roles_cannot_be_updated_by_creators()
    {
        $owner = User::factory()->create();
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($creator, ['role' => TeamRole::Creator->value]);
        $team->members()->attach($member, ['role' => TeamRole::ReadOnly->value]);

        $response = $this
            ->actingAs($creator)
            ->patch(route('team-settings.members.update', [$team, $member]), [
                'role' => TeamRole::Admin->value,
            ]);

        $response->assertForbidden();
    }

    public function test_team_members_can_be_removed_by_owners()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $response = $this
            ->actingAs($owner)
            ->delete(route('team-settings.members.destroy', [$team, $member]));

        $response->assertRedirect(route('team-settings.members.index', $team));

        $this->assertFalse($member->fresh()->belongsToTeam($team));
    }

    public function test_removing_team_member_removes_team_workspace_memberships()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $workspace = Workspace::factory()->create(['team_id' => $team->id]);

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);
        $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

        $this
            ->actingAs($owner)
            ->delete(route('team-settings.members.destroy', [$team, $member]))
            ->assertRedirect(route('team-settings.members.index', $team));

        $this->assertFalse($member->fresh()->belongsToTeam($team));
        $this->assertFalse($member->fresh()->belongsToWorkspace($workspace));
    }

    public function test_team_members_can_be_removed_by_admins()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('team-settings.members.destroy', [$team, $member]));

        $response->assertRedirect(route('team-settings.members.index', $team));

        $this->assertFalse($member->fresh()->belongsToTeam($team));
    }

    public function test_team_members_cannot_be_removed_by_creators()
    {
        $owner = User::factory()->create();
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($creator, ['role' => TeamRole::Creator->value]);
        $team->members()->attach($member, ['role' => TeamRole::ReadOnly->value]);

        $response = $this
            ->actingAs($creator)
            ->delete(route('team-settings.members.destroy', [$team, $member]));

        $response->assertForbidden();
    }

    public function test_team_owner_cannot_be_removed()
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($owner)
            ->delete(route('team-settings.members.destroy', [$team, $owner]));

        $response->assertForbidden();

        $this->assertTrue($owner->fresh()->belongsToTeam($team));
    }

    public function test_team_owner_role_cannot_be_changed()
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($owner)
            ->patch(route('team-settings.members.update', [$team, $owner]), [
                'role' => TeamRole::Admin->value,
            ]);

        $response->assertForbidden();

        $this->assertEquals(
            TeamRole::Owner->value,
            $team->members()->where('user_id', $owner->id)->first()->pivot->role->value,
        );
    }

    public function test_team_member_role_cannot_be_set_to_owner()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $response = $this
            ->actingAs($owner)
            ->patch(route('team-settings.members.update', [$team, $member]), [
                'role' => TeamRole::Owner->value,
            ]);

        $response->assertSessionHasErrors('role');

        $this->assertEquals(
            TeamRole::Creator->value,
            $team->members()->where('user_id', $member->id)->first()->pivot->role->value,
        );
    }

    public function test_team_member_role_cannot_be_set_to_legacy_member()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $response = $this
            ->actingAs($owner)
            ->patch(route('team-settings.members.update', [$team, $member]), [
                'role' => 'member',
            ]);

        $response->assertSessionHasErrors('role');

        $this->assertEquals(
            TeamRole::Creator->value,
            $team->members()->where('user_id', $member->id)->first()->pivot->role->value,
        );
    }

    public function test_removed_member_current_team_is_set_to_personal_team()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $personalTeam = $member->personalTeam();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $member->update(['current_team_id' => $team->id]);

        $this
            ->actingAs($owner)
            ->delete(route('team-settings.members.destroy', [$team, $member]));

        $this->assertEquals($personalTeam->id, $member->fresh()->current_team_id);
    }
}
