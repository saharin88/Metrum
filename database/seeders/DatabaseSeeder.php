<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            MeterSeeder::class,
            ReadingSeeder::class,
        ]);

        $property = Property::query()
            ->where('name', MeterSeeder::PROPERTY_NAME)
            ->sole();

        $user->properties()->syncWithoutDetaching([$property->getKey()]);
    }
}
