<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| Run "php artisan schedule:work" during development, or point Windows
| Task Scheduler at "php artisan schedule:run" every minute.
*/

// Approve submissions that had no response within gigs.auto_approve_days.
Schedule::command('gigs:auto-approve')->hourly();

// Remind employers to confirm payment gigs.payment_reminder_days after the
// worker finishes. Runs once a day so each job gets one reminder.
Schedule::command('gigs:payment-reminders')->dailyAt('09:00');
