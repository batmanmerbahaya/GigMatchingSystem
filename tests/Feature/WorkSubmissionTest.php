<?php

namespace Tests\Feature;

use App\Enums\ApprovalMethod;
use App\Enums\SubmissionStatus;
use App\Models\Application;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\ChangesRequested;
use App\Notifications\NewSubmission;
use App\Notifications\SubmissionApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Work submission and approval (spec section 5): UC06 and UC14, file
 * privacy and the automatic approval scheduled task.
 */
class WorkSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('local');
    }

    public function test_accepted_worker_can_submit_work_with_files(): void
    {
        $application = Application::factory()->accepted()->create();

        $this->actingAs($application->worker)
            ->post(route('worker.submissions.store', $application), [
                'note' => 'Poster draft attached.',
                'files' => [
                    UploadedFile::fake()->create('poster.pdf', 500, 'application/pdf'),
                    UploadedFile::fake()->create('preview.png', 50, 'image/png'),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('applications.show', $application));

        $submission = Submission::first();
        $this->assertSame(SubmissionStatus::Pending, $submission->status);
        $this->assertCount(2, $submission->files);

        // Stored on the private disk; only the path is in the database.
        foreach ($submission->files as $file) {
            Storage::disk('local')->assertExists($file->stored_path);
        }

        Notification::assertSentTo($application->job->employer, NewSubmission::class);
    }

    public function test_files_are_optional(): void
    {
        $application = Application::factory()->accepted()->create();

        $this->actingAs($application->worker)
            ->post(route('worker.submissions.store', $application), ['note' => 'Cleaning done, see photos in person.'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('submissions', 1);
    }

    public function test_file_count_size_and_type_are_validated(): void
    {
        $application = Application::factory()->accepted()->create();
        $worker = $application->worker;
        $url = route('worker.submissions.store', $application);

        $six = array_map(fn ($i) => UploadedFile::fake()->create("f{$i}.pdf", 10, 'application/pdf'), range(1, 6));
        $this->actingAs($worker)->post($url, ['note' => 'x', 'files' => $six])->assertSessionHasErrors('files');

        $big = UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf');
        $this->actingAs($worker)->post($url, ['note' => 'x', 'files' => [$big]])->assertSessionHasErrors('files.0');

        $exe = UploadedFile::fake()->create('tool.exe', 10, 'application/x-msdownload');
        $this->actingAs($worker)->post($url, ['note' => 'x', 'files' => [$exe]])->assertSessionHasErrors('files.0');

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_work_can_only_be_submitted_after_acceptance_and_one_at_a_time(): void
    {
        $applied = Application::factory()->create();
        $this->actingAs($applied->worker)
            ->post(route('worker.submissions.store', $applied), ['note' => 'Too early'])
            ->assertForbidden();

        $accepted = Application::factory()->accepted()->create();
        Submission::factory()->for($accepted)->create();

        $this->actingAs($accepted->worker)
            ->post(route('worker.submissions.store', $accepted), ['note' => 'Second one'])
            ->assertForbidden();
    }

    public function test_employer_can_approve_a_submission(): void
    {
        $submission = Submission::factory()->create();
        $application = $submission->application;

        $this->actingAs($application->worker)
            ->patch(route('employer.submissions.approve', $submission))
            ->assertForbidden();

        $this->actingAs($application->job->employer)
            ->patch(route('employer.submissions.approve', $submission))
            ->assertRedirect();

        $this->assertSame(SubmissionStatus::Approved, $submission->refresh()->status);
        $application->refresh();
        $this->assertNotNull($application->approved_at);
        $this->assertSame(ApprovalMethod::Manual, $application->approval_method);
        Notification::assertSentTo($application->worker, SubmissionApproved::class);
    }

    public function test_request_changes_adds_a_comment_and_allows_a_new_submission(): void
    {
        $submission = Submission::factory()->create();
        $application = $submission->application;

        $this->actingAs($application->job->employer)
            ->patch(route('employer.submissions.request-changes', $submission), ['comment' => 'Make the logo bigger.'])
            ->assertRedirect();

        $this->assertSame(SubmissionStatus::ChangesRequested, $submission->refresh()->status);
        $this->assertDatabaseHas('comments', [
            'application_id' => $application->id,
            'submission_id' => $submission->id,
            'body' => 'Make the logo bigger.',
        ]);
        Notification::assertSentTo($application->worker, ChangesRequested::class);

        $this->actingAs($application->worker)
            ->post(route('worker.submissions.store', $application), ['note' => 'Bigger logo now.'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('submissions', 2);
    }

    public function test_only_the_two_parties_can_download_files(): void
    {
        $application = Application::factory()->accepted()->create();

        $this->actingAs($application->worker)->post(route('worker.submissions.store', $application), [
            'note' => 'Here it is',
            'files' => [UploadedFile::fake()->create('work.pdf', 20, 'application/pdf')],
        ]);

        $file = Submission::first()->files->first();

        $this->actingAs($application->worker)->get(route('files.download', $file))->assertOk();
        $this->actingAs($application->job->employer)->get(route('files.download', $file))->assertOk();
        $this->actingAs(User::factory()->gigWorker()->create())->get(route('files.download', $file))->assertForbidden();
        $this->actingAs(User::factory()->contractEntity()->create())->get(route('files.download', $file))->assertForbidden();
    }

    public function test_scheduled_task_approves_old_pending_submissions_automatically(): void
    {
        config(['gigs.auto_approve_days' => 7]);

        $old = Submission::factory()->create(['submitted_at' => now()->subDays(8)]);
        $recent = Submission::factory()->create(['submitted_at' => now()->subDays(2)]);

        $this->artisan('gigs:auto-approve')->assertSuccessful();

        $this->assertSame(SubmissionStatus::Approved, $old->refresh()->status);
        $this->assertSame(ApprovalMethod::Automatic, $old->application->approval_method);
        $this->assertSame(SubmissionStatus::Pending, $recent->refresh()->status);

        Notification::assertSentTo($old->application->worker, SubmissionApproved::class);
        Notification::assertSentTo($old->application->job->employer, SubmissionApproved::class);
    }
}
