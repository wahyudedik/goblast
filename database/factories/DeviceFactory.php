<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word().' Device',
            'phone_number' => null,
            'gateway_device_id' => fake()->unique()->uuid(),
            'status' => 'pending',
            'last_seen_at' => null,
            'session_data' => null,
        ];
    }

    /**
     * Create a connected device.
     */
    public function connected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'connected',
            'phone_number' => '62'.fake()->numerify('##########'),
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Create a disconnected device.
     */
    public function disconnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disconnected',
            'phone_number' => '62'.fake()->numerify('##########'),
            'last_seen_at' => now()->subHours(2),
        ]);
    }

    /**
     * Create a device with error status.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
            'phone_number' => '62'.fake()->numerify('##########'),
            'last_seen_at' => now()->subDay(),
        ]);
    }

    /**
     * Create a pending device.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'phone_number' => null,
            'last_seen_at' => null,
        ]);
    }
}
