<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WorkController;
use App\Models\Job;
use Illuminate\Support\Facades\Route;

// Welcome page, with the three newest open jobs shown as a stack of cards.
Route::get('/', function () {
    return view('welcome', [
        'sampleJobs' => Job::query()->open()->with('location')->latest()->take(3)->get(),
        'openCount' => Job::query()->open()->count(),
    ]);
})->name('home');

// Lands each user on the home page for their role.
Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Gig Worker routes
|--------------------------------------------------------------------------
| role:gig_worker returns 403 to Contract Entities. Applying and reporting
| also need a verified email (verify-later). Policies check ownership and
| the job tracker stage inside each action.
*/
Route::middleware(['auth', 'role:gig_worker'])
    ->prefix('worker')
    ->name('worker.')
    ->group(function () {
        Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');                       // UC01
        Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');                   // UC02
        Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store'])                       // UC03
            ->middleware(['verified', 'throttle:10,1'])
            ->name('jobs.apply');
        Route::post('/jobs/{job}/report', [ReportController::class, 'store'])                           // UC08
            ->middleware(['verified', 'throttle:10,1'])
            ->name('jobs.report');

        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index'); // UC05
        Route::patch('/applications/{application}/withdraw', [ApplicationController::class, 'withdraw'])  // UC04
            ->name('applications.withdraw');
        Route::post('/applications/{application}/submissions', [WorkController::class, 'store'])         // UC06
            ->middleware('throttle:10,1')
            ->name('submissions.store');
        Route::patch('/applications/{application}/finish', [ApplicationController::class, 'finish'])      // UC07
            ->name('applications.finish');

        Route::get('/history', [ApplicationController::class, 'history'])->name('history');            // UC17
    });

/*
|--------------------------------------------------------------------------
| Contract Entity routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:contract_entity'])
    ->prefix('employer')
    ->name('employer.')
    ->group(function () {
        Route::get('/jobs', [JobController::class, 'postings'])->name('jobs.index');                    // UC10
        Route::get('/jobs/create', [JobController::class, 'create'])                                    // UC09
            ->middleware('verified')
            ->name('jobs.create');
        Route::post('/jobs', [JobController::class, 'store'])
            ->middleware(['verified', 'throttle:20,1'])
            ->name('jobs.store');
        Route::get('/jobs/{job}', [JobController::class, 'manage'])->name('jobs.show');                 // UC10, UC13
        Route::get('/jobs/{job}/edit', [JobController::class, 'edit'])->name('jobs.edit');              // UC11
        Route::put('/jobs/{job}', [JobController::class, 'update'])
            ->middleware('throttle:20,1')
            ->name('jobs.update');
        Route::patch('/jobs/{job}/close', [JobController::class, 'close'])->name('jobs.close');         // UC12

        Route::patch('/applications/{application}/accept', [ApplicationController::class, 'accept'])    // UC13
            ->name('applications.accept');
        Route::patch('/applications/{application}/reject', [ApplicationController::class, 'reject'])
            ->name('applications.reject');
        Route::patch('/submissions/{submission}/approve', [WorkController::class, 'approve'])           // UC14
            ->name('submissions.approve');
        Route::patch('/submissions/{submission}/request-changes', [WorkController::class, 'requestChanges'])
            ->name('submissions.request-changes');
        Route::patch('/applications/{application}/confirm-payment', [ApplicationController::class, 'confirmPayment']) // UC15
            ->name('applications.confirm-payment');

        Route::get('/history', [JobController::class, 'history'])->name('history');                    // UC17
    });

/*
|--------------------------------------------------------------------------
| Routes for both roles
|--------------------------------------------------------------------------
| Policies make sure only the two parties of a job reach its tracker,
| comments and files.
*/
Route::middleware('auth')->group(function () {
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show'); // UC18
    Route::post('/applications/{application}/comments', [CommentController::class, 'store'])        // UC19
        ->middleware('throttle:30,1')
        ->name('comments.store');
    Route::post('/applications/{application}/reviews', [FeedbackController::class, 'store'])        // UC20
        ->name('reviews.store');
    Route::get('/files/{file}', [WorkController::class, 'download'])->name('files.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');                // UC16
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');       // UC21
});

require __DIR__.'/auth.php';
