<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditPropertyProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return __('Property profile');
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
                    ->required()
                    ->nullable()
                    ->maxLength(255),
            ]);
    }
}
