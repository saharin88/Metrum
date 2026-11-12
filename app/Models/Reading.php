<?php

namespace App\Models;

use Database\Factories\ReadingFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'property_id',
    'meter_id',
    'zone',
    'value',
    'date',
    'photo',
    'notes',
])]
class Reading extends Model
{
    /** @use HasFactory<ReadingFactory> */
    use HasFactory, HasUuids;

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new DomainException('A reading cannot be changed once it has been recorded.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'zone' => 'integer',
            'value' => 'integer',
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<Meter, $this>
     */
    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }
}
