<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'assignee_id' => null,
            'title' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['todo', 'in_progress', 'done']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'due_date' => fake()->optional(0.7)->dateTimeBetween('-2 days', '+3 days')->format('c'),
        ];
    }
}
