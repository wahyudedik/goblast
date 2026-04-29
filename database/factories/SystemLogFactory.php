<?php

namespace Database\Factories;

use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemLog>
 */
class SystemLogFactory extends Factory
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
            'user_id' => null,
            'type' => fake()->randomElement([
                'device.connected',
                'device.disconnected',
                'quota.exhausted',
                'subscription.activated',
                'subscription.expired',
                'tenant.suspended',
                'gateway.restart',
                'config.updated',
            ]),
            'severity' => fake()->randomElement(['info', 'warning', 'error', 'critical']),
            'message' => fake()->sentence(),
            'context' => null,
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create a log with info severity.
     */
    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'info',
        ]);
    }

    /**
     * Create a log with warning severity.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'warning',
        ]);
    }

    /**
     * Create a log with error severity.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'error',
        ]);
    }

    /**
     * Create a log with critical severity.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }

    /**
     * Create a log with context data.
     */
    public function withContext(): static
    {
        return $this->state(fn (array $attributes) => [
            'context' => [
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'details' => fake()->sentence(),
            ],
        ]);
    }
}
