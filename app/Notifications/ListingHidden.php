<?php

namespace App\Notifications;

use App\Models\Job;
use Illuminate\Notifications\Messages\MailMessage;

class ListingHidden extends GigNotification
{
    public function __construct(public Job $job) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your listing was hidden: '.$this->job->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your listing "'.$this->job->title.'" was reported by several users and is now hidden from search.')
            ->line('A hidden listing cannot be edited and its applicants cannot be accepted. You can close it and post a new one.')
            ->action('View listing', route('employer.jobs.show', $this->job));
    }
}
