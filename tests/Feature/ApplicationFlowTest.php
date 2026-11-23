<?php

namespace Tests\Feature;

use App\Enums\ApplicationStage;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Notifications\ApplicationAccepted;
use App\Notifications\ApplicationRejected;
use App\Notifications\JobCompleted;
use App\Notifications\NewApplication;
use App\Notifications\PaymentConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The job tracker (spec section 4): UC03, UC04, UC05, UC07, UC13, UC15, UC18.
 */
class ApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_worker_can_apply_once_and_the_employer_is_emailed(): void
    {
        $job = Job::factory()->create();
        $worker = User::factory()->gigWorker()->create();

        $this->actingAs($worker)
            ->post(route('worker.jobs.apply', $job), ['message' => 'Pick me'])
            ->assertRedirect();

        $application = Application::first();
        $this->assertSame(ApplicationStage::Applied, $application->stage);
        $this->assertNotNull($application->applied_at);
        $this->assertSame('Pick me', $application->message);
        Notification::assertSentTo($job->employer, NewApplication::class);

        // Not applied before.
        $this->actingAs($worker)->post(route('worker.jobs.apply', $job))->assertForbidden();
        $this->assertDatabaseCount('applications', 1);
    }

    public function test_applying_requires_a_verified_email_and_an_open_job(): void
    {
        $unverified = User::factory()->gigWorker()->unverified()->create();
        $this->actingAs($unverified)
            ->post(route('worker.jobs.apply', Job::factory()->create()))
            ->assertRedirect(route('verification.notice'));

        $worker = User::factory()->gigWorker()->create();
        $this->actingAs($worker)->post(route('worker.jobs.apply', Job::factory()->hidden()->create()))->assertForbidden();
        $this->actingAs($worker)->post(route('worker.jobs.apply', Job::factory()->closed()->create()))->assertForbidden();

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_accepting_one_applicant_rejects_the_others(): void
    {
        $job = Job::factory()->create();
        $chosen = Application::factory()->for($job)->create();
        $other = Application::factory()->for($job)->create();

        $this->actingAs($job->employer)
            ->patch(route('employer.applications.accept', $chosen))
            ->assertRedirect(route('applications.show', $chosen));

        $chosen->refresh();
        $this->assertSame(ApplicationStage::Accepted, $chosen->stage);
        $this->assertNotNull($chosen->accepted_at);
        $this->assertSame(JobStatus::InProgress, $job->refresh()->status);
        $this->assertSame(ApplicationStage::Rejected, $other->refresh()->stage);
        $this->assertNotNull($other->ended_at);

        Notification::assertSentTo($chosen->worker, ApplicationAccepted::class);
        Notification::assertSentTo($other->worker, ApplicationRejected::class);

        // At most one accepted worker.
        $this->actingAs($job->employer)->patch(route('employer.applications.accept', $other))->assertForbidden();
    }

    public function test_applicants_of_a_hidden_job_cannot_be_accepted(): void
    {
        $job = Job::factory()->hidden()->create();
        $application = Application::factory()->for($job)->create();

        $this->actingAs($job->employer)->patch(route('employer.applications.accept', $application))->assertForbidden();
    }

    public function test_only_the_owner_can_accept_or_reject(): void
    {
        $application = Application::factory()->create();
        $intruder = User::factory()->contractEntity()->create();

        $this->actingAs($intruder)->patch(route('employer.applications.accept', $application))->assertForbidden();
        $this->actingAs($intruder)->patch(route('employer.applications.reject', $application))->assertForbidden();
        $this->actingAs($intruder)->get(route('applications.show', $application))->assertForbidden();
    }

    public function test_employer_can_reject_and_the_worker_is_emailed(): void
    {
        $application = Application::factory()->create();

        $this->actingAs($application->job->employer)->patch(route('employer.applications.reject', $application));

        $this->assertSame(ApplicationStage::Rejected, $application->refresh()->stage);
        Notification::assertSentTo($application->worker, ApplicationRejected::class);
    }

    public function test_worker_can_withdraw_only_before_acceptance(): void
    {
        $application = Application::factory()->create();

        $this->actingAs($application->worker)->patch(route('worker.applications.withdraw', $application));
        $this->assertSame(ApplicationStage::Withdrawn, $application->refresh()->stage);
        $this->assertNotNull($application->ended_at);

        $accepted = Application::factory()->accepted()->create();
        $this->actingAs($accepted->worker)->patch(route('worker.applications.withdraw', $accepted))->assertForbidden();
        $this->assertSame(ApplicationStage::Accepted, $accepted->refresh()->stage);
    }

    public function test_finish_job_needs_an_approved_submission(): void
    {
        $application = Application::factory()->accepted()->create();

        $this->actingAs($application->worker)->patch(route('worker.applications.finish', $application))->assertForbidden();

        $approved = Application::factory()->approved()->create();
        $this->actingAs($approved->worker)->patch(route('worker.applications.finish', $approved))->assertRedirect();

        $this->assertSame(ApplicationStage::Completed, $approved->refresh()->stage);
        $this->assertNotNull($approved->completed_at);
        Notification::assertSentTo($approved->job->employer, JobCompleted::class);
    }

    public function test_confirm_payment_closes_the_job(): void
    {
        $application = Application::factory()->completed()->create();

        $this->actingAs($application->worker)
            ->patch(route('employer.applications.confirm-payment', $application))
            ->assertForbidden();

        $this->actingAs($application->job->employer)
            ->patch(route('employer.applications.confirm-payment', $application))
            ->assertRedirect();

        $this->assertSame(ApplicationStage::Confirmed, $application->refresh()->stage);
        $this->assertNotNull($application->confirmed_at);
        $this->assertSame(JobStatus::Closed, $application->job->refresh()->status);
        Notification::assertSentTo($application->worker, PaymentConfirmed::class);
    }

    public function test_phone_numbers_are_revealed_only_after_acceptance(): void
    {
        $job = Job::factory()->create();
        $job->employer->update(['phone_number' => '017-1112222']);
        $worker = User::factory()->gigWorker()->create(['phone_number' => '018-3334444']);
        $application = Application::factory()->for($job)->for($worker, 'worker')->create();

        $this->actingAs($worker)->get(route('applications.show', $application))
            ->assertOk()->assertDontSee('017-1112222');
        $this->actingAs($job->employer)->get(route('applications.show', $application))
            ->assertOk()->assertDontSee('018-3334444');

        $this->actingAs($job->employer)->patch(route('employer.applications.accept', $application));

        $this->actingAs($worker)->get(route('applications.show', $application))->assertSee('017-1112222');
        $this->actingAs($job->employer)->get(route('applications.show', $application))->assertSee('018-3334444');
    }

    public function test_tracker_lists_and_history_pages_render(): void
    {
        $active = Application::factory()->accepted()->create();
        $worker = $active->worker;
        $done = Application::factory()->confirmed()->for($worker, 'worker')->create();

        $this->actingAs($worker)->get('/worker/applications')->assertOk()->assertSee($active->job->title);
        $this->actingAs($worker)->get('/worker/history')->assertOk()->assertSee($done->job->title);
        $this->actingAs($done->job->employer)->get('/employer/history')->assertOk()->assertSee($done->job->title);
        $this->actingAs($active->job->employer)->get(route('employer.jobs.show', $active->job))->assertOk();
    }
}
