<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentConfirmed extends GigNotification
{
    public function __construct(public Application $application) {}

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->job;

        return (new MailMessage)
            ->subject('Payment confirmed: '.$job->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($job->employer->displayName().' confirmed payment for "'.$job->title.'".')
            ->line('You can now rate this job. Rating is optional.')
            ->action('Give feedback', route('applications.show', $this->application));
    }
}
