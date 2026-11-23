<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Messages\MailMessage;

class ApplicationCancelled extends GigNotification
{
    public const JOB_CLOSED = 'job_closed';

    public const ACCOUNT_DELETED = 'account_deleted';

    public function __construct(public Application $application, public string $reason = self::JOB_CLOSED) {}

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->job;

        return (new MailMessage)
            ->subject('Application cancelled: '.$job->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->reason === self::ACCOUNT_DELETED
                ? 'The account that posted "'.$job->title.'" was deleted, so your application was cancelled.'
                : 'The job "'.$job->title.'" was closed, so your application was cancelled.')
            ->action('Find more jobs', route('worker.jobs.index'));
    }
}
