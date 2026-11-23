<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStage;
use App\Models\Application;
use App\Notifications\PaymentReminder;
use App\Support\Notifier;
use Illuminate\Console\Command;

/**
 * Payment confirmation reminder (spec section 11). Emails the Contract
 * Entity once, on the day that is gigs.payment_reminder_days after the
 * worker clicked Finish Job, if payment is still not confirmed. Matching
 * on a single day means no extra column is needed to remember that a
 * reminder was sent, as long as the task runs once a day.
 */
class SendPaymentReminders extends Command
{
    protected $signature = 'gigs:payment-reminders';

    protected $description = 'Remind employers to confirm payment for jobs finished a set number of days ago';

    public function handle(): int
    {
        $days = (int) config('gigs.payment_reminder_days');
        $day = now()->subDays($days);

        $applications = Application::query()
            ->where('stage', ApplicationStage::Completed->value)
            ->whereBetween('completed_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->with('job.employer', 'worker')
            ->get();

        foreach ($applications as $application) {
            Notifier::send($application->job->employer, new PaymentReminder($application));
        }

        $this->info("Sent {$applications->count()} payment reminder(s).");

        return self::SUCCESS;
    }
}
