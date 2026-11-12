<?php

use App\Actions\Readings\StoreReadingAction;
use App\DTOs\Readings\StoreReadingData;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Reading;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @param  array<string, mixed>  $overrides
 */
function storeReadingData(Meter $meter, array $overrides = []): StoreReadingData
{
    return StoreReadingData::fromArray([
        'meter_id' => $meter->getKey(),
        'zone' => 1,
        'value' => 100,
        'date' => '2025-03-10',
        ...$overrides,
    ]);
}

it('stores a reading and derives its property from the meter', function (): void {
    $property = Property::factory()->create();
    $meter = Meter::factory()->for($property)->create(['installed_at' => '2024-01-01']);

    $reading = app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'value' => 250,
        'notes' => 'Paid',
    ]));

    expect($reading->exists)->toBeTrue()
        ->and($reading->property_id)->toBe($property->getKey())
        ->and($reading->meter_id)->toBe($meter->getKey())
        ->and($reading->zone)->toBe(1)
        ->and($reading->value)->toBe(250)
        ->and($reading->date->toDateString())->toBe('2025-03-10')
        ->and($reading->notes)->toBe('Paid');

    $this->assertModelExists($reading);
});

it('ignores a property supplied in the data', function (): void {
    $meter = Meter::factory()->create(['installed_at' => '2024-01-01']);
    $foreignProperty = Property::factory()->create();

    $reading = app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'property_id' => $foreignProperty->getKey(),
    ]));

    expect($reading->property_id)->toBe($meter->property_id);
});

it('rejects a reading dated before the meter installation date', function (): void {
    $meter = Meter::factory()->create(['installed_at' => '2025-01-01']);

    expect(fn () => app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'date' => '2024-12-31',
    ])))->toThrow(
        ValidationException::class,
        __('The reading date cannot be earlier than the meter installation date.'),
    );

    $this->assertDatabaseCount('readings', 0);
});

it('rejects a reading dated after the meter removal date', function (): void {
    $meter = Meter::factory()->create([
        'installed_at' => '2025-01-01',
        'removed_at' => '2025-06-30',
        'is_active' => false,
    ]);

    expect(fn () => app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'date' => '2025-07-01',
    ])))->toThrow(
        ValidationException::class,
        __('The reading date cannot be later than the meter removal date.'),
    );

    $this->assertDatabaseCount('readings', 0);
});

it('accepts a reading on the meter installation date', function (): void {
    $meter = Meter::factory()->create(['installed_at' => '2025-01-01']);

    $reading = app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'date' => '2025-01-01',
    ]));

    expect($reading->date->toDateString())->toBe('2025-01-01');
});

it('accepts a reading on the meter removal date', function (): void {
    $meter = Meter::factory()->create([
        'installed_at' => '2025-01-01',
        'removed_at' => '2025-06-30',
        'is_active' => false,
    ]);

    $reading = app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'date' => '2025-06-30',
    ]));

    expect($reading->date->toDateString())->toBe('2025-06-30');
});

it('rejects a reading dated in the future', function (): void {
    $this->travelTo('2025-03-10');

    $meter = Meter::factory()->create(['installed_at' => '2024-01-01']);

    expect(fn () => app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'date' => '2025-03-11',
    ])))->toThrow(
        ValidationException::class,
        __('The reading date cannot be in the future.'),
    );

    $this->assertDatabaseCount('readings', 0);
});

it('rejects a zone outside the meter zone count', function (): void {
    $meter = Meter::factory()->create(['zone_count' => 2, 'installed_at' => '2024-01-01']);

    expect(fn () => app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'zone' => 3,
    ])))->toThrow(
        ValidationException::class,
        __('The reading zone must be within the meter zone count.'),
    );

    $this->assertDatabaseCount('readings', 0);
});

it('rejects a value lower than the previous reading', function (): void {
    $meter = Meter::factory()->create(['installed_at' => '2024-01-01']);

    Reading::factory()->for($meter)->create([
        'zone' => 1,
        'date' => '2025-03-01',
        'value' => 200,
    ]);

    expect(fn () => app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'value' => 199,
    ])))->toThrow(
        ValidationException::class,
        __('The reading value cannot be lower than the previous reading value of :previous on :date.', [
            'previous' => 200,
            'date' => '01.03.2025',
        ]),
    );

    $this->assertDatabaseCount('readings', 1);
});

it('accepts a value equal to the previous reading', function (): void {
    $meter = Meter::factory()->create(['installed_at' => '2024-01-01']);

    Reading::factory()->for($meter)->create([
        'zone' => 1,
        'date' => '2025-03-01',
        'value' => 200,
    ]);

    $reading = app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'value' => 200,
    ]));

    expect($reading->value)->toBe(200);
});

it('rejects a value higher than the next reading', function (): void {
    $meter = Meter::factory()->create(['installed_at' => '2024-01-01']);

    Reading::factory()->for($meter)->create([
        'zone' => 1,
        'date' => '2025-03-20',
        'value' => 300,
    ]);

    expect(fn () => app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'value' => 301,
    ])))->toThrow(
        ValidationException::class,
        __('The reading value cannot be higher than the next reading value of :next on :date.', [
            'next' => 300,
            'date' => '20.03.2025',
        ]),
    );

    $this->assertDatabaseCount('readings', 1);
});

it('rejects a duplicate reading for the same meter zone and date', function (): void {
    $meter = Meter::factory()->create(['installed_at' => '2024-01-01']);

    Reading::factory()->for($meter)->create([
        'zone' => 1,
        'date' => '2025-03-10',
        'value' => 100,
    ]);

    expect(fn () => app(StoreReadingAction::class)->execute(storeReadingData($meter)))
        ->toThrow(
            ValidationException::class,
            __('A reading for this meter zone already exists on the given date.'),
        );

    $this->assertDatabaseCount('readings', 1);
});

it('allows readings for different zones on the same date', function (): void {
    $meter = Meter::factory()->create(['zone_count' => 2, 'installed_at' => '2024-01-01']);

    Reading::factory()->for($meter)->create([
        'zone' => 1,
        'date' => '2025-03-10',
        'value' => 100,
    ]);

    $reading = app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'zone' => 2,
    ]));

    expect($reading->zone)->toBe(2);
});

it('rejects a meter that does not exist', function (): void {
    expect(fn () => app(StoreReadingAction::class)->execute(StoreReadingData::fromArray([
        'meter_id' => (string) Str::uuid(),
        'zone' => 1,
        'value' => 100,
        'date' => '2025-03-10',
    ])))->toThrow(
        ValidationException::class,
        __('The meter associated with the reading is invalid.'),
    );

    $this->assertDatabaseCount('readings', 0);
});

it('rejects a non-integer value', function (): void {
    $meter = Meter::factory()->create(['installed_at' => '2024-01-01']);

    expect(fn () => app(StoreReadingAction::class)->execute(storeReadingData($meter, [
        'value' => 42.5,
    ])))->toThrow(TypeError::class);

    $this->assertDatabaseCount('readings', 0);
});
