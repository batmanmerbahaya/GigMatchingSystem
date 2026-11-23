<?php

namespace App\Policies;

use App\Enums\ApplicationStage;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * UC14 Approve Submission: approve or request changes on a pending
     * submission while the job is still at Accepted.
     */
    public function respond(User $user, Submission $submission): bool
    {
        $application = $submission->application;

        return $user->id === $application->job->user_id
            && $submission->isPending()
            && $application->isAt(ApplicationStage::Accepted);
    }
}
