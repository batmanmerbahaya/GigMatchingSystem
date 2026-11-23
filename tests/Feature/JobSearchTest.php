<?php

namespace Tests\Feature;

use App\Enums\JobCategory;
use App\Models\Job;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UC01 Search Job and UC02 View Job Details.
 */
class JobSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = User::factory()->gigWorker()->create();
    }

    public function test_only_open_jobs_appear_in_search(): void
    {
        Job::factory()->create(['title' => 'Open gig']);
        Job::factory()->hidden()->create(['title' => 'Hidden gig']);
        Job::factory()->closed()->create(['title' => 'Closed gig']);
        Job::factory()->inProgress()->create(['title' => 'Busy gig']);

        $this->actingAs($this->worker)->get('/worker/jobs')
            ->assertOk()
            ->assertSee('Open gig')
            ->assertDontSee('Hidden gig')
            ->assertDontSee('Closed gig')
            ->assertDontSee('Busy gig');
    }

    public function test_keyword_category_location_and_pay_filters(): void
    {
        $jasin = Location::factory()->create(['state' => 'Melaka', 'district' => 'Jasin']);
        $klang = Location::factory()->create(['state' => 'Selangor', 'district' => 'Klang']);

        Job::factory()->create(['title' => 'Barista Jasin', 'category' => JobCategory::FoodAndBeverage, 'location_id' => $jasin->id, 'pay_rate' => 12]);
        Job::factory()->create(['title' => 'Tutor Klang', 'category' => JobCategory::Tutoring, 'location_id' => $klang->id, 'pay_rate' => 200]);

        $this->actingAs($this->worker)->get('/worker/jobs?q=barista')
            ->assertSee('Barista Jasin')->assertDontSee('Tutor Klang');

        $this->actingAs($this->worker)->get('/worker/jobs?category=tutoring')
            ->assertSee('Tutor Klang')->assertDontSee('Barista Jasin');

        $this->actingAs($this->worker)->get('/worker/jobs?state=Melaka')
            ->assertSee('Barista Jasin')->assertDontSee('Tutor Klang');

        $this->actingAs($this->worker)->get('/worker/jobs?location_id='.$klang->id)
            ->assertSee('Tutor Klang')->assertDontSee('Barista Jasin');

        $this->actingAs($this->worker)->get('/worker/jobs?pay_min=100')
            ->assertSee('Tutor Klang')->assertDontSee('Barista Jasin');
    }

    public function test_date_facet_matches_jobs_that_overlap_the_range(): void
    {
        Job::factory()->create([
            'title' => 'Next week job',
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(9)->toDateString(),
        ]);
        Job::factory()->create([
            'title' => 'Tomorrow job',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);

        $from = now()->addDays(8)->toDateString();
        $to = now()->addDays(12)->toDateString();

        $this->actingAs($this->worker)->get("/worker/jobs?date_from={$from}&date_to={$to}")
            ->assertSee('Next week job')
            ->assertDontSee('Tomorrow job');
    }

    public function test_sort_options(): void
    {
        Job::factory()->create(['title' => 'Cheap job', 'pay_rate' => 10]);
        Job::factory()->create(['title' => 'Rich job', 'pay_rate' => 500]);

        $this->actingAs($this->worker)->get('/worker/jobs?sort=pay_high')
            ->assertSeeInOrder(['Rich job', 'Cheap job']);

        $this->actingAs($this->worker)->get('/worker/jobs?sort=pay_low')
            ->assertSeeInOrder(['Cheap job', 'Rich job']);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->worker)
            ->get('/worker/jobs?sort=ranking&category=nope&pay_min=abc')
            ->assertSessionHasErrors(['sort', 'category', 'pay_min']);
    }

    public function test_job_details_never_show_the_employer_phone(): void
    {
        $job = Job::factory()->create();
        $job->employer->update(['phone_number' => '017-1112222']);

        $this->actingAs($this->worker)->get(route('worker.jobs.show', $job))
            ->assertOk()
            ->assertSee($job->title)
            ->assertDontSee('017-1112222');
    }

    public function test_workers_cannot_view_hidden_or_closed_jobs_they_did_not_apply_to(): void
    {
        $hidden = Job::factory()->hidden()->create();
        $closed = Job::factory()->closed()->create();

        $this->actingAs($this->worker)->get(route('worker.jobs.show', $hidden))->assertForbidden();
        $this->actingAs($this->worker)->get(route('worker.jobs.show', $closed))->assertForbidden();
    }
}
