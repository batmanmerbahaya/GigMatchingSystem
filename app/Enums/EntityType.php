<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum EntityType: string
{
    use HasValues;

    case Individual = 'individual';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Business => 'Business',
        };
    }
}
