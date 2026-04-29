<?php

namespace Database\Factories;

use App\Models\GatewayInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GatewayInstance>
 */
class GatewayInstanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word().' Gateway',
            'base_url' => fake()->url(),
            'status' => 'active',
            'last_error' => null,
            'last_checked_at' => now(),
        ];
    }

    /**
     * Create an inactive gateway instance.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'last_checked_at' => now()->subHour(),
        ]);
    }

    /**
     * Create a gateway instance with error status.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
            'last_error' => fake()->sentence(),
            'last_checked_at' => now()->subMinutes(5),
        ]);
    }
}
