<?php

namespace App\Models;

use App\Enums\ServiceType;
use Database\Factories\MeterFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'property_id',
    'service_type',
    'name',
    'zone_count',
    'serial_number',
    'installed_at',
    'removed_at',
    'is_active',
])]
class Meter extends Model
{
    /** @use HasFactory<MeterFactory> */
    use HasFactory, HasUuids;

    protected static function booted(): void
    {
        static::saving(function (Meter $meter): void {
            if ($meter->is_removed && $meter->is_active) {
                throw new DomainException('A removed meter cannot be active.');
            }
        });
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isRemoved(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->removed_at !== null,
        );
    }

    public function canBeActivated(): bool
    {
        return ! $this->is_removed;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'zone_count' => 'integer',
            'installed_at' => 'date',
            'removed_at' => 'date',
            'is_active' => 'boolean',
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
     * @return HasMany<Reading, $this>
     */
    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }
}
