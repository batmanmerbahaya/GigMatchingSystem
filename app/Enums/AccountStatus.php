<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum AccountStatus: string
{
    use HasValues;

    case Active = 'active';
    case Deleted = 'deleted';
}
