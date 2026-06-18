<?php

namespace Database\Factories;

use App\Enums\ProjectSharePermission;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectShare>
 */
class ProjectShareFactory extends Factory
{
    protected $model = ProjectShare::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'email' => fake()->unique()->safeEmail(),
            'user_id' => null,
            'permission' => ProjectSharePermission::Read,
            'shared_by' => User::factory(),
        ];
    }

    public function write(): static
    {
        return $this->state(fn () => [
            'permission' => ProjectSharePermission::Write,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'email' => strtolower($user->email),
            'user_id' => $user->id,
        ]);
    }
}
