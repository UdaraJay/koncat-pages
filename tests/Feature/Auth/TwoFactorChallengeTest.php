<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\MagicLoginNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
    }

    public function test_two_factor_challenge_redirects_to_login_when_not_authenticated(): void
    {
        $response = $this->get(route('two-factor.login'));

        $response->assertRedirect(route('login'));
    }

    public function test_two_factor_challenge_can_be_rendered(): void
    {
        Notification::fake();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $this->post(route('login.magic.request'), ['email' => $user->email]);

        Notification::assertSentOnDemand(MagicLoginNotification::class, function (MagicLoginNotification $notification) {
            $this->post(route('login.code.verify'), [
                'challenge_id' => $notification->challenge->id,
                'code' => $notification->code,
            ])->assertRedirect(route('two-factor.login'));

            return true;
        });

        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/two-factor-challenge'),
            );
    }
}
