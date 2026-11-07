<?php

namespace App\Filament\Resources\Meters\Tables;

use App\Enums\ServiceType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MetersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_type')
                    ->label(__('Service type'))
                    ->alignCenter()
                    ->badge(),
                TextColumn::make('zone_count')
                    ->label(__('Zone count'))
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('serial_number')
                    ->label(__('Serial number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('installed_at')
                    ->label(__('Installed at'))
                    ->alignCenter()
                    ->date()
                    ->sortable(),
                TextColumn::make('removed_at')
                    ->label(__('Removed at'))
                    ->alignCenter()
                    ->date()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('Is active'))
                    ->alignCenter()
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('service_type')
                    ->label(__('Service type'))
                    ->options(ServiceType::class),
                TernaryFilter::make('is_active')
                    ->label(__('Is active'))
                    ->trueLabel(__('Active'))
                    ->falseLabel(__('Inactive')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
