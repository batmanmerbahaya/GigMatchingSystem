<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

class NewComment extends GigNotification
{
    public function __construct(public Comment $comment) {}

    public function toMail(object $notifiable): MailMessage
    {
        $application = $this->comment->application;

        return (new MailMessage)
            ->subject('New comment: '.$application->job->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->comment->author->name.' wrote:')
            ->line(Str::limit($this->comment->body, 500))
            ->action('Reply on the job tracker', route('applications.show', $application));
    }
}
