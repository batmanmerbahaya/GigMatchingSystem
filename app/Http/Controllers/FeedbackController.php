<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStage;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Application;
use App\Models\Review;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * UC20 Give Feedback. Optional, after payment is Confirmed, one rating
 * per party per job. The first rating moves the tracker to Rated.
 */
class FeedbackController extends Controller
{
    public function store(StoreReviewRequest $request, Application $application): RedirectResponse
    {
        $user = $request->user();

        try {
            DB::transaction(function () use ($request, $application, $user) {
                $review = new Review($request->validated());
                $review->application_id = $application->id;
                $review->reviewer_id = $user->id;
                $review->reviewee_id = $application->otherParty($user)->id;
                $review->save();

                if ($application->isAt(ApplicationStage::Confirmed)) {
                    $application->moveTo(ApplicationStage::Rated);
                }
            });
        } catch (UniqueConstraintViolationException) {
            return back()->with('error', 'You have already rated this job.');
        }

        // Rating from the worker's History page returns there; otherwise to the tracker.
        if ($request->input('return') === 'history' && $user->isGigWorker()) {
            return redirect()
                ->route('worker.history')
                ->with('status', 'Thank you for your feedback.');
        }

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Thank you for your feedback.');
    }
}
