<?php

namespace Database\Factories;

use App\Enums\ServiceType;
use App\Models\Meter;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meter>
 */
class MeterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'service_type' => fake()->randomElement(ServiceType::cases()),
            'name' => fake()->randomElement([
                'Main counter',
                'Kitchen counter',
                'Bathroom counter',
            ]),
            'zone_count' => fake()->numberBetween(1, 5),
            'serial_number' => fake()->optional()->bothify('??########'),
            'installed_at' => fake()->dateTimeBetween('-10 years', 'now'),
            'removed_at' => null,
            'is_active' => true,
        ];
    }
}
