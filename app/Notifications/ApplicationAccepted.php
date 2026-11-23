<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Messages\MailMessage;

class ApplicationAccepted extends GigNotification
{
    public function __construct(public Application $application) {}

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->job;

        return (new MailMessage)
            ->subject('You were accepted: '.$job->title)
            ->greeting('Good news, '.$notifiable->name.'!')
            ->line($job->employer->displayName().' accepted your application for "'.$job->title.'".')
            ->line('You can now see each other\'s phone numbers, submit your work and use the comment thread on the job tracker.')
            ->action('Open job tracker', route('applications.show', $this->application));
    }
}
