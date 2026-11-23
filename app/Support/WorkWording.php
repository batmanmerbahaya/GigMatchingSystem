<?php

namespace App\Support;

use App\Enums\JobCategory;
use App\Enums\SubmissionStatus;
use App\Models\Job;

/**
 * Words for the work step of the job tracker (UC06, UC07, UC14).
 *
 * Online jobs (Design & Digital, Data Entry & Admin) submit work for approval.
 * In-person jobs such as event crew, cleaning or delivery have nothing to
 * upload, so the same step reads "mark the job as done" and the employer
 * confirms it or sends it back. Only the words differ: the steps, rules and
 * data are the same for every job.
 */
final class WorkWording
{
    private function __construct(public readonly bool $inPerson) {}

    public static function for(Job|JobCategory $jobOrCategory): self
    {
        $category = $jobOrCategory instanceof Job ? $jobOrCategory->category : $jobOrCategory;

        return new self($category->isInPerson());
    }

    /**
     * The online wording or the in-person wording, for one-off sentences.
     */
    public function choose(string $online, string $inPerson): string
    {
        return $this->inPerson ? $inPerson : $online;
    }

    // Worker's side

    public function sectionTitle(): string
    {
        return $this->choose('Work submissions', 'Job done reports');
    }

    public function sectionSubtitle(): string
    {
        return $this->choose(
            'Only you and the other party can see and download these files.',
            'Only you and the other party can see these reports and photos.',
        );
    }

    public function formTitle(): string
    {
        return $this->choose('Submit work', 'Mark the job as done');
    }

    public function noteLabel(): string
    {
        return $this->choose('Note', 'What did you do, and when?');
    }

    public function notePlaceholder(): string
    {
        return $this->choose(
            'What did you do? Anything the employer should check?',
            'e.g. Worked 9 AM to 6 PM at the hall. Packed up the chairs before leaving.',
        );
    }

    public function filesLabel(): string
    {
        return $this->choose('Files', 'Photos or files');
    }

    public function submitButton(): string
    {
        return $this->choose('Submit work', 'Mark as done');
    }

    public function waitingNote(): string
    {
        return $this->choose(
            'Your latest submission is waiting for review. You can submit again if changes are requested.',
            'Your report is waiting for the employer to confirm it. If they send it back, you can mark the job as done again.',
        );
    }

    public function nothingYet(): string
    {
        return $this->choose('No work has been submitted yet.', 'The job has not been marked as done yet.');
    }

    /**
     * "Submission 2" or "Report 2".
     */
    public function item(int|string $number): string
    {
        return $this->choose('Submission', 'Report').' '.$number;
    }

    public function status(SubmissionStatus $status): string
    {
        if (! $this->inPerson) {
            return $status->label();
        }

        return match ($status) {
            SubmissionStatus::Pending => 'Waiting for confirmation',
            SubmissionStatus::Approved => 'Confirmed done',
            SubmissionStatus::ChangesRequested => 'Sent back',
        };
    }

    // Employer's side

    public function approveButton(): string
    {
        return $this->choose('Approve', 'Confirm');
    }

    public function approveQuestion(): string
    {
        return $this->choose(
            'Approve this submission? The worker can then finish the job.',
            'Confirm the job is done? The worker can then finish the job.',
        );
    }

    public function approveYes(): string
    {
        return $this->choose('Yes, approve', "Yes, it's done");
    }

    public function sendBackButton(): string
    {
        return $this->choose('Request changes', 'Send back');
    }

    public function sendBackQuestion(): string
    {
        return $this->choose('What should be changed?', 'What still needs doing?');
    }

    public function sendBackSubmit(): string
    {
        return $this->choose('Send back for changes', 'Send back');
    }

    /**
     * "approved" or "confirmed", as in "approved automatically".
     */
    public function approvedWord(): string
    {
        return $this->choose('approved', 'confirmed');
    }

    // Next-step lines on the tracker and My Applications

    public function finishPrompt(): string
    {
        return $this->choose(
            'Your work was approved. Click Finish Job to tell the employer you are done.',
            'The employer confirmed the job is done. Click Finish Job to finish it.',
        );
    }

    public function workerHint(): string
    {
        return $this->choose(
            'Submit your work below. Finish Job unlocks after a submission is approved.',
            'When you finish, mark the job as done below. Finish Job unlocks after the employer confirms it.',
        );
    }

    public function employerHint(int $days): string
    {
        return $this->choose(
            "The worker is on the job. Review each submission below. A submission with no response for {$days} days is approved automatically.",
            "The worker is on the job. When they mark it as done, confirm it below. A report with no response for {$days} days is confirmed automatically.",
        );
    }

    public function nextStart(): string
    {
        return $this->choose(
            'Submit your work through the job tracker when it is ready.',
            'When you finish, mark the job as done in the job tracker.',
        );
    }

    public function nextWaiting(string $employer): string
    {
        return $this->choose(
            "Your latest work is waiting for {$employer} to review.",
            "Your report is waiting for {$employer} to confirm.",
        );
    }

    public function nextSentBack(string $employer): string
    {
        return $this->choose(
            "{$employer} asked for changes. Check the comments and submit again.",
            "{$employer} sent it back. Check the comments, then mark the job as done again.",
        );
    }

    public function nextApproved(string $employer): string
    {
        return $this->choose(
            'Your work was approved. Open the job tracker and finish the job.',
            "{$employer} confirmed the job is done. Open the job tracker and finish the job.",
        );
    }
}
