<?php

namespace App\Validators\Readings;

use App\DTOs\Readings\StoreReadingData;
use App\Models\Meter;
use App\Models\Reading;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StoreReadingValidator
{
    /**
     * Validates the reading against the given meter and returns the validated meter.
     *
     * @throws ValidationException
     */
    public function validate(StoreReadingData $data, Meter $meter): void
    {
        $this->ensureDateIsNotInTheFuture($data);
        $this->ensureDateIsWithinMeterLifetime($data, $meter);
        $this->ensureZoneIsSupportedByMeter($data, $meter);
        $this->ensureReadingDoesNotExist($data, $meter);
        $this->ensureValueIsNotBelowPreviousReading($data, $meter);
        $this->ensureValueIsNotAboveNextReading($data, $meter);
    }

    /**
     * @throws ValidationException
     */
    private function ensureDateIsNotInTheFuture(StoreReadingData $data): void
    {
        if ($data->date->isAfter(today())) {
            throw ValidationException::withMessages([
                'date' => __('The reading date cannot be in the future.'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureDateIsWithinMeterLifetime(StoreReadingData $data, Meter $meter): void
    {
        if ($data->date->lt(Carbon::parse($meter->installed_at))) {
            throw ValidationException::withMessages([
                'date' => __('The reading date cannot be earlier than the meter installation date.'),
            ]);
        }

        if ($meter->removed_at !== null && $data->date->gt(Carbon::parse($meter->removed_at))) {
            throw ValidationException::withMessages([
                'date' => __('The reading date cannot be later than the meter removal date.'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureZoneIsSupportedByMeter(StoreReadingData $data, Meter $meter): void
    {
        if ($data->zone < 1 || $data->zone > (int) $meter->zone_count) {
            throw ValidationException::withMessages([
                'zone' => __('The reading zone must be within the meter zone count.'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureReadingDoesNotExist(StoreReadingData $data, Meter $meter): void
    {
        $readingExists = Reading::query()
            ->whereBelongsTo($meter)
            ->where('zone', $data->zone)
            ->whereDate('date', $data->date)
            ->exists();

        if ($readingExists) {
            throw ValidationException::withMessages([
                'date' => __('A reading for this meter zone already exists on the given date.'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureValueIsNotBelowPreviousReading(StoreReadingData $data, Meter $meter): void
    {
        $previousReading = Reading::query()
            ->whereBelongsTo($meter)
            ->where('zone', $data->zone)
            ->whereDate('date', '<', $data->date)
            ->orderByDesc('date')
            ->first();

        if ($previousReading !== null && $data->value < $previousReading->value) {
            throw ValidationException::withMessages([
                'value' => __('The reading value cannot be lower than the previous reading value of :previous on :date.',
                    [
                        'previous' => $previousReading->value,
                        'date' => Carbon::parse($previousReading->date)->format('d.m.Y'),
                    ]),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureValueIsNotAboveNextReading(StoreReadingData $data, Meter $meter): void
    {
        $nextReading = Reading::query()
            ->whereBelongsTo($meter)
            ->where('zone', $data->zone)
            ->whereDate('date', '>', $data->date)
            ->orderBy('date')
            ->first();

        if ($nextReading !== null && $data->value > $nextReading->value) {
            throw ValidationException::withMessages([
                'value' => __('The reading value cannot be higher than the next reading value of :next on :date.', [
                    'next' => $nextReading->value,
                    'date' => Carbon::parse($nextReading->date)->format('d.m.Y'),
                ]),
            ]);
        }
    }
}
