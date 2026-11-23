<?php

namespace App\Notifications;

use App\Enums\ApprovalMethod;
use App\Models\Submission;
use App\Support\WorkWording;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Manual approval emails the worker. Automatic approval by the scheduled
 * task emails both parties.
 */
class SubmissionApproved extends GigNotification
{
    public function __construct(public Submission $submission, public ApprovalMethod $method) {}

    public function toMail(object $notifiable): MailMessage
    {
        $application = $this->submission->application;
        $title = $application->job->title;
        $isWorker = $notifiable->id === $application->user_id;
        $words = WorkWording::for($application->job);

        $mail = (new MailMessage)
            ->subject($words->choose('Submission approved: ', 'Job confirmed as done: ').$title)
            ->greeting('Hello '.$notifiable->name.',');

        if ($this->method === ApprovalMethod::Automatic) {
            $mail->line($words->choose(
                'The submission for "'.$title.'" was approved automatically',
                'The report for "'.$title.'" was confirmed automatically',
            ).' because there was no response within '.config('gigs.auto_approve_days').' days.');
        } else {
            $mail->line($words->choose(
                'Your submission for "'.$title.'" was approved.',
                'The employer confirmed that "'.$title.'" is done.',
            ));
        }

        if ($isWorker) {
            $mail->line('You can now click Finish Job on the job tracker.');
        }

        return $mail->action('Open job tracker', route('applications.show', $application));
    }
}
