<?php

use App\Enums\ServiceType;
use App\Models\Meter;
use App\Models\Property;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

describe('Meter Model', function (): void {
    it('generates a valid uuid primary key on creation', function (): void {
        $meter = Meter::factory()->create();

        expect($meter->getKey())
            ->toBeString()
            ->and(Str::isUuid($meter->getKey()))->toBeTrue();

        $this->assertModelExists($meter);
    });

    it('belongs to a property', function (): void {
        $property = Property::factory()->create();
        $meter = Meter::factory()->for($property)->create();

        expect($meter->property->is($property))->toBeTrue()
            ->and($property->meters->contains($meter))->toBeTrue();
    });

    it('casts attributes to their expected types', function (): void {
        $meter = Meter::factory()->create([
            'service_type' => ServiceType::Water,
            'zone_count' => '3',
            'installed_at' => '2025-01-15',
            'removed_at' => '2025-06-20',
            'is_active' => 0,
        ]);

        expect($meter->service_type)->toBe(ServiceType::Water)
            ->and($meter->zone_count)->toBeInt()->toBe(3)
            ->and($meter->installed_at)->toBeInstanceOf(Carbon::class)
            ->and($meter->installed_at->toDateString())->toBe('2025-01-15')
            ->and($meter->removed_at)->toBeInstanceOf(Carbon::class)
            ->and($meter->removed_at->toDateString())->toBe('2025-06-20')
            ->and($meter->is_active)->toBeBool()->toBeFalse();
    });

    it('handles nullable date attributes properly', function (): void {
        $meter = Meter::factory()->create([
            'removed_at' => null,
        ]);

        expect($meter->removed_at)->toBeNull();
    });

    it('treats a meter with a removal date as removed', function (): void {
        $meter = Meter::factory()->create([
            'removed_at' => '2025-06-20',
            'is_active' => false,
        ]);

        expect($meter->is_removed)->toBeTrue()
            ->and($meter->is_active)->toBeFalse()
            ->and($meter->canBeActivated())->toBeFalse();
    });

    it('exposes the removal state through the is_removed virtual attribute', function (): void {
        $installedMeter = Meter::factory()->create(['removed_at' => null]);
        $removedMeter = Meter::factory()->create([
            'removed_at' => '2025-06-20',
            'is_active' => false,
        ]);

        expect($installedMeter->is_removed)->toBeFalse()
            ->and($removedMeter->is_removed)->toBeTrue();
    });

    it('prevents activating a removed meter', function (): void {
        $meter = Meter::factory()->create(['is_active' => true]);

        expect(fn () => $meter->update([
            'removed_at' => '2025-06-20',
            'is_active' => true,
        ]))->toThrow(DomainException::class, 'A removed meter cannot be active.')
            ->and($meter->refresh()->is_active)->toBeTrue()
            ->and($meter->removed_at)->toBeNull();

    });

    it('allows an installed meter to be active', function (): void {
        $meter = Meter::factory()->create([
            'removed_at' => null,
            'is_active' => true,
        ]);

        expect($meter->is_active)->toBeTrue()
            ->and($meter->is_removed)->toBeFalse()
            ->and($meter->canBeActivated())->toBeTrue();
    });

    it('allows a meter to be activated again once the removal date is cleared', function (): void {
        $meter = Meter::factory()->create([
            'removed_at' => '2025-06-20',
            'is_active' => false,
        ]);

        $meter->update([
            'removed_at' => null,
            'is_active' => true,
        ]);

        expect($meter->refresh()->is_active)->toBeTrue();
    });

    it('creates valid default meters through its factory', function (): void {
        $meter = Meter::factory()->make();

        expect($meter->service_type)->toBeInstanceOf(ServiceType::class)
            ->and($meter->zone_count)->toBeBetween(1, 5)
            ->and($meter->is_active)->toBeTrue();
    });

    it('supports hot water and heating as service types', function (): void {
        $hotWaterMeter = Meter::factory()->create(['service_type' => ServiceType::HotWater]);
        $heatingMeter = Meter::factory()->create(['service_type' => ServiceType::Heating]);

        expect($hotWaterMeter->service_type)->toBe(ServiceType::HotWater)
            ->and(ServiceType::HotWater->getColor())->toBe('danger')
            ->and($heatingMeter->service_type)->toBe(ServiceType::Heating)
            ->and(ServiceType::Heating->getLabel())->toBe(__('Heating'))
            ->and(ServiceType::Heating->getColor())->toBe('danger');
    });

    it('returns the measurement unit of every service type', function (ServiceType $serviceType, string $unit): void {
        expect($serviceType->getUnit())->toBe($unit);
    })->with([
        'gas' => [ServiceType::Gas, 'м³'],
        'water' => [ServiceType::Water, 'м³'],
        'electricity' => [ServiceType::Electricity, 'кВт·год'],
        'hot water' => [ServiceType::HotWater, 'м³'],
        'heating' => [ServiceType::Heating, 'Гкал'],
    ]);
});
