<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Messages\MailMessage;

class ApplicationRejected extends GigNotification
{
    public function __construct(public Application $application, public bool $anotherApplicantAccepted = false) {}

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->job;

        return (new MailMessage)
            ->subject('Update on your application: '.$job->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->anotherApplicantAccepted
                ? 'Another applicant was picked for "'.$job->title.'".'
                : 'Your application for "'.$job->title.'" was not accepted.')
            ->action('Find more jobs', route('worker.jobs.index'));
    }
}
