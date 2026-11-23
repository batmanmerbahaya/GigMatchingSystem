<?php

namespace App\Notifications;

use App\Models\Submission;
use App\Support\WorkWording;
use Illuminate\Notifications\Messages\MailMessage;

class NewSubmission extends GigNotification
{
    public function __construct(public Submission $submission) {}

    public function toMail(object $notifiable): MailMessage
    {
        $application = $this->submission->application;
        $title = $application->job->title;
        $days = config('gigs.auto_approve_days');
        $words = WorkWording::for($application->job);

        return (new MailMessage)
            ->subject($words->choose('New work submitted: ', 'Job marked as done: ').$title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($words->choose(
                $application->worker->name.' submitted work for "'.$title.'".',
                $application->worker->name.' marked "'.$title.'" as done.',
            ))
            ->line($words->choose(
                'Please approve it or request changes. If there is no response within '.$days.' days, it will be approved automatically.',
                'Please confirm it or send it back. If there is no response within '.$days.' days, it will be confirmed automatically.',
            ))
            ->action($words->choose('Review submission', 'Check the report'), route('applications.show', $application));
    }
}
