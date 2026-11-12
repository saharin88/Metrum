<?php

use App\Models\Meter;
use App\Models\Property;
use App\Models\Reading;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

describe('Reading Model', function (): void {
    it('generates a valid uuid primary key on creation', function (): void {
        $reading = Reading::factory()->create();

        expect($reading->getKey())
            ->toBeString()
            ->and(Str::isUuid($reading->getKey()))->toBeTrue();

        $this->assertModelExists($reading);
    });

    it('belongs to a meter and a property', function (): void {
        $property = Property::factory()->create();
        $meter = Meter::factory()->for($property)->create();
        $reading = Reading::factory()->for($meter)->create();

        expect($reading->meter->is($meter))->toBeTrue()
            ->and($reading->property->is($property))->toBeTrue()
            ->and($meter->readings->contains($reading))->toBeTrue()
            ->and($property->readings->contains($reading))->toBeTrue();
    });

    it('casts attributes to their expected types', function (): void {
        $meter = Meter::factory()->create(['zone_count' => 2, 'installed_at' => '2024-01-01']);

        $reading = Reading::factory()->for($meter)->create([
            'zone' => '2',
            'value' => '123',
            'date' => '2025-01-15',
        ]);

        expect($reading->zone)->toBeInt()->toBe(2)
            ->and($reading->value)->toBeInt()->toBe(123)
            ->and($reading->date)->toBeInstanceOf(Carbon::class)
            ->and($reading->date->toDateString())->toBe('2025-01-15');
    });

    it('defaults the zone to the first zone', function (): void {
        $attributes = Arr::except(Reading::factory()->raw(), 'zone');
        $reading = Reading::create($attributes);
        $reading->refresh();

        expect($reading->zone)->toBe(1);
    });

    it('allows choosing any zone of a multi-zone meter', function (): void {
        $meter = Meter::factory()->create(['zone_count' => 4]);

        foreach ([1, 2, 3, 4] as $zone) {
            $reading = Reading::factory()->for($meter)->create(['zone' => $zone]);

            expect($reading->zone)->toBe($zone)
                ->and($reading->meter->is($meter))->toBeTrue();
        }
    });

    it('allows readings for different zones of the same meter on the same date', function (): void {
        $meter = Meter::factory()->create(['zone_count' => 2, 'installed_at' => '2024-01-01']);

        Reading::factory()->for($meter)->create(['zone' => 1, 'date' => '2025-03-10']);
        $secondReading = Reading::factory()->for($meter)->create(['zone' => 2, 'date' => '2025-03-10']);

        expect($secondReading->exists)->toBeTrue();
    });

    it('allows readings for different meters on the same date', function (): void {
        $property = Property::factory()->create();
        $firstMeter = Meter::factory()->for($property)->create(['installed_at' => '2024-01-01']);
        $secondMeter = Meter::factory()->for($property)->create(['installed_at' => '2024-01-01']);

        Reading::factory()->for($firstMeter)->create(['date' => '2025-03-10']);
        $secondReading = Reading::factory()->for($secondMeter)->create(['date' => '2025-03-10']);

        expect($secondReading->exists)->toBeTrue();
    });

    it('prevents changing a reading after it has been recorded', function (): void {
        $meter = Meter::factory()->create(['installed_at' => '2024-01-01']);
        $reading = Reading::factory()->for($meter)->create(['date' => '2025-03-10', 'value' => 10]);

        expect(fn () => $reading->update(['value' => 20]))
            ->toThrow(DomainException::class, 'A reading cannot be changed once it has been recorded.');

        expect($reading->refresh()->value)->toBe(10);
    });

    it('enforces the unique reading per meter zone and date in the database', function (): void {
        $meter = Meter::factory()->create(['installed_at' => '2024-01-01']);
        $reading = Reading::factory()->for($meter)->create(['zone' => 1, 'date' => '2025-03-10']);

        DB::table('readings')->insert([
            'id' => (string) Str::uuid(),
            'property_id' => $reading->property_id,
            'meter_id' => $reading->meter_id,
            'zone' => 1,
            'value' => 1,
            'date' => '2025-03-10 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->throws(QueryException::class);

    it('handles nullable photo and notes attributes properly', function (): void {
        $reading = Reading::factory()->create([
            'photo' => null,
            'notes' => null,
        ]);

        expect($reading->photo)->toBeNull()
            ->and($reading->notes)->toBeNull();
    });

    it('creates valid default readings through its factory', function (): void {
        $reading = Reading::factory()->make();

        expect($reading->property_id)->not->toBeNull()
            ->and($reading->meter)->toBeInstanceOf(Meter::class)
            ->and($reading->zone)->toBe(1)
            ->and($reading->date)->toBeInstanceOf(Carbon::class);
    });
});
