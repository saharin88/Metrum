<?php

namespace App\Filament\Resources\Readings\Pages;

use App\Actions\Readings\StoreReadingAction;
use App\DTOs\Readings\StoreReadingData;
use App\Filament\Resources\Readings\ReadingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ListReadings extends ListRecords
{
    protected static string $resource = ReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::Medium)
                ->label(__('Submit a meter reading'))
                ->modalHeading(__('Submit a meter reading'))
                ->modalSubmitActionLabel(__('Submit'))
                ->createAnother(false)
                ->using(function (array $data, CreateAction $action, StoreReadingAction $storeReadingAction): Model {
                    try {
                        return $storeReadingAction->execute(StoreReadingData::fromArray($data));
                    } catch (ValidationException $exception) {
                        $dataPath = 'mountedActions.'.($action->getNestingIndex() ?? 0).'.data.';

                        throw ValidationException::withMessages(
                            collect($exception->errors())
                                ->mapWithKeys(fn (array $messages, string $key): array => [
                                    $dataPath.$key => $messages,
                                ])
                                ->all(),
                        );
                    }
                }),
        ];
    }
}
