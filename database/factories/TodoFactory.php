<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => null,
            'recurrence_parent_id' => null,
            'title' => fake()->sentence(4),
            'memo' => fake()->optional()->paragraph(),
            'due_date' => fake()->optional()->date(),
            'due_time' => null,
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'is_completed' => false,
            'completed_at' => null,
            'recurrence' => 'none',
            'remind_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    public function recurring(string $recurrence = 'daily'): static
    {
        return $this->state(fn (array $attributes) => [
            'recurrence' => $recurrence,
            'due_date' => fake()->date(),
        ]);
    }
}
