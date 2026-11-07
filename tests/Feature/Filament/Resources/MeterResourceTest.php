<?php

use App\Enums\ServiceType;
use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Meters\Pages\CreateMeter;
use App\Filament\Resources\Meters\Pages\EditMeter;
use App\Filament\Resources\Meters\Pages\ListMeters;
use App\Models\Meter;
use App\Models\Property;
use App\Models\User;
use Filament\Facades\Filament;
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

describe('Meter Resource Listing', function (): void {
    it('lists only meters belonging to the current tenant', function (): void {
        $ownMeter = Meter::factory()->for($this->property)->create();
        $foreignProperty = Property::factory()->create();
        $foreignMeter = Meter::factory()->for($foreignProperty)->createQuietly();

        Livewire::test(ListMeters::class)
            ->assertCanSeeTableRecords([$ownMeter])
            ->assertCanNotSeeTableRecords([$foreignMeter]);
    });
});

describe('Meter Resource Creation & Editing', function (): void {
    it('associates a newly created meter with the current tenant', function (): void {
        Livewire::test(CreateMeter::class)
            ->fillForm([
                'service_type' => ServiceType::Electricity->value,
                'name' => 'Main counter',
                'zone_count' => 2,
                'serial_number' => 'EL-000123',
                'installed_at' => '2025-01-15',
                'removed_at' => null,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $meter = Meter::withoutGlobalScopes()->firstWhere('name', 'Main counter');

        expect($meter)->toBeInstanceOf(Meter::class)
            ->and($meter->property->is($this->property))->toBeTrue();
    });

    it('can update an existing meter', function (): void {
        $meter = Meter::factory()->for($this->property)->create([
            'name' => 'Old Meter Name',
        ]);

        Livewire::test(EditMeter::class, ['record' => $meter->getKey()])
            ->fillForm([
                'name' => 'Updated Meter Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($meter->refresh()->name)->toBe('Updated Meter Name');
    });

    it('rejects a zone type outside the allowed range', function (): void {
        Livewire::test(CreateMeter::class)
            ->fillForm([
                'service_type' => ServiceType::Gas->value,
                'name' => 'Gas meter',
                'zone_count' => 6,
                'installed_at' => '2025-01-15',
            ])
            ->call('create')
            ->assertHasFormErrors(['zone_count']);

        $this->assertDatabaseMissing('meters', ['name' => 'Gas meter']);
    });

    it('hides and deactivates the active toggle when a removal date is set', function (): void {
        Livewire::test(CreateMeter::class)
            ->fillForm([
                'service_type' => ServiceType::Water->value,
                'name' => 'Removed meter',
                'zone_count' => 1,
                'installed_at' => '2025-01-15',
                'is_active' => true,
            ])
            ->fillForm([
                'removed_at' => '2025-06-20',
            ])
            ->assertSchemaStateSet(['is_active' => false])
            ->assertFormFieldHidden('is_active')
            ->call('create')
            ->assertHasNoFormErrors();

        $meter = Meter::withoutGlobalScopes()->firstWhere('name', 'Removed meter');

        expect($meter->is_active)->toBeFalse()
            ->and($meter->is_removed)->toBeTrue();
    });

    it('keeps the active toggle visible when there is no removal date', function (): void {
        Livewire::test(CreateMeter::class)
            ->assertFormFieldVisible('is_active');
    });

    it('hides the active toggle when editing a meter with a removal date', function (): void {
        $meter = Meter::factory()->for($this->property)->create([
            'removed_at' => '2025-06-20',
            'is_active' => false,
        ]);

        Livewire::test(EditMeter::class, ['record' => $meter->getKey()])
            ->assertFormFieldHidden('is_active')
            ->assertSchemaStateSet(['is_active' => false]);
    });

    it('automatically unchecks is_active when removed_at date is selected in form', function (): void {
        $meter = Meter::factory()->for($this->property)->create([
            'removed_at' => null,
            'is_active' => true,
        ]);

        Livewire::test(EditMeter::class, ['record' => $meter->getKey()])
            ->fillForm([
                'removed_at' => '2025-06-20',
            ])
            ->assertSchemaStateSet(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($meter->refresh()->is_active)->toBeFalse();
    });

    it('fails to save if user sets removal date but keeps meter active', function (): void {
        $meter = Meter::factory()->for($this->property)->create([
            'removed_at' => null,
            'is_active' => true,
        ]);

        expect(function () use ($meter): void {
            Livewire::test(EditMeter::class, ['record' => $meter->getKey()])
                ->fillForm([
                    'removed_at' => '2025-06-20',
                    'is_active' => true,
                ])
                ->call('save');
        })->toThrow(DomainException::class);
    });
});

describe('Tenant Security & Access Isolation', function (): void {
    it('returns 404 when editing a meter from another tenant', function (): void {
        $foreignProperty = Property::factory()->create();
        $foreignMeter = Meter::factory()->for($foreignProperty)->createQuietly();

        $this->get(MeterResource::getUrl('edit', ['record' => $foreignMeter], tenant: $this->property))
            ->assertNotFound();
    });

    it('returns 404 when a user opens another tenant meter list', function (): void {
        $foreignProperty = Property::factory()->create();
        Meter::factory()->for($foreignProperty)->createQuietly();

        $this->get(MeterResource::getUrl('index', tenant: $foreignProperty))
            ->assertNotFound();
    });

    it('returns 404 when a user without tenant access opens a meter list', function (): void {
        $property = Property::factory()->create();
        Meter::factory()->for($property)->createQuietly();

        $this->actingAs(User::factory()->create());

        $this->get(MeterResource::getUrl('index', tenant: $property))
            ->assertNotFound();
    });
});
