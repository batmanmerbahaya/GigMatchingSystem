<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentReminder extends GigNotification
{
    public function __construct(public Application $application) {}

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->job;

        return (new MailMessage)
            ->subject('Reminder: confirm payment for '.$job->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->application->worker->name.' finished "'.$job->title.'" on '.$this->application->completed_at->format('j M Y').'.')
            ->line('Once you have paid the worker, please confirm the payment so the job can be closed.')
            ->action('Confirm payment', route('applications.show', $this->application));
    }
}
