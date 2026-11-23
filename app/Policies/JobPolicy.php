<?php

namespace App\Policies;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;

/**
 * Who may do what with a job listing. Each check names the use case it
 * guards. Route middleware already enforces the role and, for posting
 * and applying, a verified email.
 */
class JobPolicy
{
    /**
     * UC02 View Job Details: open listings, or any job the worker applied to.
     */
    public function view(User $user, Job $job): bool
    {
        if (! $user->isGigWorker()) {
            return false;
        }

        return $job->isOpen() || $this->hasApplied($user, $job);
    }

    /**
     * UC09 Post Job.
     */
    public function create(User $user): bool
    {
        return $user->isContractEntity();
    }

    /**
     * UC10 View Posted Job and UC13 Verify Applicants: owner only.
     */
    public function manage(User $user, Job $job): bool
    {
        return $user->id === $job->user_id;
    }

    /**
     * UC11 Edit Posted Job: only until the first application arrives.
     */
    public function update(User $user, Job $job): bool
    {
        return $this->manage($user, $job)
            && $job->isOpen()
            && ! $job->applications()->exists();
    }

    /**
     * UC12 Close Job: open or hidden listings. An in-progress job ends
     * through Confirm Payment instead, because accepted work cannot be
     * cancelled.
     */
    public function close(User $user, Job $job): bool
    {
        return $this->manage($user, $job)
            && in_array($job->status, [JobStatus::Open, JobStatus::Hidden], true);
    }

    /**
     * UC03 Apply Job: open job, not applied before.
     */
    public function apply(User $user, Job $job): bool
    {
        return $user->isGigWorker()
            && $job->isOpen()
            && ! $this->hasApplied($user, $job);
    }

    /**
     * UC08 Report Listing: once per user, only while the listing is open.
     */
    public function report(User $user, Job $job): bool
    {
        return $user->isGigWorker()
            && $job->isOpen()
            && ! $job->reports()->where('user_id', $user->id)->exists();
    }

    private function hasApplied(User $user, Job $job): bool
    {
        return $job->applications()->where('user_id', $user->id)->exists();
    }
}
