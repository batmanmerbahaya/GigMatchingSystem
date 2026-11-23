<?php

namespace App\Policies;

use App\Enums\ApplicationStage;
use App\Models\Application;
use App\Models\User;

/**
 * Job tracker transitions (spec section 4). Every rule checks both who
 * the user is and which stage the application is at, so a request can
 * never skip or repeat a stage.
 */
class ApplicationPolicy
{
    /**
     * UC05 View Application and UC18 Track Job Progress: the two parties.
     */
    public function view(User $user, Application $application): bool
    {
        return $application->isParty($user);
    }

    /**
     * UC04 Withdraw Application: only while still at Applied.
     */
    public function withdraw(User $user, Application $application): bool
    {
        return $this->isWorker($user, $application)
            && $application->isAt(ApplicationStage::Applied);
    }

    /**
     * UC13 accept. A hidden job's applicants cannot be accepted.
     */
    public function accept(User $user, Application $application): bool
    {
        return $this->isOwner($user, $application)
            && $application->isAt(ApplicationStage::Applied)
            && $application->job->isOpen();
    }

    /**
     * UC13 reject.
     */
    public function reject(User $user, Application $application): bool
    {
        return $this->accept($user, $application);
    }

    /**
     * UC06 Submit Work: after acceptance, one pending submission at a
     * time, and no more once a submission has been approved.
     */
    public function submitWork(User $user, Application $application): bool
    {
        return $this->isWorker($user, $application)
            && $application->isAt(ApplicationStage::Accepted)
            && ! $application->hasApprovedWork()
            && ! $application->hasPendingSubmission();
    }

    /**
     * UC07 Finish Job: only after a submission is approved.
     */
    public function finish(User $user, Application $application): bool
    {
        return $this->isWorker($user, $application)
            && $application->isAt(ApplicationStage::Accepted)
            && $application->hasApprovedWork();
    }

    /**
     * UC15 Confirm Payment.
     */
    public function confirmPayment(User $user, Application $application): bool
    {
        return $this->isOwner($user, $application)
            && $application->isAt(ApplicationStage::Completed);
    }

    /**
     * UC19 Post Comment: thread is open from Accepted until Confirmed.
     */
    public function comment(User $user, Application $application): bool
    {
        return $application->isParty($user) && $application->threadIsOpen();
    }

    /**
     * UC20 Give Feedback: after payment is Confirmed, once per party.
     */
    public function review(User $user, Application $application): bool
    {
        return $application->isParty($user)
            && $application->isAt(ApplicationStage::Confirmed, ApplicationStage::Rated)
            && ! $application->reviews()->where('reviewer_id', $user->id)->exists();
    }

    private function isWorker(User $user, Application $application): bool
    {
        return $user->id === $application->user_id;
    }

    private function isOwner(User $user, Application $application): bool
    {
        return $user->id === $application->job->user_id;
    }
}
