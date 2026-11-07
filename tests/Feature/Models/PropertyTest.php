<?php

use App\Models\Meter;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Str;

describe('Property Model', function (): void {
    it('generates a valid uuid primary key on creation', function (): void {
        $property = Property::factory()->create();

        expect($property->getKey())
            ->toBeString()
            ->and(Str::isUuid($property->getKey()))->toBeTrue();

        $this->assertModelExists($property);
    });

    it('can create a property via mass assignment', function (): void {
        $property = Property::create([
            'name' => 'Sunrise Apartments',
            'address' => '123 Main Street, Kyiv',
        ]);

        expect($property->name)->toBe('Sunrise Apartments')
            ->and($property->address)->toBe('123 Main Street, Kyiv');

        $this->assertModelExists($property);
    });

    it('belongs to multiple users', function (): void {
        $property = Property::factory()->create();
        $user = User::factory()->create();

        $property->users()->attach($user);

        expect($property->users->contains($user))->toBeTrue();
    });

    it('has many meters', function (): void {
        $property = Property::factory()->create();
        $meters = Meter::factory()->count(2)->for($property)->create();

        expect($property->meters)->toHaveCount(2)
            ->and($property->meters->contains($meters->first()))->toBeTrue();
    });
});
