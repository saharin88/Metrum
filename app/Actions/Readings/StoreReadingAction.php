<?php

namespace App\Actions\Readings;

use App\DTOs\Readings\StoreReadingData;
use App\Models\Meter;
use App\Models\Reading;
use App\Validators\Readings\StoreReadingValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreReadingAction
{
    public function __construct(
        private readonly StoreReadingValidator $validator
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(StoreReadingData $data): Reading
    {
        return DB::transaction(function () use ($data): Reading {

            try {
                $meter = Meter::query()
                    ->lockForUpdate()
                    ->findOrFail($data->meterId);
            } catch (ModelNotFoundException) {
                throw ValidationException::withMessages([
                    'meter_id' => __('The meter associated with the reading is invalid.'),
                ]);
            }

            $this->validator->validate($data, $meter);

            try {
                return $meter->readings()->create([
                    'property_id' => $meter->property_id,
                    'zone' => $data->zone,
                    'value' => $data->value,
                    'date' => $data->date->toDateString(),
                    'photo' => $data->photo,
                    'notes' => $data->notes,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'date' => __('A reading for this meter zone already exists on the given date.'),
                ]);
            }
        });
    }
}
