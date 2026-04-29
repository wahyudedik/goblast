<?php

namespace Database\Factories;

use App\Models\ApiToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiToken>
 */
class ApiTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word().' Token',
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'last_used_at' => null,
            'revoked_at' => null,
        ];
    }

    /**
     * Create an active API token.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => null,
        ]);
    }

    /**
     * Create a revoked API token.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }

    /**
     * Create an API token that has been used.
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    /**
     * Create an API token that has never been used.
     */
    public function unused(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => null,
        ]);
    }
}
