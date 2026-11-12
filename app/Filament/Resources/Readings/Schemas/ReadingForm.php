<?php

namespace App\Filament\Resources\Readings\Schemas;

use App\Models\Meter;
use Carbon\CarbonInterface;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ReadingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        Select::make('meter_id')
                            ->label(__('Meter'))
                            ->relationship(
                                name: 'meter',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->columnSpan(3)
                            ->afterStateUpdated(function (Set $set): void {
                                $set('zone', 1);
                            }),
                        Select::make('zone')
                            ->label(__('Zone'))
                            ->options(function (Get $get): array {
                                $zones = range(1, self::getZoneCount($get('meter_id')));

                                return array_combine($zones, $zones);
                            })
                            ->default(1)
                            ->columnSpan(1)
                            ->required(),
                    ]),
                TextInput::make('value')
                    ->label(__('Value'))
                    ->integer()
                    ->minValue(0)
                    ->required(),
                DatePicker::make('date')
                    ->label(__('Date'))
                    ->required()
                    ->default(now())
                    ->minDate(function (Get $get): ?CarbonInterface {
                        $installedAt = self::getSelectedMeter($get)?->installed_at;

                        return $installedAt === null ? null : Carbon::parse($installedAt);
                    })
                    ->maxDate(function (Get $get): CarbonInterface {
                        $removedAt = self::getSelectedMeter($get)?->removed_at;

                        if ($removedAt === null) {
                            return now();
                        }

                        $removedAt = Carbon::parse($removedAt);

                        return $removedAt->lessThan(now()) ? $removedAt : now();
                    }),
                FileUpload::make('photo')
                    ->label(__('Photo'))
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->maxSize(5120)
                    ->visibility('private')
                    ->directory(fn (Get $get) => 'readings/'.(filament()->getTenant()?->getKey() ?? 'global').'/'.$get('date'))
                    ->preventFilePathTampering()
                    ->openable()
                    ->downloadable()
                    ->previewable()
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label(__('Notes'))
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Checkbox::make('confirmed')
                    ->label(__('I confirm that the readings are correct.'))
                    ->validationAttribute(__('Confirmation'))
                    ->validationMessages([
                        'accepted' => __('You must confirm that the readings are correct.'),
                    ])
                    ->accepted()
                    ->dehydrated(false)
                    ->visible($schema->getOperation() === 'create')
                    ->columnSpanFull(),
            ]);
    }

    private static function getSelectedMeter(Get $get): ?Meter
    {
        $meterId = $get('meter_id');

        if (blank($meterId)) {
            return null;
        }

        return Meter::query()->whereKey($meterId)->first();
    }

    private static function getZoneCount(?string $meterId): int
    {
        if (blank($meterId)) {
            return 1;
        }

        return max(1, (int) Meter::query()->whereKey($meterId)->value('zone_count'));
    }
}
