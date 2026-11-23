<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;
use App\Notifications\ListingHidden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * UC08 Report Listing: moderation without an admin (spec section 8).
 */
class ReportListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['gigs.report_threshold' => 3]);
    }

    public function test_a_note_is_required_when_the_reason_is_other(): void
    {
        $job = Job::factory()->create();
        $worker = User::factory()->gigWorker()->create();

        $this->actingAs($worker)
            ->post(route('worker.jobs.report', $job), ['reason' => 'other'])
            ->assertSessionHasErrors('note');

        $this->actingAs($worker)
            ->post(route('worker.jobs.report', $job), ['reason' => 'scam'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_a_user_can_report_a_listing_only_once(): void
    {
        $job = Job::factory()->create();
        $worker = User::factory()->gigWorker()->create();

        $this->actingAs($worker)->post(route('worker.jobs.report', $job), ['reason' => 'spam']);
        $this->actingAs($worker)->post(route('worker.jobs.report', $job), ['reason' => 'spam'])->assertForbidden();

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_reporting_requires_a_verified_email(): void
    {
        $job = Job::factory()->create();

        $this->actingAs(User::factory()->gigWorker()->unverified()->create())
            ->post(route('worker.jobs.report', $job), ['reason' => 'scam'])
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_listing_is_hidden_when_the_threshold_is_reached(): void
    {
        $job = Job::factory()->create(['title' => 'Too good to be true']);
        $reporters = User::factory()->gigWorker()->count(3)->create();

        $this->actingAs($reporters[0])->post(route('worker.jobs.report', $job), ['reason' => 'scam']);
        $this->actingAs($reporters[1])->post(route('worker.jobs.report', $job), ['reason' => 'misleading_details']);
        $this->assertSame(JobStatus::Open, $job->refresh()->status);
        Notification::assertNothingSent();

        $this->actingAs($reporters[2])->post(route('worker.jobs.report', $job), ['reason' => 'spam']);

        $this->assertSame(JobStatus::Hidden, $job->refresh()->status);
        Notification::assertSentTo($job->employer, ListingHidden::class);

        // Hidden jobs disappear from search but are not deleted.
        $this->actingAs(User::factory()->gigWorker()->create())
            ->get('/worker/jobs')
            ->assertDontSee('Too good to be true');
        $this->assertDatabaseHas('jobs', ['id' => $job->id]);

        // The owner can still see it and close it, but not edit it.
        $this->actingAs($job->employer)->get(route('employer.jobs.show', $job))->assertOk();
        $this->actingAs($job->employer)->get(route('employer.jobs.edit', $job))->assertForbidden();
        $this->actingAs($job->employer)->patch(route('employer.jobs.close', $job));
        $this->assertSame(JobStatus::Closed, $job->refresh()->status);
    }

    public function test_contract_entities_cannot_report(): void
    {
        $job = Job::factory()->create();

        $this->actingAs(User::factory()->contractEntity()->create())
            ->post(route('worker.jobs.report', $job), ['reason' => 'scam'])
            ->assertForbidden();
    }
}
