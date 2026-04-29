<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'slug' => fake()->unique()->slug(),
            'price' => fake()->numberBetween(10000, 100000),
            'message_quota' => fake()->numberBetween(100, 10000),
            'max_devices' => 1,
            'has_reminder' => false,
            'has_api' => false,
            'has_multi_device' => false,
            'description' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Create a Starter plan.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 50000,
            'message_quota' => 100,
            'max_devices' => 1,
            'has_reminder' => false,
            'has_api' => false,
            'has_multi_device' => false,
            'sort_order' => 1,
        ]);
    }

    /**
     * Create a Pro plan.
     */
    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 150000,
            'message_quota' => 1000,
            'max_devices' => 1,
            'has_reminder' => true,
            'has_api' => false,
            'has_multi_device' => false,
            'sort_order' => 2,
        ]);
    }

    /**
     * Create a Business plan.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Business',
            'slug' => 'business',
            'price' => 500000,
            'message_quota' => null,
            'max_devices' => 5,
            'has_reminder' => true,
            'has_api' => true,
            'has_multi_device' => true,
            'sort_order' => 3,
        ]);
    }

    /**
     * Create a Pay-per-message plan.
     */
    public function payPerMessage(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pay-per-message',
            'slug' => 'pay-per-message',
            'price' => 0,
            'message_quota' => null,
            'max_devices' => 1,
            'has_reminder' => false,
            'has_api' => false,
            'has_multi_device' => false,
            'sort_order' => 4,
        ]);
    }

    /**
     * Create an inactive plan.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
