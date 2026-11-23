<?php

namespace App\Enums\Concerns;

trait HasValues
{
    /**
     * The raw database values of every case, for validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
