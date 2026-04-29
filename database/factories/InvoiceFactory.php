<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 50000, 500000),
            'duration_days' => fake()->randomElement([30, 60, 90, 365]),
            'paid_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Create an invoice with a linked subscription.
     */
    public function withSubscription(int $subscriptionId): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscriptionId,
        ]);
    }
}
