<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\ApplicationStage;
use App\Enums\EntityType;
use App\Enums\JobStatus;
use App\Http\Requests\ProfileUpdateRequest;
use App\Notifications\ApplicationCancelled;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    /**
     * UC16 Manage Profile.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load('gigWorker', 'contractEntity');

        return view('profile.edit', [
            'user' => $user,
            'entityTypes' => EntityType::cases(),
            'averageRating' => $user->averageRating(),
            'completedJobs' => $user->completedJobCount(),
            'hasUnfinishedWork' => $user->hasUnfinishedWork(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
        ]);

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        DB::transaction(function () use ($user, $data) {
            $user->save();

            if ($user->isGigWorker()) {
                $user->gigWorker()->updateOrCreate([], ['bio' => $data['bio'] ?? null]);
            } else {
                $isBusiness = $data['entity_type'] === EntityType::Business->value;

                $user->contractEntity()->updateOrCreate([], [
                    'entity_type' => $data['entity_type'],
                    'organisation_name' => $isBusiness ? $data['organisation_name'] : null,
                ]);
            }
        });

        if ($emailChanged) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * UC21 Delete Account: anonymise and keep (spec section 10).
     *
     * Blocked while the user has accepted work that is not yet Confirmed.
     * Open jobs are closed and pending applications cancelled. Jobs,
     * applications, submissions, comments and reviews are kept so the
     * other party keeps their records.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->hasUnfinishedWork()) {
            return back()->withErrors([
                'password' => 'You cannot delete your account while you have accepted work that is not yet confirmed.',
            ], 'userDeletion');
        }

        $cancelledByJobClosing = DB::transaction(function () use ($user) {
            $cancelled = collect();

            // A Contract Entity's open and hidden jobs are closed.
            $jobs = $user->jobs()->whereIn('status', [JobStatus::Open, JobStatus::Hidden])->get();

            foreach ($jobs as $job) {
                $cancelled = $cancelled->merge($job->closeListing());
            }

            // A Gig Worker's pending applications are cancelled.
            $pending = $user->applications()->where('stage', ApplicationStage::Applied->value)->get();

            foreach ($pending as $application) {
                $application->moveTo(ApplicationStage::Cancelled);
            }

            $oldEmail = $user->email;

            $user->forceFill([
                'name' => 'Deleted account',
                'email' => 'deleted-'.$user->id.'-'.Str::lower(Str::random(10)).'@deleted.invalid',
                'password' => null,
                'phone_number' => null,
                'email_verified_at' => null,
                'remember_token' => null,
                'account_status' => AccountStatus::Deleted,
                'deleted_at' => now(),
            ])->save();

            $user->gigWorker()->update(['bio' => null]);
            $user->contractEntity()->update(['organisation_name' => null]);

            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $oldEmail)->delete();

            return $cancelled;
        });

        foreach ($cancelledByJobClosing as $application) {
            Notifier::send($application->worker, new ApplicationCancelled($application, ApplicationCancelled::ACCOUNT_DELETED));
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Your account has been deleted.');
    }
}
