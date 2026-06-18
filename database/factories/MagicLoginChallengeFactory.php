<?php

namespace Database\Factories;

use App\Models\MagicLoginChallenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MagicLoginChallenge>
 */
class MagicLoginChallengeFactory extends Factory
{
    protected $model = MagicLoginChallenge::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->safeEmail(),
            'user_id' => User::factory(),
            'purpose' => MagicLoginChallenge::PURPOSE_LOGIN,
            'token_hash' => hash_hmac('sha256', Str::random(64), config('app.key')),
            'code_hash' => hash_hmac('sha256', '123456', config('app.key')),
            'remember' => false,
            'metadata' => null,
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(MagicLoginChallenge::EXPIRES_MINUTES),
            'consumed_at' => null,
            'attempts' => 0,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ];
    }
}
