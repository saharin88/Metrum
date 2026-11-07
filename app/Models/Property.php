<?php

namespace App\Models;

use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'address'])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return HasMany<Meter, $this>
     */
    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }
}
