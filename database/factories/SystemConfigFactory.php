<?php

namespace Database\Factories;

use App\Models\SystemConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemConfig>
 */
class SystemConfigFactory extends Factory
{
    protected $model = SystemConfig::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(3),
            'value' => (string) fake()->numberBetween(1, 100),
            'type' => 'integer',
            'description' => fake()->sentence(),
            'updated_by' => null,
        ];
    }

    /**
     * Create a string type config.
     */
    public function string(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'string',
            'value' => fake()->word(),
        ]);
    }

    /**
     * Create a boolean type config.
     */
    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'value' => fake()->randomElement(['true', 'false']),
        ]);
    }

    /**
     * Create a json type config.
     */
    public function json(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'json',
            'value' => json_encode(['key' => 'value']),
        ]);
    }
}
