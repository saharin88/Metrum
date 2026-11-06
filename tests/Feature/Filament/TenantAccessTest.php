<?php

use App\Models\Property;
use App\Models\User;
use Filament\Models\Contracts\HasTenants;

describe('Tenant Access and Security', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('implements HasTenants interface', function (): void {
        expect($this->user)->toBeInstanceOf(HasTenants::class);
    });

    it('redirects an authenticated user to their property dashboard', function (): void {
        $property = Property::factory()->create();
        $this->user->properties()->attach($property);

        $this->get('/')->assertRedirect("/{$property->getKey()}");
    });

    it('forbids a user from opening another users property dashboard', function (): void {
        $ownProperty = Property::factory()->create();
        $foreignProperty = Property::factory()->create();

        $this->user->properties()->attach($ownProperty);

        $this->get("/{$ownProperty->getKey()}")->assertSuccessful();
        $this->get("/{$foreignProperty->getKey()}")->assertNotFound();
    });

    it('verifies tenant access rights via canAccessTenant method', function (): void {
        $accessibleProperty = Property::factory()->create();
        $inaccessibleProperty = Property::factory()->create();

        $this->user->properties()->attach($accessibleProperty);

        expect($this->user->canAccessTenant($accessibleProperty))->toBeTrue()
            ->and($this->user->canAccessTenant($inaccessibleProperty))->toBeFalse();
    });
});
