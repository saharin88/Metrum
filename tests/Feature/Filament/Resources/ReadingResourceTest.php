<?php

use App\Filament\Resources\Readings\Pages\ListReadings;
use App\Filament\Resources\Readings\ReadingResource;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Reading;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->property = Property::factory()->create();

    $this->user->properties()->attach($this->property);

    $this->actingAs($this->user);

    Filament::setTenant($this->property);
    Filament::setCurrentPanel('app');
    Filament::bootCurrentPanel();
});

describe('Reading Resource Listing', function (): void {
    it('lists only readings belonging to the current tenant', function (): void {
        $ownMeter = Meter::factory()->for($this->property)->create();
        $ownReading = Reading::factory()->for($ownMeter)->create();

        $foreignProperty = Property::factory()->create();
        $foreignMeter = Meter::factory()->for($foreignProperty)->createQuietly();
        $foreignReading = Reading::factory()->for($foreignMeter)->createQuietly();

        Livewire::test(ListReadings::class)
            ->assertCanSeeTableRecords([$ownReading])
            ->assertCanNotSeeTableRecords([$foreignReading]);
    });
});

describe('Reading Resource Creation', function (): void {
    it('associates a newly created reading with the current tenant and meter', function (): void {
        $meter = Meter::factory()->for($this->property)->create(['zone_count' => 1, 'installed_at' => '2024-01-01']);

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $meter->getKey(),
                'zone' => 1,
                'value' => 150,
                'date' => '2025-03-10',
                'notes' => 'Paid',
                'confirmed' => true,
            ])
            ->assertHasNoFormErrors();

        $reading = Reading::withoutGlobalScopes()->firstWhere('meter_id', $meter->getKey());

        expect($reading)->toBeInstanceOf(Reading::class)
            ->and($reading->property->is($this->property))->toBeTrue()
            ->and($reading->meter->is($meter))->toBeTrue()
            ->and($reading->value)->toBe(150)
            ->and($reading->notes)->toBe('Paid');
    });

    it('rejects a reading for a meter zone that already has a reading on the same date', function (): void {
        $meter = Meter::factory()->for($this->property)->create(['zone_count' => 1, 'installed_at' => '2024-01-01']);

        Reading::factory()->for($meter)->create(['zone' => 1, 'date' => '2025-03-10']);

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $meter->getKey(),
                'zone' => 1,
                'value' => 42,
                'date' => '2025-03-10',
                'confirmed' => true,
            ])
            ->assertHasFormErrors(['date']);

        expect(Reading::query()->where('meter_id', $meter->getKey())->count())->toBe(1);
    });

    it('rejects a reading dated before the meter installation date', function (): void {
        $meter = Meter::factory()->for($this->property)->create([
            'zone_count' => 1,
            'installed_at' => '2025-01-01',
        ]);

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $meter->getKey(),
                'zone' => 1,
                'value' => 42,
                'date' => '2024-12-31',
                'confirmed' => true,
            ])
            ->assertHasFormErrors(['date']);

        $this->assertDatabaseMissing('readings', ['meter_id' => $meter->getKey()]);
    });

    it('rejects a reading dated after the meter removal date', function (): void {
        $meter = Meter::factory()->for($this->property)->create([
            'zone_count' => 1,
            'installed_at' => '2025-01-01',
            'removed_at' => '2025-06-30',
            'is_active' => false,
        ]);

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $meter->getKey(),
                'zone' => 1,
                'value' => 42,
                'date' => '2025-07-01',
                'confirmed' => true,
            ])
            ->assertHasFormErrors(['date']);

        $this->assertDatabaseMissing('readings', ['meter_id' => $meter->getKey()]);
    });

    it('accepts a reading on the meter removal date', function (): void {
        $meter = Meter::factory()->for($this->property)->create([
            'zone_count' => 1,
            'installed_at' => '2025-01-01',
            'removed_at' => '2025-06-30',
            'is_active' => false,
        ]);

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $meter->getKey(),
                'zone' => 1,
                'value' => 42,
                'date' => '2025-06-30',
                'confirmed' => true,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('readings', ['meter_id' => $meter->getKey(), 'zone' => 1]);
    });

    it('does not expose an edit action for an existing reading', function (): void {
        $meter = Meter::factory()->for($this->property)->create(['zone_count' => 1, 'installed_at' => '2024-01-01']);
        $reading = Reading::factory()->for($meter)->create([
            'zone' => 1,
            'date' => '2025-03-10',
            'value' => 10,
        ]);

        Livewire::test(ListReadings::class)
            ->assertTableActionDoesNotExist('edit', record: $reading);
    });

    it('offers every zone of a multi-zone meter', function (): void {
        $meter = Meter::factory()->for($this->property)->create(['zone_count' => 4]);

        Livewire::test(ListReadings::class)
            ->mountAction(CreateAction::class)
            ->fillForm(['meter_id' => $meter->getKey()])
            ->assertSchemaComponentExists(
                key: 'zone',
                checkComponentUsing: fn ($component): bool => $component->getOptions() === [1 => 1, 2 => 2, 3 => 3, 4 => 4]
            );
    });

    it('allows choosing any zone of a four-zone meter', function (): void {
        $meter = Meter::factory()->for($this->property)->create(['zone_count' => 4, 'installed_at' => '2024-01-01']);

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $meter->getKey(),
                'zone' => 4,
                'value' => 42,
                'date' => '2025-03-10',
                'confirmed' => true,
            ])
            ->assertHasNoFormErrors();

        $reading = Reading::withoutGlobalScopes()->firstWhere('meter_id', $meter->getKey());

        expect($reading->zone)->toBe(4);
    });

    it('rejects a zone that exceeds the meter zone count', function (): void {
        $meter = Meter::factory()->for($this->property)->create(['zone_count' => 4, 'installed_at' => '2024-01-01']);

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $meter->getKey(),
                'zone' => 5,
                'value' => 42,
                'date' => '2025-03-10',
                'confirmed' => true,
            ])
            ->assertHasFormErrors(['zone']);

        $this->assertDatabaseMissing('readings', ['meter_id' => $meter->getKey()]);
    });

    it('rejects a non-integer reading value', function (): void {
        $meter = Meter::factory()->for($this->property)->create(['installed_at' => '2024-01-01']);

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $meter->getKey(),
                'zone' => 1,
                'value' => 42.5,
                'date' => '2025-03-10',
                'confirmed' => true,
            ])
            ->assertHasFormErrors(['value']);

        $this->assertDatabaseMissing('readings', ['meter_id' => $meter->getKey()]);
    });

    it('defaults the zone to the first zone and resets it when the meter changes', function (): void {
        $fourZoneMeter = Meter::factory()->for($this->property)->create(['zone_count' => 4]);
        $singleZoneMeter = Meter::factory()->for($this->property)->create(['zone_count' => 1]);

        Livewire::test(ListReadings::class)
            ->mountAction(CreateAction::class)
            ->fillForm([
                'meter_id' => $fourZoneMeter->getKey(),
                'zone' => 4,
            ])
            ->fillForm(['meter_id' => $singleZoneMeter->getKey()])
            ->assertSchemaStateSet(['zone' => 1]);
    });

    it('stores an uploaded meter photo', function (): void {
        Storage::fake('public');

        $meter = Meter::factory()->for($this->property)->create(['installed_at' => '2024-01-01']);

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $meter->getKey(),
                'zone' => 1,
                'value' => 10,
                'date' => '2025-03-10',
                'photo' => UploadedFile::fake()->image('meter.jpg'),
                'confirmed' => true,
            ])
            ->assertHasNoFormErrors();

        $reading = Reading::withoutGlobalScopes()->firstOrFail();

        expect($reading->photo)->toBeString();
        Storage::disk('local')->assertExists($reading->photo);
    });

    it('requires confirmation that the readings are correct', function (): void {
        $meter = Meter::factory()->for($this->property)->create(['zone_count' => 1, 'installed_at' => '2024-01-01']);

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $meter->getKey(),
                'zone' => 1,
                'value' => 10,
                'date' => '2025-03-10',
            ])
            ->assertHasFormErrors(['confirmed']);

        $this->assertDatabaseMissing('readings', ['meter_id' => $meter->getKey()]);
    });
});

describe('Reading Tenant Security & Access Isolation', function (): void {
    it('does not expose an edit action for a reading from another tenant', function (): void {
        $foreignProperty = Property::factory()->create();
        $foreignMeter = Meter::factory()->for($foreignProperty)->createQuietly();
        $foreignReading = Reading::factory()->for($foreignMeter)->createQuietly();

        Livewire::test(ListReadings::class)
            ->assertTableActionDoesNotExist('edit', record: $foreignReading);
    });

    it('returns 404 when a user opens another tenant reading list', function (): void {
        $foreignProperty = Property::factory()->create();

        $this->get(ReadingResource::getUrl('index', tenant: $foreignProperty))
            ->assertNotFound();
    });

    it('rejects a meter that belongs to another tenant', function (): void {
        $foreignProperty = Property::factory()->create();
        $foreignMeter = Meter::factory()->for($foreignProperty)->createQuietly();

        Livewire::test(ListReadings::class)
            ->callAction(CreateAction::class, data: [
                'meter_id' => $foreignMeter->getKey(),
                'zone' => 1,
                'value' => 10,
                'date' => '2025-03-10',
                'confirmed' => true,
            ])
            ->assertHasFormErrors(['meter_id']);

        $this->assertDatabaseCount('readings', 0);
    });
});
