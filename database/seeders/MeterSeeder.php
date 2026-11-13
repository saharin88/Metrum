<?php

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Models\Property;
use Illuminate\Database\Seeder;

class MeterSeeder extends Seeder
{
    public const string PROPERTY_NAME = 'Квартира';

    public const string GAS_METER_NAME = 'Газовий лічильник';

    public const string WATER_METER_NAME = 'Лічильник води';

    public const string SINGLE_ZONE_ELECTRICITY_METER_NAME = 'Лічильник електроенергії (однозонний)';

    public const string TWO_ZONE_ELECTRICITY_METER_NAME = 'Лічильник електроенергії (двозонний)';

    public const string ELECTRICITY_METER_REPLACEMENT_DATE = '2026-03-17';

    /**
     * Seed the property and the meters recorded in the notebook.
     */
    public function run(): void
    {
        $property = Property::query()->firstOrCreate([
            'name' => self::PROPERTY_NAME,
        ]);

        foreach ($this->meters() as $attributes) {
            $property->meters()->firstOrCreate(
                ['name' => $attributes['name']],
                $attributes,
            );
        }
    }

    /**
     * The installation date of the original meters is not recorded in the
     * notebook, so the earliest recorded reading date is used instead.
     *
     * @return array<int, array<string, mixed>>
     */
    private function meters(): array
    {
        $firstReadingDate = '2014-12-18';

        return [
            [
                'service_type' => ServiceType::Gas,
                'name' => self::GAS_METER_NAME,
                'zone_count' => 1,
                'serial_number' => null,
                'installed_at' => $firstReadingDate,
                'removed_at' => null,
                'is_active' => true,
            ],
            [
                'service_type' => ServiceType::Water,
                'name' => self::WATER_METER_NAME,
                'zone_count' => 1,
                'serial_number' => null,
                'installed_at' => $firstReadingDate,
                'removed_at' => null,
                'is_active' => true,
            ],
            [
                'service_type' => ServiceType::Electricity,
                'name' => self::SINGLE_ZONE_ELECTRICITY_METER_NAME,
                'zone_count' => 1,
                'serial_number' => null,
                'installed_at' => $firstReadingDate,
                'removed_at' => self::ELECTRICITY_METER_REPLACEMENT_DATE,
                'is_active' => false,
            ],
            [
                'service_type' => ServiceType::Electricity,
                'name' => self::TWO_ZONE_ELECTRICITY_METER_NAME,
                'zone_count' => 2,
                'serial_number' => null,
                'installed_at' => self::ELECTRICITY_METER_REPLACEMENT_DATE,
                'removed_at' => null,
                'is_active' => true,
            ],
        ];
    }
}
