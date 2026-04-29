<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => 'active',
            'trial_ends_at' => null,
            'suspended_at' => null,
            'suspended_reason' => null,
        ];
    }

    /**
     * Create a tenant in trial status.
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    /**
     * Create a suspended tenant.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspended_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Create an expired tenant.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
        ]);
    }
}
