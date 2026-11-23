<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalMethod;
use App\Enums\SubmissionStatus;
use App\Http\Requests\RequestChangesRequest;
use App\Http\Requests\SubmitWorkRequest;
use App\Models\Application;
use App\Models\Comment;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Notifications\ChangesRequested;
use App\Notifications\NewSubmission;
use App\Notifications\SubmissionApproved;
use App\Support\Notifier;
use App\Support\WorkWording;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Work submission and approval (spec section 5). Files are stored on the
 * private "local" disk; only their path is saved in the database.
 */
class WorkController extends Controller
{
    /**
     * UC06 Submit Work.
     */
    public function store(SubmitWorkRequest $request, Application $application): RedirectResponse
    {
        $submission = DB::transaction(function () use ($request, $application) {
            // Lock the application so a double click cannot create two pending submissions.
            $locked = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();

            if ($locked->hasPendingSubmission()) {
                return null;
            }

            $submission = new Submission($request->safe()->only('note'));
            $submission->application_id = $locked->id;
            $submission->status = SubmissionStatus::Pending;
            $submission->submitted_at = now();
            $submission->save();

            foreach ($request->file('files', []) as $file) {
                $path = $file->store('submissions/'.$locked->id, 'local');

                if ($path === false) {
                    throw new RuntimeException('Could not store the uploaded file.');
                }

                $submission->files()->create([
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'stored_path' => $path,
                    'file_type' => $file->getMimeType() ?? 'application/octet-stream',
                    'file_size' => $file->getSize(),
                ]);
            }

            return $submission;
        });

        $words = WorkWording::for($application->job);

        if ($submission === null) {
            return back()->with('error', $words->choose(
                'You already have a submission waiting for review.',
                'You already marked this job as done. It is waiting for the employer to confirm it.',
            ));
        }

        Notifier::send($application->job->employer, new NewSubmission($submission));

        return redirect()
            ->route('applications.show', $application)
            ->with('status', $words->choose(
                'Work submitted. The employer has been emailed.',
                'Marked as done. The employer has been emailed to confirm it.',
            ));
    }

    /**
     * UC14 Approve Submission.
     */
    public function approve(Submission $submission): RedirectResponse
    {
        Gate::authorize('respond', $submission);

        DB::transaction(function () use ($submission) {
            $submission->status = SubmissionStatus::Approved;
            $submission->save();

            $submission->application->markWorkApproved(ApprovalMethod::Manual);
        });

        Notifier::send($submission->application->worker, new SubmissionApproved($submission, ApprovalMethod::Manual));

        return redirect()
            ->route('applications.show', $submission->application)
            ->with('status', WorkWording::for($submission->application->job)->choose(
                'Submission approved. The worker can now finish the job.',
                'Confirmed. The worker can now finish the job.',
            ));
    }

    /**
     * UC14 request changes. The comment is saved to the thread, linked to
     * the submission, and the worker uploads a new submission.
     */
    public function requestChanges(RequestChangesRequest $request, Submission $submission): RedirectResponse
    {
        $comment = DB::transaction(function () use ($request, $submission) {
            $submission->status = SubmissionStatus::ChangesRequested;
            $submission->save();

            $comment = new Comment([
                'body' => $request->validated('comment'),
                'submission_id' => $submission->id,
            ]);
            $comment->application_id = $submission->application_id;
            $comment->user_id = $request->user()->id;
            $comment->save();

            return $comment;
        });

        Notifier::send($submission->application->worker, new ChangesRequested($submission, $comment));

        return redirect()
            ->route('applications.show', $submission->application)
            ->with('status', WorkWording::for($submission->application->job)->choose(
                'Changes requested. The worker has been emailed.',
                'Sent back. The worker has been emailed.',
            ));
    }

    /**
     * Download a submitted file. Only the two parties of the job may.
     */
    public function download(SubmissionFile $file): StreamedResponse
    {
        Gate::authorize('view', $file->submission->application);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($file->stored_path), 404);

        return $disk->download($file->stored_path, $file->original_name);
    }
}
