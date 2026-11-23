<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum JobStatus: string
{
    use HasValues;

    case Open = 'open';
    case InProgress = 'in_progress';
    case Hidden = 'hidden';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::Hidden => 'Hidden',
            self::Closed => 'Closed',
        };
    }
}
