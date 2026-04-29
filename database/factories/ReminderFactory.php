<?php

namespace Database\Factories;

use App\Models\Reminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word().' Reminder',
            'type' => fake()->randomElement(['spp_due', 'invoice_unpaid', 'booking_tomorrow']),
            'is_active' => true,
            'last_run_at' => null,
        ];
    }

    /**
     * Create an SPP due reminder.
     */
    public function sppDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'spp_due',
            'name' => 'SPP Due Reminder',
        ]);
    }

    /**
     * Create an invoice unpaid reminder.
     */
    public function invoiceUnpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'invoice_unpaid',
            'name' => 'Invoice Unpaid Reminder',
        ]);
    }

    /**
     * Create a booking tomorrow reminder.
     */
    public function bookingTomorrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'booking_tomorrow',
            'name' => 'Booking Tomorrow Reminder',
        ]);
    }

    /**
     * Create an active reminder.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive reminder.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a reminder that has been run.
     */
    public function hasRun(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_run_at' => now()->subDay(),
        ]);
    }
}
