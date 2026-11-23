<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Job tracker stages, spec section 4.
 *
 * Main flow: Applied, Accepted, Completed, Confirmed, Rated.
 * Early endings: Withdrawn, Rejected, Cancelled.
 */
enum ApplicationStage: string
{
    use HasValues;

    case Applied = 'applied';
    case Accepted = 'accepted';
    case Completed = 'completed';
    case Confirmed = 'confirmed';
    case Rated = 'rated';
    case Withdrawn = 'withdrawn';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::Accepted => 'Accepted',
            self::Completed => 'Completed',
            self::Confirmed => 'Payment confirmed',
            self::Rated => 'Rated',
            self::Withdrawn => 'Withdrawn',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * The stages shown as steps on the job tracker, in order.
     *
     * @return array<int, self>
     */
    public static function mainFlow(): array
    {
        return [self::Applied, self::Accepted, self::Completed, self::Confirmed, self::Rated];
    }

    /**
     * Stages in which the accepted application acts as the contract:
     * phone numbers are revealed and work and comments are visible.
     *
     * @return array<int, self>
     */
    public static function contractStages(): array
    {
        return [self::Accepted, self::Completed, self::Confirmed, self::Rated];
    }

    /**
     * Accepted work that is not yet Confirmed. It blocks account deletion.
     *
     * @return array<int, self>
     */
    public static function unfinishedStages(): array
    {
        return [self::Accepted, self::Completed];
    }

    /**
     * Stages that count as a finished, paid job.
     *
     * @return array<int, self>
     */
    public static function finishedStages(): array
    {
        return [self::Confirmed, self::Rated];
    }

    public function isEarlyEnding(): bool
    {
        return in_array($this, [self::Withdrawn, self::Rejected, self::Cancelled], true);
    }

    /**
     * The application column that records when this stage was reached.
     */
    public function timestampColumn(): string
    {
        return match ($this) {
            self::Applied => 'applied_at',
            self::Accepted => 'accepted_at',
            self::Completed => 'completed_at',
            self::Confirmed => 'confirmed_at',
            self::Rated => 'rated_at',
            self::Withdrawn, self::Rejected, self::Cancelled => 'ended_at',
        };
    }
}
