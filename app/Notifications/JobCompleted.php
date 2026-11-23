<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Messages\MailMessage;

class JobCompleted extends GigNotification
{
    public function __construct(public Application $application) {}

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->job;

        return (new MailMessage)
            ->subject('Job completed: '.$job->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->application->worker->name.' marked "'.$job->title.'" as finished.')
            ->line('Please pay the worker directly, then confirm the payment on the job tracker.')
            ->action('Confirm payment', route('applications.show', $this->application));
    }
}
