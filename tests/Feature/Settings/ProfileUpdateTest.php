<?php

namespace Tests\Feature\Settings;

use App\Models\MagicLoginChallenge;
use App\Models\User;
use App\Notifications\Auth\MagicLoginNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_email_change_requires_new_email_confirmation()
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'new@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertNotSame('new@example.com', $user->email);

        Notification::assertSentOnDemand(MagicLoginNotification::class, function (MagicLoginNotification $notification) {
            $this->post(route('profile.email.verify'), [
                'challenge_id' => $notification->challenge->id,
                'code' => $notification->code,
            ])->assertRedirect(route('profile.edit'));

            return true;
        });

        $this->assertSame('new@example.com', $user->refresh()->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull(MagicLoginChallenge::query()->firstOrFail()->consumed_at);
    }

    public function test_profile_update_requires_recent_access_confirmation()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('profile.destroy'));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_delete_account_requires_recent_access_confirmation()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'));

        $response->assertRedirect(route('password.confirm'));

        $this->assertNotNull($user->fresh());
    }
}
