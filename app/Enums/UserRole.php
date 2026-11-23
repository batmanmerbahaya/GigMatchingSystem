<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum UserRole: string
{
    use HasValues;

    case GigWorker = 'gig_worker';
    case ContractEntity = 'contract_entity';

    public function label(): string
    {
        return match ($this) {
            self::GigWorker => 'Gig Worker',
            self::ContractEntity => 'Contract Entity',
        };
    }
}
