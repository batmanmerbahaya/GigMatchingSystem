<?php

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['state', 'district'])]
#[WithoutTimestamps]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * For example "Jasin, Melaka". Single-district states show once.
     */
    public function label(): string
    {
        return $this->district === $this->state
            ? $this->state
            : $this->district.', '.$this->state;
    }

    /**
     * All locations grouped by state, for the dropdowns.
     *
     * @return \Illuminate\Support\Collection<string, Collection<int, self>>
     */
    public static function groupedByState(): \Illuminate\Support\Collection
    {
        return static::query()->orderBy('state')->orderBy('district')->get()->groupBy('state');
    }
}
