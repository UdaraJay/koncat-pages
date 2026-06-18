<?php

namespace Tests\Feature\Auth;

use App\Enums\TeamRole;
use App\Models\MagicLoginChallenge;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Auth\MagicLoginNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('auth/login')
            ->missing('canResetPassword'),
        );
    }

    public function test_login_screen_includes_team_invitation_context(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Laravel Team']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this->get(route('login', ['invitation' => $invitation->code]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('auth/login')
            ->where('teamInvitation.code', $invitation->code)
            ->where('teamInvitation.teamName', 'Laravel Team'),
        );
    }

    public function test_users_can_request_a_magic_login_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'USER@example.com']);

        $response = $this->post(route('login.magic.request'), [
            'email' => 'user@example.com',
            'remember' => '1',
        ]);

        $challenge = MagicLoginChallenge::query()->firstOrFail();

        $response->assertRedirect(route('login.magic.check', $challenge));
        $this->assertSame($user->id, $challenge->user_id);
        $this->assertTrue($challenge->remember);
        $this->assertNull($challenge->consumed_at);

        Notification::assertSentOnDemand(MagicLoginNotification::class);
    }

    public function test_users_can_authenticate_with_a_magic_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('login.magic.request'), ['email' => $user->email]);

        Notification::assertSentOnDemand(MagicLoginNotification::class, function (MagicLoginNotification $notification) {
            $response = $this->post(route('login.magic.consume', $notification->challenge), [
                'token' => $notification->token,
            ]);

            $response->assertRedirect(route('dashboard'));

            return true;
        });

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(MagicLoginChallenge::query()->firstOrFail()->consumed_at);
    }

    public function test_users_can_authenticate_with_a_magic_code(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('login.magic.request'), ['email' => $user->email]);

        Notification::assertSentOnDemand(MagicLoginNotification::class, function (MagicLoginNotification $notification) {
            $response = $this->post(route('login.code.verify'), [
                'challenge_id' => $notification->challenge->id,
                'code' => $notification->code,
            ]);

            $response->assertRedirect(route('dashboard'));

            return true;
        });

        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_email_completes_account_after_email_proof(): void
    {
        Notification::fake();

        $this->post(route('login.magic.request'), ['email' => 'new@example.com']);

        Notification::assertSentOnDemand(MagicLoginNotification::class, function (MagicLoginNotification $notification) {
            $this->post(route('login.code.verify'), [
                'challenge_id' => $notification->challenge->id,
                'code' => $notification->code,
            ])->assertRedirect(route('login.complete'));

            return true;
        });

        $this->post(route('login.complete.store'), [
            'name' => 'New User',
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('New User', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->current_team_id);
    }

    public function test_magic_code_rejects_invalid_and_over_attempted_codes(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('login.magic.request'), ['email' => $user->email]);
        $challenge = MagicLoginChallenge::query()->firstOrFail();

        for ($i = 0; $i < MagicLoginChallenge::MAX_ATTEMPTS; $i++) {
            $this->post(route('login.code.verify'), [
                'challenge_id' => $challenge->id,
                'code' => '000000',
            ])->assertSessionHasErrors('code');
        }

        $this->post(route('login.code.verify'), [
            'challenge_id' => $challenge->id,
            'code' => '000000',
        ])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertNotNull($challenge->refresh()->consumed_at);
    }

    public function test_magic_link_get_does_not_consume_the_challenge(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('login.magic.request'), ['email' => $user->email]);

        Notification::assertSentOnDemand(MagicLoginNotification::class, function (MagicLoginNotification $notification) {
            $this->get(route('login.magic.link', [
                'challenge' => $notification->challenge,
                'token' => $notification->token,
            ]))->assertOk();

            return true;
        });

        $this->assertNull(MagicLoginChallenge::query()->firstOrFail()->consumed_at);
        $this->assertGuest();
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Notification::fake();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $this->post(route('login.magic.request'), ['email' => $user->email]);

        Notification::assertSentOnDemand(MagicLoginNotification::class, function (MagicLoginNotification $notification) use ($user) {
            $response = $this->post(route('login.code.verify'), [
                'challenge_id' => $notification->challenge->id,
                'code' => $notification->code,
            ]);

            $response->assertRedirect(route('two-factor.login'));
            $response->assertSessionHas('login.id', $user->id);

            return true;
        });

        $this->assertGuest();
    }

    public function test_invitation_login_rejects_a_different_email(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $this->post(route('login.magic.request'), [
            'email' => 'other@example.com',
            'invitation' => $invitation->code,
        ])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_password_auth_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('register.store'));
        $this->assertFalse(Route::has('password.request'));
        $this->assertFalse(Route::has('password.email'));
        $this->assertFalse(Route::has('password.reset'));
        $this->assertFalse(Route::has('password.update'));
        $this->assertFalse(Route::has('user-password.update'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }
}
