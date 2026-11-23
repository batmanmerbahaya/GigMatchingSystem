<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Smoke test for the interface: with the demo data loaded, every page of
 * both roles renders, including the job tracker in each seeded stage.
 */
class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_pages_render(): void
    {
        $this->get('/')->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    public function test_every_gig_worker_page_renders(): void
    {
        foreach (['worker@example.com', 'worker2@example.com'] as $email) {
            $worker = User::where('email', $email)->firstOrFail();
            $this->actingAs($worker);

            $this->get(route('worker.jobs.index'))->assertOk()->assertSee('Find your next gig');
            $this->get(route('worker.jobs.index', ['category' => 'event', 'sort' => 'pay_high']))->assertOk();
            $this->get(route('worker.applications.index'))->assertOk();
            $this->get(route('profile.edit'))->assertOk();

            foreach (['all', 'done', 'ended'] as $show) {
                $this->get(route('worker.history', ['show' => $show]))->assertOk();
            }

            foreach (\App\Models\Job::query()->open()->get() as $job) {
                $this->get(route('worker.jobs.show', $job))->assertOk()->assertSee($job->title);
            }

            foreach ($worker->applications as $application) {
                $this->get(route('applications.show', $application))->assertOk();
            }
        }
    }

    public function test_every_contract_entity_page_renders(): void
    {
        foreach (['employer@example.com', 'employer2@example.com'] as $email) {
            $employer = User::where('email', $email)->firstOrFail();
            $this->actingAs($employer);

            $this->get(route('employer.jobs.index'))->assertOk();
            $this->get(route('employer.jobs.create'))->assertOk();
            $this->get(route('employer.history'))->assertOk();
            $this->get(route('profile.edit'))->assertOk();

            foreach ($employer->jobs as $job) {
                $this->get(route('employer.jobs.show', $job))->assertOk()->assertSee($job->title);
            }

            $contracts = Application::query()
                ->whereIn('job_id', $employer->jobs()->pluck('id'))
                ->get();
            foreach ($contracts as $application) {
                $this->get(route('applications.show', $application))->assertOk();
            }
        }
    }

    public function test_history_rating_returns_to_history(): void
    {
        $worker = User::where('email', 'worker@example.com')->firstOrFail();
        $paid = $worker->applications()->where('stage', 'confirmed')->firstOrFail();

        $this->actingAs($worker)
            ->post(route('reviews.store', $paid), ['rating' => 5, 'return' => 'history'])
            ->assertRedirect(route('worker.history'));

        $this->assertDatabaseHas('reviews', ['application_id' => $paid->id, 'reviewer_id' => $worker->id, 'rating' => 5]);
    }
}
