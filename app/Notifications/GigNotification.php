<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * All notifications are email only (spec section 11). There is no
 * notification table and no in-app notification bell.
 */
abstract class GigNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
