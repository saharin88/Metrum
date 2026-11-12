<?php

namespace App\Filament\Resources\Readings\Tables;

use App\Models\Meter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ReadingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meter.name')
                    ->label(__('Meter'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('zone')
                    ->label(__('Zone'))
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('value')
                    ->label(__('Value'))
                    ->alignCenter()
                    ->numeric()
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('Date'))
                    ->date('d.m.Y')
                    ->alignCenter()
                    ->sortable(),
                ImageColumn::make('photo')
                    ->label(__('Photo'))
                    ->disk('public')
                    ->url(fn ($state) => filled($state) ? Storage::url($state) : null, true)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('meter_id')
                    ->label(__('Meter'))
                    ->options(fn (): array => Meter::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
