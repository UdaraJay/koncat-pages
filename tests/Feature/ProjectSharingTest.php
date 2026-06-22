<?php

namespace Tests\Feature;

use App\Enums\ProjectSharePermission;
use App\Enums\TeamRole;
use App\Models\MagicLoginChallenge;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Auth\MagicLoginNotification;
use App\Notifications\Projects\ProjectShared;
use App\Services\MatterpipeRuntimeTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_share_with_existing_user(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'Shared@Example.com']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'created_by' => $owner->id,
            'name' => 'Shared App',
            'slug' => 'shared-app',
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.shares.store', $project), [
                'email' => ' shared@example.com ',
                'permission' => ProjectSharePermission::Read->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_shares', [
            'project_id' => $project->id,
            'email' => 'shared@example.com',
            'user_id' => $recipient->id,
            'permission' => ProjectSharePermission::Read->value,
            'shared_by' => $owner->id,
        ]);

        Notification::assertSentOnDemand(ProjectShared::class);

        $this
            ->actingAs($recipient)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('projects', 0)
                ->has('sharedProjects', 1)
                ->where('sharedProjects.0.name', 'Shared App')
                ->where('sharedProjects.0.sharePermission', ProjectSharePermission::Read->value)
                ->where('sharedProjects.0.canArchive', false)
                ->where('sharedProjects.0.canManageShares', false));

        $this
            ->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('projects', 1)
                ->where('projects.0.canManageShares', true)
                ->where('projects.0.shares.0.email', 'shared@example.com'));
    }

    public function test_project_shares_can_be_updated_and_revoked(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'reader@example.com']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
        ]);
        $share = ProjectShare::factory()->forUser($recipient)->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
            'permission' => ProjectSharePermission::Read,
        ]);

        $this
            ->actingAs($owner)
            ->patch(route('projects.shares.update', [$project, $share]), [
                'permission' => ProjectSharePermission::Write->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_shares', [
            'id' => $share->id,
            'permission' => ProjectSharePermission::Write->value,
        ]);

        $this
            ->actingAs($owner)
            ->delete(route('projects.shares.destroy', [$project, $share]))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_shares', [
            'id' => $share->id,
        ]);
    }

    public function test_users_with_inherited_access_are_not_added_as_direct_shares(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create(['email' => 'member@example.com']);
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::ReadOnly->value]);
        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'workspace_id' => null,
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.shares.store', $project), [
                'email' => 'member@example.com',
                'permission' => ProjectSharePermission::Read->value,
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('project_shares', [
            'project_id' => $project->id,
            'email' => 'member@example.com',
        ]);
    }

    public function test_read_and_write_shares_have_different_runtime_permissions(): void
    {
        $hostingDomain = config('matterpipe.hosting_domain');

        $owner = User::factory()->create();
        $reader = User::factory()->create(['email' => 'reader@example.com']);
        $writer = User::factory()->create(['email' => 'writer@example.com']);
        $owner->personalTeam()->update(['subdomain' => 'share-team']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'slug' => 'share-app',
        ]);

        ProjectShare::factory()->forUser($reader)->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
            'permission' => ProjectSharePermission::Read,
        ]);
        ProjectShare::factory()->forUser($writer)->write()->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
        ]);

        $this
            ->actingAs($reader)
            ->getJson("http://share-team.{$hostingDomain}/share-app/__matterpipe/db/posts")
            ->assertOk();

        $this
            ->actingAs($reader)
            ->postJson("http://share-team.{$hostingDomain}/share-app/__matterpipe/db/posts", [
                'data' => ['title' => 'Nope'],
            ])
            ->assertForbidden();

        $this
            ->actingAs($writer)
            ->withToken(app(MatterpipeRuntimeTokens::class)->makeRuntimeToken($project, $writer))
            ->postJson("http://share-team.{$hostingDomain}/share-app/__matterpipe/db/posts", [
                'data' => ['title' => 'Draft'],
            ])
            ->assertCreated();

        $this
            ->actingAs($writer)
            ->post(route('projects.deployments.store', $project))
            ->assertForbidden();

        $this
            ->actingAs($writer)
            ->delete(route('projects.archive', $project))
            ->assertForbidden();
    }

    public function test_read_only_team_members_can_view_but_not_mutate_team_projects(): void
    {
        $hostingDomain = config('matterpipe.hosting_domain');

        $owner = User::factory()->create();
        $reader = User::factory()->create();
        $team = Team::factory()->create(['subdomain' => 'read-only-team']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($reader, ['role' => TeamRole::ReadOnly->value]);
        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'created_by' => $owner->id,
            'slug' => 'team-app',
        ]);

        $this
            ->actingAs($reader)
            ->get(route('projects.show', $project))
            ->assertOk();

        $this
            ->actingAs($reader)
            ->patch(route('projects.update', $project), [
                'name' => 'Changed',
                'description' => null,
            ])
            ->assertForbidden();

        $this
            ->actingAs($reader)
            ->post(route('projects.deployments.store', $project))
            ->assertForbidden();

        $this
            ->actingAs($reader)
            ->post(route('projects.shares.store', $project), [
                'email' => 'shared@example.com',
                'permission' => ProjectSharePermission::Read->value,
            ])
            ->assertForbidden();

        $this
            ->actingAs($reader)
            ->withToken(app(MatterpipeRuntimeTokens::class)->makeRuntimeToken($project, $reader))
            ->postJson("http://read-only-team.{$hostingDomain}/team-app/__matterpipe/db/posts", [
                'data' => ['title' => 'Nope'],
            ])
            ->assertForbidden();

        $this
            ->actingAs($reader)
            ->delete(route('projects.archive', $project))
            ->assertForbidden();
    }

    public function test_creators_can_create_and_manage_team_projects_they_created(): void
    {
        $creator = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($creator, ['role' => TeamRole::Creator->value]);

        $this
            ->actingAs($creator)
            ->post(route('projects.store'), [
                'owner_type' => 'team',
                'team_id' => $team->id,
                'name' => 'Creator App',
                'slug' => 'creator-app',
            ])
            ->assertRedirect(route('dashboard'));

        $project = Project::query()->where('slug', 'creator-app')->firstOrFail();

        $this->assertSame($creator->id, $project->created_by);

        $this
            ->actingAs($creator)
            ->patch(route('projects.update', $project), [
                'name' => 'Updated Creator App',
                'description' => 'Owned by the creator',
            ])
            ->assertRedirect();

        $this
            ->actingAs($creator)
            ->post(route('projects.deployments.store', $project))
            ->assertSessionHasErrors('archive');

        $this
            ->actingAs($creator)
            ->delete(route('projects.archive', $project))
            ->assertRedirect();

        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_creators_can_only_manage_other_projects_when_write_shared(): void
    {
        $hostingDomain = config('matterpipe.hosting_domain');

        $owner = User::factory()->create();
        $creator = User::factory()->create(['email' => 'creator@example.com']);
        $team = Team::factory()->create(['subdomain' => 'creator-team']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($creator, ['role' => TeamRole::Creator->value]);
        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'created_by' => $owner->id,
            'slug' => 'owner-app',
        ]);

        $this
            ->actingAs($creator)
            ->patch(route('projects.update', $project), [
                'name' => 'Nope',
                'description' => null,
            ])
            ->assertForbidden();

        $this
            ->actingAs($creator)
            ->withToken(app(MatterpipeRuntimeTokens::class)->makeRuntimeToken($project, $creator))
            ->postJson("http://creator-team.{$hostingDomain}/owner-app/__matterpipe/db/posts", [
                'data' => ['title' => 'Nope'],
            ])
            ->assertForbidden();

        ProjectShare::factory()->forUser($creator)->write()->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
        ]);

        $this
            ->actingAs($creator)
            ->patch(route('projects.update', $project), [
                'name' => 'Write Shared App',
                'description' => null,
            ])
            ->assertRedirect();

        $this
            ->actingAs($creator)
            ->withToken(app(MatterpipeRuntimeTokens::class)->makeRuntimeToken($project->fresh(), $creator))
            ->postJson("http://creator-team.{$hostingDomain}/owner-app/__matterpipe/db/posts", [
                'data' => ['title' => 'Draft'],
            ])
            ->assertCreated();

        $this
            ->actingAs($creator)
            ->post(route('projects.deployments.store', $project))
            ->assertForbidden();

        $this
            ->actingAs($creator)
            ->post(route('projects.shares.store', $project), [
                'email' => 'new@example.com',
                'permission' => ProjectSharePermission::Read->value,
            ])
            ->assertForbidden();

        $this
            ->actingAs($creator)
            ->delete(route('projects.archive', $project))
            ->assertForbidden();
    }

    public function test_unknown_email_share_is_claimed_after_account_creation(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
            'name' => 'Claimed App',
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.shares.store', $project), [
                'email' => 'new@example.com',
                'permission' => ProjectSharePermission::Write->value,
            ])
            ->assertRedirect();

        $share = ProjectShare::query()->where('email', 'new@example.com')->firstOrFail();

        auth()->logout();

        $this
            ->get(route('login', ['project_share' => $share->code]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/login')
                ->where('projectShare.code', $share->code)
                ->where('projectShare.projectName', 'Claimed App')
                ->where('projectShare.sharerName', $owner->name));

        $this
            ->post(route('login.magic.request'), [
                'email' => 'new@example.com',
                'project_share' => $share->code,
            ])
            ->assertRedirect();

        $challenge = MagicLoginChallenge::query()->firstOrFail();
        $this->assertSame($share->code, $challenge->metadata['project_share']);

        Notification::assertSentOnDemand(MagicLoginNotification::class, function (MagicLoginNotification $notification) {
            $this
                ->post(route('login.code.verify'), [
                    'challenge_id' => $notification->challenge->id,
                    'code' => $notification->code,
                ])
                ->assertRedirect(route('login.complete'));

            return true;
        });

        $this
            ->post(route('login.complete.store'), [
                'name' => 'New Collaborator',
            ])
            ->assertRedirect($project->url());

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        $this->assertDatabaseHas('project_shares', [
            'id' => $share->id,
            'user_id' => $user->id,
            'permission' => ProjectSharePermission::Write->value,
        ]);
    }

    public function test_project_share_email_links_are_bound_to_each_share_recipient(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.shares.store', $project), [
                'email' => 'b@example.com',
                'permission' => ProjectSharePermission::Read->value,
            ])
            ->assertRedirect();

        $this
            ->actingAs($owner)
            ->post(route('projects.shares.store', $project), [
                'email' => 'a@example.com',
                'permission' => ProjectSharePermission::Write->value,
            ])
            ->assertRedirect();

        $bShare = ProjectShare::query()->where('email', 'b@example.com')->firstOrFail();
        $aShare = ProjectShare::query()->where('email', 'a@example.com')->firstOrFail();

        $this->assertNotSame($bShare->code, $aShare->code);
        $this->assertSame(route('login', ['project_share' => $bShare->code]), (new ProjectShared($bShare))->toMail((object) [])->actionUrl);
        $this->assertSame(route('login', ['project_share' => $aShare->code]), (new ProjectShared($aShare))->toMail((object) [])->actionUrl);

        auth()->logout();

        $this
            ->post(route('login.magic.request'), [
                'email' => 'a@example.com',
                'project_share' => $bShare->code,
            ])
            ->assertSessionHasErrors('email');

        $this
            ->post(route('login.magic.request'), [
                'email' => 'a@example.com',
                'project_share' => $aShare->code,
            ])
            ->assertRedirect();

        $challenge = MagicLoginChallenge::query()->latest()->firstOrFail();

        $this->assertSame('a@example.com', $challenge->email);
        $this->assertSame($aShare->code, $challenge->metadata['project_share']);
    }

    public function test_existing_user_project_share_link_redirects_to_shared_project_after_login(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'known@example.com']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
        ]);

        $this
            ->actingAs($owner)
            ->post(route('projects.shares.store', $project), [
                'email' => 'known@example.com',
                'permission' => ProjectSharePermission::Read->value,
            ])
            ->assertRedirect();

        $share = ProjectShare::query()->where('user_id', $recipient->id)->firstOrFail();

        auth()->logout();

        $this
            ->post(route('login.magic.request'), [
                'email' => 'known@example.com',
                'project_share' => $share->code,
            ])
            ->assertRedirect();

        Notification::assertSentOnDemand(MagicLoginNotification::class, function (MagicLoginNotification $notification) use ($project) {
            $this
                ->post(route('login.magic.consume', $notification->challenge), [
                    'token' => $notification->token,
                ])
                ->assertRedirect($project->url());

            return true;
        });
    }

    public function test_notification_links_users_to_claim_link(): void
    {
        $owner = User::factory()->create([
            'name' => 'Project Sender',
            'email' => 'sender@example.com',
        ]);
        $recipient = User::factory()->create(['email' => 'known@example.com']);
        $project = Project::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'hosting_team_id' => $owner->personalTeam()->id,
        ]);
        $knownShare = ProjectShare::factory()->forUser($recipient)->create([
            'project_id' => $project->id,
            'shared_by' => $owner->id,
        ]);
        $unknownShare = ProjectShare::factory()->create([
            'project_id' => $project->id,
            'email' => 'unknown@example.com',
            'shared_by' => $owner->id,
        ]);
        $knownMail = (new ProjectShared($knownShare))->toMail((object) []);
        $unknownMail = (new ProjectShared($unknownShare))->toMail((object) []);

        $this->assertSame(route('login', ['project_share' => $knownShare->code]), $knownMail->actionUrl);
        $this->assertSame(route('login', ['project_share' => $unknownShare->code]), $unknownMail->actionUrl);
        $this->assertSame([['sender@example.com', 'Project Sender']], $knownMail->replyTo);
        $this->assertStringContainsString('Sender email: sender@example.com', implode(' ', $knownMail->introLines));
    }
}
