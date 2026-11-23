<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStage;
use App\Enums\ApprovalMethod;
use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Notifications\SubmissionApproved;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Automatic approval (spec section 5). A pending submission with no
 * response from the Contract Entity within N days is approved, with
 * approval_method = automatic, and both parties are emailed.
 */
class AutoApproveSubmissions extends Command
{
    protected $signature = 'gigs:auto-approve';

    protected $description = 'Approve pending submissions that had no response within the configured number of days';

    public function handle(): int
    {
        $days = (int) config('gigs.auto_approve_days');

        $submissions = Submission::query()
            ->where('status', SubmissionStatus::Pending->value)
            ->where('submitted_at', '<=', now()->subDays($days))
            ->whereHas('application', fn ($query) => $query->where('stage', ApplicationStage::Accepted->value))
            ->with('application.job.employer', 'application.worker')
            ->get();

        foreach ($submissions as $submission) {
            DB::transaction(function () use ($submission) {
                $submission->status = SubmissionStatus::Approved;
                $submission->save();

                $submission->application->markWorkApproved(ApprovalMethod::Automatic);
            });

            $notification = new SubmissionApproved($submission, ApprovalMethod::Automatic);

            Notifier::send([$submission->application->worker, $submission->application->job->employer], $notification);
        }

        $this->info("Automatically approved {$submissions->count()} submission(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
