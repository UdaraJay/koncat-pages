<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => User::class,
            'owner_id' => User::factory(),
            'created_by' => null,
            'workspace_id' => null,
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }

    public function inWorkspace(): static
    {
        return $this->state(function () {
            return [
                'workspace_id' => Workspace::factory(),
            ];
        });
    }
}
