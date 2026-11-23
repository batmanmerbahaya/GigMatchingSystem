<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Messages\MailMessage;

class NewApplication extends GigNotification
{
    public function __construct(public Application $application) {}

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->job;

        $mail = (new MailMessage)
            ->subject('New application: '.$job->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->application->worker->name.' applied for your job "'.$job->title.'".');

        if (filled($this->application->message)) {
            $mail->line('Their message: '.$this->application->message);
        }

        return $mail->action('Review applicants', route('employer.jobs.show', $job));
    }
}
