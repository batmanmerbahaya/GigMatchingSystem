<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ReportReason: string
{
    use HasValues;

    case Scam = 'scam';
    case Spam = 'spam';
    case MisleadingDetails = 'misleading_details';
    case InappropriateContent = 'inappropriate_content';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Scam => 'Scam',
            self::Spam => 'Spam',
            self::MisleadingDetails => 'Misleading details',
            self::InappropriateContent => 'Inappropriate content',
            self::Other => 'Other',
        };
    }
}
