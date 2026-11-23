<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Http\Requests\ReportListingRequest;
use App\Models\Job;
use App\Models\Report;
use App\Notifications\ListingHidden;
use App\Support\Notifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * UC08 Report Listing, moderation without an admin (spec section 8).
 * When the number of distinct reporters reaches gigs.report_threshold,
 * the job becomes hidden and the employer is emailed.
 */
class ReportController extends Controller
{
    public function store(ReportListingRequest $request, Job $job): RedirectResponse
    {
        try {
            $hidden = DB::transaction(function () use ($request, $job) {
                $locked = Job::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();

                $report = new Report($request->validated());
                $report->job_id = $locked->id;
                $report->user_id = $request->user()->id;
                $report->save();

                // One report per user (unique key), so this counts distinct reporters.
                if ($locked->isOpen() && $locked->reports()->count() >= config('gigs.report_threshold')) {
                    $locked->status = JobStatus::Hidden;
                    $locked->save();

                    return $locked;
                }

                return null;
            });
        } catch (UniqueConstraintViolationException) {
            return back()->with('error', 'You have already reported this listing.');
        }

        if ($hidden !== null) {
            Notifier::send($hidden->employer, new ListingHidden($hidden));

            return redirect()
                ->route('worker.jobs.index')
                ->with('status', 'Thank you. The listing has been hidden after several reports.');
        }

        return back()->with('status', 'Thank you. Your report has been recorded.');
    }
}
