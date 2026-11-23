<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum PayType: string
{
    use HasValues;

    case Hourly = 'hourly';
    case Daily = 'daily';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Hourly => 'Per hour',
            self::Daily => 'Per day',
            self::Fixed => 'Fixed (whole job)',
        };
    }

    /**
     * Short suffix shown after an amount, for example RM 15.00 / hour.
     */
    public function suffix(): string
    {
        return match ($this) {
            self::Hourly => '/ hour',
            self::Daily => '/ day',
            self::Fixed => 'fixed',
        };
    }

    /**
     * Label on the Find Jobs pay switch.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Hourly => 'Per hour',
            self::Daily => 'Per day',
            self::Fixed => 'Fixed',
        };
    }

    /**
     * Words after a range, for example "RM 80 to RM 150 per day".
     */
    public function rangeWords(): string
    {
        return match ($this) {
            self::Hourly => 'per hour',
            self::Daily => 'per day',
            self::Fixed => 'for the whole job',
        };
    }

    /**
     * The pay slider's ends are rounded to this, so they read as friendly numbers.
     */
    public function sliderRounding(): int
    {
        return match ($this) {
            self::Hourly => 1,
            self::Daily => 10,
            self::Fixed => 50,
        };
    }

    /**
     * How far one step of the pay slider moves.
     */
    public function sliderStep(): int
    {
        return match ($this) {
            self::Hourly => 1,
            self::Daily => 5,
            self::Fixed => 10,
        };
    }

    /**
     * Slider range to use while there are no open jobs of this type.
     *
     * @return array{0: int, 1: int}
     */
    public function defaultRange(): array
    {
        return match ($this) {
            self::Hourly => [5, 50],
            self::Daily => [50, 300],
            self::Fixed => [50, 1000],
        };
    }
}
