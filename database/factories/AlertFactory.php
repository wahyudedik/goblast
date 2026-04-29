<?php

namespace Database\Factories;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'type' => fake()->randomElement(['gateway.down', 'quota.90pct', 'jobs.failed_spike', 'subscription.expiring']),
            'severity' => fake()->randomElement(['warning', 'error', 'critical']),
            'message' => fake()->sentence(),
            'context' => null,
            'status' => 'active',
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    /**
     * Create a resolved alert.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }
}
