<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum SubmissionStatus: string
{
    use HasValues;

    case Pending = 'pending';
    case Approved = 'approved';
    case ChangesRequested = 'changes_requested';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Approved => 'Approved',
            self::ChangesRequested => 'Changes requested',
        };
    }
}
