<?php

namespace App\Support;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Sends email notifications without letting a mail failure undo or
 * break the action that triggered it. Mailtrap's free inbox rejects
 * bursts of mail, for example when accepting one applicant emails all
 * the others. Failures are written to the log instead.
 */
class Notifier
{
    public static function send(mixed $users, Notification $notification): void
    {
        foreach (Collection::wrap($users)->filter() as $user) {
            try {
                $user->notify($notification);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
