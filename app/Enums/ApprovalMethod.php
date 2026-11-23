<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ApprovalMethod: string
{
    use HasValues;

    case Manual = 'manual';
    case Automatic = 'automatic';
}
