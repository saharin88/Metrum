<?php

use App\Filament\Pages\Tenancy\EditPropertyProfile;
use App\Models\Property;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

describe('Edit Property Tenant Profile Page', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('can render the property edit profile page with initial data', function (): void {
        $property = Property::factory()->create();
        $this->user->properties()->attach($property);

        Filament::setTenant($property);
        Filament::bootCurrentPanel();

        $this->get(EditPropertyProfile::getUrl(tenant: $property))->assertSuccessful();

        Livewire::test(EditPropertyProfile::class)
            ->assertSchemaStateSet([
                'name' => $property->name,
                'address' => $property->address,
            ]);
    });

    it('can update property profile details', function (): void {
        $property = Property::factory()->create([
            'name' => 'Old Property Name',
            'address' => 'Old Address 123',
        ]);

        $this->user->properties()->attach($property);

        Filament::setTenant($property);
        Filament::bootCurrentPanel();

        Livewire::test(EditPropertyProfile::class)
            ->fillForm([
                'name' => 'Updated Property Name',
                'address' => 'Updated Address 456, Kyiv',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($property->refresh())
            ->name->toBe('Updated Property Name')
            ->address->toBe('Updated Address 456, Kyiv');
    });
});
