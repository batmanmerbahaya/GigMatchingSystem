<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum JobCategory: string
{
    use HasValues;

    case FoodAndBeverage = 'food_and_beverage';
    case Retail = 'retail';
    case Event = 'event';
    case Delivery = 'delivery';
    case Tutoring = 'tutoring';
    case Cleaning = 'cleaning';
    case DesignDigital = 'design_digital';
    case DataEntryAdmin = 'data_entry_admin';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FoodAndBeverage => 'Food & Beverage',
            self::Retail => 'Retail',
            self::Event => 'Event',
            self::Delivery => 'Delivery',
            self::Tutoring => 'Tutoring',
            self::Cleaning => 'Cleaning',
            self::DesignDigital => 'Design & Digital',
            self::DataEntryAdmin => 'Data Entry & Admin',
            self::Other => 'Other',
        };
    }

    /**
     * Jobs done in person (event crew, cleaning, delivery...) have nothing to
     * upload, so the work step is worded as "mark the job as done". Only
     * Design & Digital and Data Entry & Admin hand over files or online work.
     */
    public function isInPerson(): bool
    {
        return ! in_array($this, [self::DesignDigital, self::DataEntryAdmin], true);
    }

    /**
     * Icon name for <x-icon>, so each category is recognisable at a glance.
     */
    public function icon(): string
    {
        return match ($this) {
            self::FoodAndBeverage => 'coffee',
            self::Retail => 'bag',
            self::Event => 'ticket',
            self::Delivery => 'bike',
            self::Tutoring => 'book',
            self::Cleaning => 'sparkles',
            self::DesignDigital => 'palette',
            self::DataEntryAdmin => 'clipboard',
            self::Other => 'briefcase',
        };
    }

    /**
     * Colour tint used for the category tile (CSS class t-{tone}).
     */
    public function tone(): string
    {
        return match ($this) {
            self::FoodAndBeverage => 'food',
            self::Retail => 'retail',
            self::Event => 'event',
            self::Delivery => 'delivery',
            self::Tutoring => 'tutor',
            self::Cleaning => 'clean',
            self::DesignDigital => 'design',
            self::DataEntryAdmin => 'admin',
            self::Other => 'other',
        };
    }
}
