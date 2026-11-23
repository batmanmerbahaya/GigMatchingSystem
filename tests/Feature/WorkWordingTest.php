<?php

namespace Tests\Feature;

use App\Enums\JobCategory;
use App\Models\Application;
use App\Models\Job;
use App\Models\Submission;
use App\Notifications\NewSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * UC06, UC07 and UC14 wording: in-person jobs "mark the job as done" and the
 * employer confirms it or sends it back; online jobs keep "submit work".
 * Only the words differ, never the steps.
 */
class WorkWordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function accepted(JobCategory $category): Application
    {
        return Application::factory()->accepted()
            ->for(Job::factory()->inProgress()->create(['category' => $category]))
            ->create();
    }

    public function test_in_person_job_asks_the_worker_to_mark_it_as_done(): void
    {
        $application = $this->accepted(JobCategory::Event);

        $this->actingAs($application->worker)->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Mark the job as done')
            ->assertSee('What did you do, and when?')
            ->assertSee('Photos or files')
            ->assertSee('Mark as done')
            ->assertDontSee('Submit work');
    }

    public function test_online_job_keeps_submit_work(): void
    {
        $application = $this->accepted(JobCategory::DesignDigital);

        $this->actingAs($application->worker)->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Submit work')
            ->assertDontSee('Mark the job as done');
    }

    public function test_employer_confirms_or_sends_back_an_in_person_report(): void
    {
        $application = $this->accepted(JobCategory::Cleaning);
        Submission::factory()->for($application)->create();

        $this->actingAs($application->job->employer)->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Job done reports')
            ->assertSee('Report 1')
            ->assertSee('Waiting for confirmation')
            ->assertSee('>Confirm</button>', false)
            ->assertSee('Send back')
            ->assertSee('What still needs doing?')
            ->assertDontSee('Request changes');
    }

    public function test_employer_approves_or_requests_changes_on_online_work(): void
    {
        $application = $this->accepted(JobCategory::DataEntryAdmin);
        Submission::factory()->for($application)->create();

        $this->actingAs($application->job->employer)->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Submission 1')
            ->assertSee('Pending review')
            ->assertSee('>Approve</button>', false)
            ->assertSee('Request changes');
    }

    public function test_marking_as_done_uses_the_same_step_with_in_person_words(): void
    {
        $application = $this->accepted(JobCategory::Delivery);

        $this->actingAs($application->worker)
            ->post(route('worker.submissions.store', $application), ['note' => 'Delivered all 30 orders, 6 PM to 10 PM.'])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHas('status', 'Marked as done. The employer has been emailed to confirm it.');

        $this->assertDatabaseHas('submissions', ['application_id' => $application->id, 'status' => 'pending']);

        Notification::assertSentTo(
            $application->job->employer,
            NewSubmission::class,
            fn (NewSubmission $notification, array $channels, object $notifiable) => $notification->toMail($notifiable)->subject
                === 'Job marked as done: '.$application->job->title,
        );

        $this->actingAs($application->worker)->get(route('worker.applications.index'))
            ->assertOk()
            ->assertSee('Your report is waiting for');
    }
}
