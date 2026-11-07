<?php

namespace App\Filament\Resources\Meters\Schemas;

use App\Enums\ServiceType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MeterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('service_type')
                    ->label(__('Service type'))
                    ->options(ServiceType::class)
                    ->required(),
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('zone_count')
                    ->label(__('Zone count'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->default(1)
                    ->required(),
                TextInput::make('serial_number')
                    ->label(__('Serial number'))
                    ->maxLength(255),
                DatePicker::make('installed_at')
                    ->label(__('Installed at'))
                    ->required()
                    ->maxDate(now()),
                DatePicker::make('removed_at')
                    ->label(__('Removed at'))
                    ->afterOrEqual('installed_at')
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if (filled($state)) {
                            $set('is_active', false);
                        }
                    }),
                Toggle::make('is_active')
                    ->label(__('Is active'))
                    ->default(true)
                    ->hidden(fn (Get $get): bool => filled($get('removed_at')))
                    ->dehydratedWhenHidden()
                    ->formatStateUsing(fn (mixed $state, Get $get): bool => blank($get('removed_at')) && $state),
            ]);
    }
}
