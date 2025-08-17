<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'owner' => fake()->userName(),
            'repo' => fake()->slug(2),
            'github_app_id' => (string) fake()->numberBetween(1000,999999),
            'github_app_client_id' => 'lv1_'.fake()->regexify('[A-Za-z0-9]{16}')
        ];
    }
}
