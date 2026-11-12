<?php

namespace Database\Factories;

use App\Models\Meter;
use App\Models\Property;
use App\Models\Reading;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Reading>
 */
class ReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meter_id' => Meter::factory(),
            'property_id' => function (array $attributes): PropertyFactory|string {
                $meter = $this->resolveMeter($attributes['meter_id'] ?? null);

                return $meter->property_id ?? Property::factory();
            },
            'zone' => 1,
            'value' => fake()->numberBetween(0, 100000),
            'date' => function (array $attributes): string {
                $meter = $this->resolveMeter($attributes['meter_id'] ?? null);

                return $meter
                    ? $this->generateDateForMeter($meter)
                    : fake()->date();
            },
            'photo' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forMeter(Meter $meter): static
    {
        return $this->state(fn () => [
            'meter_id' => $meter->id,
            'property_id' => $meter->property_id,
            'date' => $this->generateDateForMeter($meter),
        ]);
    }

    protected function resolveMeter(mixed $meter): ?Meter
    {
        if ($meter instanceof Meter) {
            return $meter;
        }

        if (is_scalar($meter) && $meter !== '') {
            static $resolvedMeters = [];

            return $resolvedMeters[$meter] ??= Meter::withoutGlobalScopes()->find($meter);
        }

        return null;
    }

    protected function generateDateForMeter(Meter $meter): string
    {
        $installedAt = Carbon::parse($meter->installed_at)->startOfDay();

        $latestDate = $meter->removed_at
            ? Carbon::parse($meter->removed_at)->startOfDay()
            : now();

        $latestDate = $latestDate->min(now())->max($installedAt);

        return fake()->dateTimeBetween($installedAt, $latestDate)->format('Y-m-d');
    }
}
