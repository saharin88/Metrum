<?php

namespace App\Filament\Resources\Readings;

use App\Filament\Resources\Readings\Pages\ListReadings;
use App\Filament\Resources\Readings\Schemas\ReadingForm;
use App\Filament\Resources\Readings\Tables\ReadingsTable;
use App\Models\Reading;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReadingResource extends Resource
{
    protected static ?string $model = Reading::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $recordTitleAttribute = 'date';

    public static function getModelLabel(): string
    {
        return __('Reading');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Readings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Readings');
    }

    public static function form(Schema $schema): Schema
    {
        return ReadingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReadingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReadings::route('/'),
        ];
    }
}
