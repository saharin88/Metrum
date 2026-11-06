<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Property;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class RegisterProperty extends RegisterTenant
{
    public static function getLabel(): string
    {
        return __('Register property');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('address')
                    ->label(__('Address'))
                    ->nullable()
                    ->maxLength(255),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $property = Property::create($data);

        $property->users()->attach(auth()->user());

        return $property;
    }
}
