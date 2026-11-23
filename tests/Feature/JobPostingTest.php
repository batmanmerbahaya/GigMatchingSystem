<?php

namespace Tests\Feature;

use App\Enums\ApplicationStage;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\Location;
use App\Models\User;
use App\Notifications\ApplicationCancelled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * UC09 Post Job, UC10 View Posted Job, UC11 Edit Posted Job, UC12 Close Job.
 */
class JobPostingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Cafe barista',
            'description' => 'Make coffee for the weekend crowd.',
            'category' => 'food_and_beverage',
            'location_id' => Location::factory()->create()->id,
            'pay_rate' => '12.50',
            'pay_type' => 'hourly',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ], $overrides);
    }

    public function test_verified_contract_entity_can_post_a_job(): void
    {
        $employer = User::factory()->contractEntity()->create();

        $this->actingAs($employer)->post('/employer/jobs', $this->payload())->assertSessionHasNoErrors();

        $job = Job::first();
        $this->assertSame($employer->id, $job->user_id);
        $this->assertSame(JobStatus::Open, $job->status);
        $this->actingAs($employer)->get(route('employer.jobs.show', $job))->assertOk()->assertSee('Cafe barista');
    }

    public function test_posting_requires_a_verified_email(): void
    {
        $employer = User::factory()->contractEntity()->unverified()->create();

        $this->actingAs($employer)->post('/employer/jobs', $this->payload())->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_gig_workers_cannot_post_jobs(): void
    {
        $this->actingAs(User::factory()->gigWorker()->create())
            ->post('/employer/jobs', $this->payload())
            ->assertForbidden();
    }

    public function test_job_dates_follow_the_rules(): void
    {
        $employer = User::factory()->contractEntity()->create();

        // Start date in the past.
        $this->actingAs($employer)
            ->post('/employer/jobs', $this->payload(['start_date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('start_date');

        // End date before start date.
        $this->actingAs($employer)
            ->post('/employer/jobs', $this->payload([
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(),
            ]))
            ->assertSessionHasErrors('end_date');

        // 31 days is too long, 30 days is allowed.
        $start = now()->addDay();
        $this->actingAs($employer)
            ->post('/employer/jobs', $this->payload([
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays(30)->toDateString(),
            ]))
            ->assertSessionHasErrors('end_date');

        $this->actingAs($employer)
            ->post('/employer/jobs', $this->payload([
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays(29)->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_job_input_is_strictly_validated(): void
    {
        $employer = User::factory()->contractEntity()->create();

        $this->actingAs($employer)
            ->post('/employer/jobs', $this->payload([
                'category' => 'not-a-category',
                'pay_type' => 'weekly',
                'location_id' => 999999,
                'pay_rate' => -5,
            ]))
            ->assertSessionHasErrors(['category', 'pay_type', 'location_id', 'pay_rate']);
    }

    public function test_protected_columns_cannot_be_mass_assigned(): void
    {
        $employer = User::factory()->contractEntity()->create();
        $other = User::factory()->contractEntity()->create();

        $this->actingAs($employer)->post('/employer/jobs', $this->payload([
            'user_id' => $other->id,
            'status' => 'closed',
        ]));

        $job = Job::first();
        $this->assertSame($employer->id, $job->user_id);
        $this->assertSame(JobStatus::Open, $job->status);
    }

    public function test_job_can_be_edited_only_until_the_first_application(): void
    {
        $job = Job::factory()->create();
        $employer = $job->employer;

        $this->actingAs($employer)->get(route('employer.jobs.edit', $job))->assertOk();
        $this->actingAs($employer)
            ->put(route('employer.jobs.update', $job), $this->payload(['title' => 'Renamed']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Renamed', $job->refresh()->title);

        Application::factory()->for($job)->create();

        $this->actingAs($employer)->get(route('employer.jobs.edit', $job))->assertForbidden();
        $this->actingAs($employer)
            ->put(route('employer.jobs.update', $job), $this->payload(['title' => 'Again']))
            ->assertForbidden();
    }

    public function test_contract_entities_cannot_manage_another_entitys_job(): void
    {
        $job = Job::factory()->create();
        $intruder = User::factory()->contractEntity()->create();

        $this->actingAs($intruder)->get(route('employer.jobs.show', $job))->assertForbidden();
        $this->actingAs($intruder)->get(route('employer.jobs.edit', $job))->assertForbidden();
        $this->actingAs($intruder)->patch(route('employer.jobs.close', $job))->assertForbidden();
    }

    public function test_closing_a_job_cancels_waiting_applicants_and_emails_them(): void
    {
        Notification::fake();

        $job = Job::factory()->create();
        $waiting = Application::factory()->for($job)->create();
        $withdrawn = Application::factory()->for($job)->create([
            'stage' => ApplicationStage::Withdrawn,
            'ended_at' => now(),
        ]);

        $this->actingAs($job->employer)->patch(route('employer.jobs.close', $job))->assertRedirect();

        $this->assertSame(JobStatus::Closed, $job->refresh()->status);
        $this->assertSame(ApplicationStage::Cancelled, $waiting->refresh()->stage);
        $this->assertNotNull($waiting->ended_at);
        $this->assertSame(ApplicationStage::Withdrawn, $withdrawn->refresh()->stage);
        Notification::assertSentTo($waiting->worker, ApplicationCancelled::class);
        Notification::assertNotSentTo($withdrawn->worker, ApplicationCancelled::class);

        // Closing replaces deleting: the job is kept.
        $this->assertDatabaseHas('jobs', ['id' => $job->id]);
    }

    public function test_in_progress_jobs_cannot_be_closed(): void
    {
        $application = Application::factory()->accepted()->create();

        $this->actingAs($application->job->employer)
            ->patch(route('employer.jobs.close', $application->job))
            ->assertForbidden();
    }
}
