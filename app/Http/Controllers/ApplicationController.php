<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStage;
use App\Enums\JobStatus;
use App\Http\Requests\ApplyJobRequest;
use App\Models\Application;
use App\Models\Job;
use App\Notifications\ApplicationAccepted;
use App\Notifications\ApplicationRejected;
use App\Notifications\JobCompleted;
use App\Notifications\NewApplication;
use App\Notifications\PaymentConfirmed;
use App\Support\Notifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The job tracker (spec section 4). Each action moves an application
 * one stage forward, checked by ApplicationPolicy.
 */
class ApplicationController extends Controller
{
    /**
     * UC05 View Application: the worker's applications still in progress.
     */
    public function index(Request $request): View
    {
        $inProgress = [ApplicationStage::Applied, ApplicationStage::Accepted, ApplicationStage::Completed];

        $applications = $request->user()->applications()
            ->whereIn('stage', $inProgress)
            ->with([
                'job.location',
                'job.employer.contractEntity',
                'submissions' => fn ($query) => $query->orderByDesc('submitted_at')->orderByDesc('id'),
            ])
            ->orderByDesc('applied_at')
            ->paginate(10);

        // Summary tiles: how many applications sit at each stage.
        $stageCounts = $request->user()->applications()
            ->whereIn('stage', $inProgress)
            ->toBase()
            ->selectRaw('stage, count(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        return view('applications.index', [
            'applications' => $applications,
            'stageCounts' => $stageCounts,
        ]);
    }

    /**
     * UC17 View History for a Gig Worker: finished and ended applications,
     * optionally only the completed ones or only those that ended early.
     */
    public function history(Request $request): View
    {
        $finished = ApplicationStage::finishedStages();
        $ended = [ApplicationStage::Withdrawn, ApplicationStage::Rejected, ApplicationStage::Cancelled];
        $show = in_array($request->query('show'), ['done', 'ended'], true) ? $request->query('show') : 'all';

        $applications = $request->user()->applications()
            ->whereIn('stage', match ($show) {
                'done' => $finished,
                'ended' => $ended,
                default => [...$finished, ...$ended],
            })
            ->with('job.location', 'job.employer.contractEntity', 'reviews')
            ->orderByRaw('COALESCE(ended_at, rated_at, confirmed_at, applied_at) DESC')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $user = $request->user();

        return view('applications.history', [
            'applications' => $applications,
            'show' => $show,
            'doneCount' => $user->applications()->whereIn('stage', $finished)->count(),
            'endedCount' => $user->applications()->whereIn('stage', $ended)->count(),
            'averageRating' => $user->averageRating(),
            'reviewCount' => $user->reviewsReceived()->count(),
        ]);
    }

    /**
     * UC03 Apply Job. The job row is locked so an application cannot slip
     * in while the employer is accepting someone else.
     */
    public function store(ApplyJobRequest $request, Job $job): RedirectResponse
    {
        try {
            $application = DB::transaction(function () use ($request, $job) {
                $locked = Job::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();

                if (! $locked->isOpen()) {
                    return null;
                }

                $application = new Application($request->safe()->only('message'));
                $application->job_id = $locked->id;
                $application->user_id = $request->user()->id;
                $application->stage = ApplicationStage::Applied;
                $application->applied_at = now();
                $application->save();

                return $application;
            });
        } catch (UniqueConstraintViolationException) {
            return back()->with('error', 'You have already applied for this job.');
        }

        if ($application === null) {
            return redirect()->route('worker.jobs.index')->with('error', 'Sorry, this job is no longer open.');
        }

        Notifier::send($job->employer, new NewApplication($application));

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Application sent. You will get an email when the employer decides.');
    }

    /**
     * UC18 Track Job Progress. Both parties see the tracker; phone numbers,
     * work and comments appear only from Accepted onwards.
     */
    public function show(Request $request, Application $application): View
    {
        Gate::authorize('view', $application);

        $application->load([
            'job.location',
            'job.employer.contractEntity',
            'worker.gigWorker',
            'submissions' => fn ($query) => $query->orderByDesc('submitted_at')->orderByDesc('id'),
            'submissions.files',
            'comments' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
            'comments.author',
            'reviews.reviewer',
        ]);

        $user = $request->user();

        // Number submissions from the oldest, so "Submission 1" is the first one sent.
        $submissionNumbers = $application->submissions
            ->reverse()
            ->values()
            ->mapWithKeys(fn ($submission, $index) => [$submission->id => $index + 1]);

        return view('applications.show', [
            'application' => $application,
            'job' => $application->job,
            'isWorker' => $user->id === $application->user_id,
            'otherParty' => $application->otherParty($user),
            'submissionNumbers' => $submissionNumbers,
            'myReview' => $application->reviews->firstWhere('reviewer_id', $user->id),
            'theirReview' => $application->reviews->first(fn ($review) => $review->reviewer_id !== $user->id),
        ]);
    }

    /**
     * UC04 Withdraw Application.
     */
    public function withdraw(Application $application): RedirectResponse
    {
        Gate::authorize('withdraw', $application);

        $application->moveTo(ApplicationStage::Withdrawn);

        return redirect()
            ->route('worker.applications.index')
            ->with('status', 'Application withdrawn.');
    }

    /**
     * UC13 accept: reveal phones, open the comment thread, reject the
     * other applicants and set the job to in progress.
     */
    public function accept(Application $application): RedirectResponse
    {
        Gate::authorize('accept', $application);

        $rejected = DB::transaction(function () use ($application) {
            $job = Job::query()->whereKey($application->job_id)->lockForUpdate()->firstOrFail();
            $application->refresh();

            if (! $job->isOpen() || ! $application->isAt(ApplicationStage::Applied)) {
                return null;
            }

            $application->moveTo(ApplicationStage::Accepted);

            $job->status = JobStatus::InProgress;
            $job->save();

            $others = $job->applications()->where('stage', ApplicationStage::Applied->value)->get();

            foreach ($others as $other) {
                $other->moveTo(ApplicationStage::Rejected);
            }

            return $others;
        });

        if ($rejected === null) {
            return back()->with('error', 'This applicant can no longer be accepted.');
        }

        Notifier::send($application->worker, new ApplicationAccepted($application));

        foreach ($rejected as $other) {
            Notifier::send($other->worker, new ApplicationRejected($other, anotherApplicantAccepted: true));
        }

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Applicant accepted. Other applicants were notified.');
    }

    /**
     * UC13 reject.
     */
    public function reject(Application $application): RedirectResponse
    {
        Gate::authorize('reject', $application);

        $application->moveTo(ApplicationStage::Rejected);

        Notifier::send($application->worker, new ApplicationRejected($application));

        return redirect()
            ->route('employer.jobs.show', $application->job_id)
            ->with('status', 'Applicant rejected.');
    }

    /**
     * UC07 Finish Job, allowed once a submission has been approved.
     */
    public function finish(Application $application): RedirectResponse
    {
        Gate::authorize('finish', $application);

        $application->moveTo(ApplicationStage::Completed);

        Notifier::send($application->job->employer, new JobCompleted($application));

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Job marked as finished. The employer will confirm payment.');
    }

    /**
     * UC15 Confirm Payment. Payment happens outside the system; this only
     * records it, closes the job and opens the rating period.
     */
    public function confirmPayment(Application $application): RedirectResponse
    {
        Gate::authorize('confirmPayment', $application);

        DB::transaction(function () use ($application) {
            $application->moveTo(ApplicationStage::Confirmed);

            $job = $application->job;
            $job->status = JobStatus::Closed;
            $job->save();
        });

        Notifier::send($application->worker, new PaymentConfirmed($application));

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Payment confirmed. You can now rate the worker.');
    }
}
