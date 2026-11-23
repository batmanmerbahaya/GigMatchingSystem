<?php

namespace Tests\Feature;

use App\Enums\ApplicationStage;
use App\Models\Application;
use App\Models\User;
use App\Notifications\NewComment;
use App\Notifications\PaymentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * UC19 Post Comment, UC20 Give Feedback and the payment reminder task.
 */
class CommentAndFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_both_parties_can_comment_and_the_other_party_is_emailed(): void
    {
        $application = Application::factory()->accepted()->create();

        $this->actingAs($application->worker)
            ->post(route('comments.store', $application), ['body' => 'Starting tomorrow.'])
            ->assertRedirect();

        Notification::assertSentTo($application->job->employer, NewComment::class);

        $this->actingAs($application->job->employer)
            ->post(route('comments.store', $application), ['body' => 'Great, thanks.']);

        Notification::assertSentTo($application->worker, NewComment::class);
        $this->assertDatabaseCount('comments', 2);
    }

    public function test_thread_is_closed_before_acceptance_after_confirmation_and_to_outsiders(): void
    {
        $applied = Application::factory()->create();
        $this->actingAs($applied->worker)
            ->post(route('comments.store', $applied), ['body' => 'Hello?'])
            ->assertForbidden();

        $confirmed = Application::factory()->confirmed()->create();
        $this->actingAs($confirmed->worker)
            ->post(route('comments.store', $confirmed), ['body' => 'Late note'])
            ->assertForbidden();

        $accepted = Application::factory()->accepted()->create();
        $this->actingAs(User::factory()->gigWorker()->create())
            ->post(route('comments.store', $accepted), ['body' => 'Spam'])
            ->assertForbidden();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_feedback_is_only_available_after_payment_is_confirmed(): void
    {
        $application = Application::factory()->completed()->create();

        $this->actingAs($application->worker)
            ->post(route('reviews.store', $application), ['rating' => 5])
            ->assertForbidden();
    }

    public function test_each_party_rates_once_and_the_first_rating_sets_rated(): void
    {
        $application = Application::factory()->confirmed()->create();
        $worker = $application->worker;
        $employer = $application->job->employer;

        $this->actingAs($worker)
            ->post(route('reviews.store', $application), ['rating' => 4, 'review_text' => 'Paid on time.'])
            ->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame(ApplicationStage::Rated, $application->stage);
        $this->assertNotNull($application->rated_at);

        // Only one rating per party.
        $this->actingAs($worker)->post(route('reviews.store', $application), ['rating' => 1])->assertForbidden();

        // The other party can still rate after the stage moved to Rated.
        $this->actingAs($employer)->post(route('reviews.store', $application), ['rating' => 2])->assertSessionHasNoErrors();

        $this->assertSame(4.0, $employer->averageRating());
        $this->assertSame(2.0, $worker->averageRating());
        $this->assertSame(1, $worker->completedJobCount());
        $this->assertSame(1, $employer->completedJobCount());
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $application = Application::factory()->confirmed()->create();

        $this->actingAs($application->worker)
            ->post(route('reviews.store', $application), ['rating' => 6])
            ->assertSessionHasErrors('rating');
    }

    public function test_payment_reminder_is_sent_once_on_the_configured_day(): void
    {
        config(['gigs.payment_reminder_days' => 3]);

        $due = Application::factory()->completed()->create(['completed_at' => now()->subDays(3)]);
        $tooEarly = Application::factory()->completed()->create(['completed_at' => now()->subDay()]);

        $this->artisan('gigs:payment-reminders')->assertSuccessful();

        Notification::assertSentTo($due->job->employer, PaymentReminder::class);
        Notification::assertNotSentTo($tooEarly->job->employer, PaymentReminder::class);
    }
}
