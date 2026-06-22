<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_teams_index_page_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('teams.index'));

        $response->assertOk();
    }

    public function test_teams_can_be_created()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('teams.store'), [
                'name' => 'Test Team',
            ]);

        $team = Team::where('name', 'Test Team')->firstOrFail();

        $response->assertRedirect(route('team-settings.general', $team));

        $this->assertDatabaseHas('teams', [
            'name' => 'Test Team',
            'subdomain' => 'test-team',
            'is_personal' => false,
        ]);
    }

    public function test_team_subdomain_uses_next_available_suffix()
    {
        $user = User::factory()->create();

        Team::factory()->create(['name' => 'Acme', 'slug' => 'acme', 'subdomain' => 'acme']);
        Team::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1', 'subdomain' => 'acme-1']);

        $this
            ->actingAs($user)
            ->post(route('teams.store'), [
                'name' => 'Acme',
            ]);

        $this->assertDatabaseHas('teams', [
            'name' => 'Acme',
            'subdomain' => 'acme-2',
        ]);
    }

    public function test_team_slug_uses_next_available_suffix()
    {
        $user = User::factory()->create();

        Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
        Team::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
        Team::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

        $this
            ->actingAs($user)
            ->post(route('teams.store'), [
                'name' => 'Acme',
            ]);

        $this->assertDatabaseHas('teams', [
            'name' => 'Acme',
            'slug' => 'acme-11',
        ]);
    }

    public function test_the_team_general_settings_page_can_be_rendered()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->get(route('team-settings.general', $team));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('team-settings/general')
                ->where('team.name', $team->name)
                ->where('permissions.canUpdateTeam', true),
            );
    }

    public function test_the_team_members_settings_page_can_be_rendered()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->get(route('team-settings.members.index', $team));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('team-settings/members')
                ->where('members.0.role', TeamRole::Owner->value)
                ->where('members.0.role_label', TeamRole::Owner->label()),
            );
    }

    public function test_the_team_branding_settings_page_can_be_rendered()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $team = Team::factory()->create([
            'brand_logo_path' => 'teams/test-team/branding/logo.png',
            'brand_background_color' => '#123456',
            'brand_foreground_color' => '#abcdef',
        ]);

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->get(route('team-settings.branding', $team));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('team-settings/branding')
                ->where('team.name', $team->name)
                ->where('team.brandLogoUrl', Storage::disk('public')->url('teams/test-team/branding/logo.png'))
                ->where('team.brandBackgroundColor', '#123456')
                ->where('team.brandForegroundColor', '#abcdef')
                ->where('permissions.canUpdateTeam', true),
            );
    }

    public function test_creator_and_read_only_are_assignable_but_owner_and_member_are_not()
    {
        $this->assertSame(
            [
                TeamRole::Admin->value,
                TeamRole::Creator->value,
                TeamRole::ReadOnly->value,
            ],
            array_column(TeamRole::assignable(), 'value'),
        );
    }

    public function test_legacy_member_memberships_and_invitations_are_migrated_to_creator()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        DB::table('team_members')->insert([
            'id' => (string) Str::ulid(),
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('team_invitations')->insert([
            'id' => (string) Str::ulid(),
            'code' => Str::random(64),
            'team_id' => $team->id,
            'email' => 'legacy@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'expires_at' => null,
            'accepted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_06_22_000000_migrate_team_members_to_creator_role.php');

        $migration->up();

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamRole::Creator->value,
        ]);
        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'legacy@example.com',
            'role' => TeamRole::Creator->value,
        ]);
    }

    public function test_teams_can_be_updated_by_owners()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Original Name']);

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->patch(route('team-settings.update', $team), [
                'name' => 'Updated Name',
                'subdomain' => 'updated-team',
            ]);

        $response->assertRedirect(route('team-settings.general', $team->fresh()));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Name',
            'subdomain' => 'updated-team',
        ]);
    }

    public function test_team_branding_can_be_updated_by_admins()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('team-settings.branding.update', $team), [
                'brand_background_color' => '#102030',
                'brand_foreground_color' => '#f0e0d0',
            ]);

        $response->assertRedirect(route('team-settings.branding', $team));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'brand_background_color' => '#102030',
            'brand_foreground_color' => '#f0e0d0',
        ]);
    }

    public function test_team_branding_logo_can_be_uploaded_replaced_and_removed()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this
            ->actingAs($user)
            ->patch(route('team-settings.branding.update', $team), [
                'logo' => UploadedFile::fake()->image('logo.png', 80, 80),
                'brand_background_color' => '#010203',
                'brand_foreground_color' => '#fefdfc',
            ])
            ->assertRedirect(route('team-settings.branding', $team));

        $firstLogoPath = $team->fresh()->brand_logo_path;

        $this->assertNotNull($firstLogoPath);
        $this->assertStringStartsWith("teams/{$team->id}/branding/", $firstLogoPath);
        Storage::disk('public')->assertExists($firstLogoPath);

        $this
            ->actingAs($user)
            ->patch(route('team-settings.branding.update', $team), [
                'logo' => UploadedFile::fake()->image('replacement.png', 80, 80),
                'brand_background_color' => '#111111',
                'brand_foreground_color' => '#eeeeee',
            ])
            ->assertRedirect(route('team-settings.branding', $team));

        $secondLogoPath = $team->fresh()->brand_logo_path;

        $this->assertNotNull($secondLogoPath);
        $this->assertNotSame($firstLogoPath, $secondLogoPath);
        Storage::disk('public')->assertMissing($firstLogoPath);
        Storage::disk('public')->assertExists($secondLogoPath);

        $this
            ->actingAs($user)
            ->patch(route('team-settings.branding.update', $team), [
                'remove_logo' => true,
                'brand_background_color' => null,
                'brand_foreground_color' => null,
            ])
            ->assertRedirect(route('team-settings.branding', $team));

        Storage::disk('public')->assertMissing($secondLogoPath);
        $this->assertNull($team->fresh()->brand_logo_path);
    }

    public function test_team_branding_rejects_invalid_colors_and_uploads()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this
            ->actingAs($user)
            ->patch(route('team-settings.branding.update', $team), [
                'logo' => UploadedFile::fake()->create('logo.txt', 1, 'text/plain'),
                'brand_background_color' => '102030',
                'brand_foreground_color' => '#xyzxyz',
            ])
            ->assertSessionHasErrors([
                'logo',
                'brand_background_color',
                'brand_foreground_color',
            ]);
    }

    public function test_team_branding_cannot_be_updated_by_creators()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $response = $this
            ->actingAs($member)
            ->patch(route('team-settings.branding.update', $team), [
                'brand_background_color' => '#102030',
                'brand_foreground_color' => '#f0e0d0',
            ]);

        $response->assertForbidden();
    }

    public function test_team_subdomain_does_not_change_when_name_changes_without_editing_it()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create([
            'name' => 'Original Name',
            'subdomain' => 'original-team',
        ]);

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this
            ->actingAs($user)
            ->patch(route('team-settings.update', $team), [
                'name' => 'Updated Name',
                'subdomain' => 'original-team',
            ])
            ->assertRedirect(route('team-settings.general', $team->fresh()));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Name',
            'subdomain' => 'original-team',
        ]);
    }

    public function test_teams_cannot_be_updated_by_creators()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $response = $this
            ->actingAs($member)
            ->patch(route('team-settings.update', $team), [
                'name' => 'Updated Name',
                'subdomain' => $team->subdomain,
            ]);

        $response->assertForbidden();
    }

    public function test_teams_can_be_deleted_by_owners()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->delete(route('team-settings.destroy', $team), [
                'name' => $team->name,
            ]);

        $response->assertRedirect();

        $this->assertSoftDeleted('teams', [
            'id' => $team->id,
        ]);
    }

    public function test_team_deletion_requires_name_confirmation()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->delete(route('team-settings.destroy', $team), [
                'name' => 'Wrong Name',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleting_current_team_switches_to_alphabetically_first_remaining_team()
    {
        $user = User::factory()->create(['name' => 'Mike']);

        $zuluTeam = Team::factory()->create(['name' => 'Zulu Team']);
        $zuluTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $alphaTeam = Team::factory()->create(['name' => 'Alpha Team']);
        $alphaTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $betaTeam = Team::factory()->create(['name' => 'Beta Team']);
        $betaTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $user->update(['current_team_id' => $zuluTeam->id]);

        $response = $this
            ->actingAs($user)
            ->delete(route('team-settings.destroy', $zuluTeam), [
                'name' => $zuluTeam->name,
            ]);

        $response->assertRedirect();

        $this->assertSoftDeleted('teams', [
            'id' => $zuluTeam->id,
        ]);

        $this->assertEquals($alphaTeam->id, $user->fresh()->current_team_id);
    }

    public function test_deleting_current_team_falls_back_to_personal_team_when_alphabetically_first()
    {
        $user = User::factory()->create();
        $personalTeam = $user->personalTeam();
        $team = Team::factory()->create(['name' => 'Zulu Team']);
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $user->update(['current_team_id' => $team->id]);

        $response = $this
            ->actingAs($user)
            ->delete(route('team-settings.destroy', $team), [
                'name' => $team->name,
            ]);

        $response->assertRedirect();

        $this->assertSoftDeleted('teams', [
            'id' => $team->id,
        ]);

        $this->assertEquals($personalTeam->id, $user->fresh()->current_team_id);
    }

    public function test_deleting_non_current_team_leaves_current_team_unchanged()
    {
        $user = User::factory()->create();
        $personalTeam = $user->personalTeam();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $user->update(['current_team_id' => $personalTeam->id]);

        $response = $this
            ->actingAs($user)
            ->delete(route('team-settings.destroy', $team), [
                'name' => $team->name,
            ]);

        $response->assertRedirect();

        $this->assertSoftDeleted('teams', [
            'id' => $team->id,
        ]);

        $this->assertEquals($personalTeam->id, $user->fresh()->current_team_id);
    }

    public function test_deleting_team_switches_other_affected_users_to_their_personal_team()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $owner->update(['current_team_id' => $team->id]);
        $member->update(['current_team_id' => $team->id]);

        $response = $this
            ->actingAs($owner)
            ->delete(route('team-settings.destroy', $team), [
                'name' => $team->name,
            ]);

        $response->assertRedirect();

        $this->assertEquals($member->personalTeam()->id, $member->fresh()->current_team_id);
    }

    public function test_personal_teams_cannot_be_deleted()
    {
        $user = User::factory()->create();

        $personalTeam = $user->personalTeam();

        $response = $this
            ->actingAs($user)
            ->delete(route('team-settings.destroy', $personalTeam), [
                'name' => $personalTeam->name,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('teams', [
            'id' => $personalTeam->id,
            'deleted_at' => null,
        ]);
    }

    public function test_teams_cannot_be_deleted_by_creators()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Creator->value]);

        $response = $this
            ->actingAs($member)
            ->delete(route('team-settings.destroy', $team), [
                'name' => $team->name,
            ]);

        $response->assertForbidden();
    }

    public function test_users_can_switch_teams()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($user, ['role' => TeamRole::ReadOnly->value]);

        $response = $this
            ->actingAs($user)
            ->post(route('teams.switch', $team));

        $response->assertRedirect();

        $this->assertEquals($team->id, $user->fresh()->current_team_id);
    }

    public function test_legacy_teams_settings_index_redirects_to_global_teams()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/settings/teams');

        $response->assertRedirect(route('teams.index'));
    }

    public function test_legacy_team_settings_edit_redirects_to_team_general_settings()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->get("/settings/teams/{$team->slug}");

        $response->assertRedirect(route('team-settings.general', $team));
    }

    public function test_users_cannot_switch_to_team_they_dont_belong_to()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('teams.switch', $team));

        $response->assertForbidden();
    }

    public function test_guests_cannot_access_teams()
    {
        $response = $this->get(route('teams.index'));

        $response->assertRedirect(route('login'));
    }
}
