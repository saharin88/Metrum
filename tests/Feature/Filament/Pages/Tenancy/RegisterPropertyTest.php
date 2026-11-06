<?php

use App\Filament\Pages\Tenancy\RegisterProperty;
use App\Models\Property;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

describe('Register Property Tenant Page', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('can render the property registration page', function (): void {
        $this->get(Filament::getTenantRegistrationUrl())->assertSuccessful();
    });

    it('can register a new property tenant', function (): void {
        Livewire::test(RegisterProperty::class)
            ->fillForm([
                'name' => 'Sunrise Apartments',
                'address' => '123 Main Street, Kyiv',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $property = Property::firstWhere('name', 'Sunrise Apartments');

        expect($property)->not->toBeNull()
            ->and($property->address)->toBe('123 Main Street, Kyiv')
            ->and($this->user->fresh()->properties->contains($property))->toBeTrue();
    });
});
