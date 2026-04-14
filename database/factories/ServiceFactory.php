<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // List of realistic service names for an auto repair shop
        $serviceNames = [
            'Engine Oil Change',
            'Transmission Fluid Change',
            'Full Tune Up',
            'Wheel Alignment & Balancing',
            'Brake Pad Replacement',
            'AC Service',
            'Battery Replacement',
            'Air Filter Replacement',
            'Radiator Flush',
            'Spark Plug Replacement',
            'Injector Cleaning',
            'Engine Overhaul',
            'Cabin Filter Replacement',
            'Electrical System Check'
        ];

        return [
            'name' => $this->faker->randomElement($serviceNames) . ' ' . $this->faker->lexify('Type ?'),
            'description' => $this->faker->optional(0.8)->sentence(6), // 80% chance of having a description
            'base_price' => $this->faker->randomFloat(2, 50000, 2000000), // Price between 50k and 2m
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the service is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
