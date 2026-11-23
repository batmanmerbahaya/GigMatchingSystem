<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Submission;
use App\Support\WorkWording;
use Illuminate\Notifications\Messages\MailMessage;

class ChangesRequested extends GigNotification
{
    public function __construct(public Submission $submission, public Comment $comment) {}

    public function toMail(object $notifiable): MailMessage
    {
        $application = $this->submission->application;
        $title = $application->job->title;
        $words = WorkWording::for($application->job);

        return (new MailMessage)
            ->subject($words->choose('Changes requested: ', 'Sent back: ').$title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($words->choose(
                'Changes were requested on your submission for "'.$title.'".',
                'The employer sent back your report for "'.$title.'".',
            ))
            ->line($words->choose('Comment: ', 'What still needs doing: ').$this->comment->body)
            ->line($words->choose(
                'Please upload a new submission with the changes.',
                'When it is done, mark the job as done again.',
            ))
            ->action('Open job tracker', route('applications.show', $application));
    }
}
