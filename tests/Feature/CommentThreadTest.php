<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Comment;
use App\Models\Submission;
use App\Notifications\NewComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * UC19 Post Comment with threads: a reply sits under the comment it answers.
 */
class CommentThreadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_a_reply_is_saved_under_its_comment_and_opens_at_it(): void
    {
        $application = Application::factory()->accepted()->create();
        $submission = Submission::factory()->for($application)->create();

        $this->actingAs($application->job->employer)
            ->post(route('comments.store', $application), ['body' => 'Please make the price bigger.', 'submission_id' => $submission->id]);
        $parent = Comment::firstOrFail();

        $response = $this->actingAs($application->worker)
            ->post(route('comments.store', $application), ['body' => 'Done, see the new draft.', 'parent_id' => $parent->id]);

        $reply = Comment::where('parent_id', $parent->id)->firstOrFail();
        $response->assertRedirect(route('applications.show', $application).'#comment-'.$reply->id);

        // The reply belongs to the same submission as the comment it answers.
        $this->assertSame($submission->id, $reply->submission_id);
        Notification::assertSentTo($application->job->employer, NewComment::class);
    }

    public function test_the_tracker_shows_replies_under_their_comment(): void
    {
        $application = Application::factory()->accepted()->create();

        $first = $this->comment($application, $application->job->user_id, 'First top-level comment');
        $this->comment($application, $application->user_id, 'Second top-level comment');
        $this->comment($application, $application->user_id, 'A reply to the first', $first->id);

        $this->actingAs($application->worker)->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('3 comments')
            ->assertSeeInOrder(['First top-level comment', 'A reply to the first', 'Second top-level comment'])
            ->assertSee('class="c-replies"', false)
            ->assertSee('name="parent_id" value="'.$first->id.'"', false);
    }

    public function test_cannot_reply_to_a_comment_from_another_job(): void
    {
        $mine = Application::factory()->accepted()->create();
        $other = Application::factory()->accepted()->create();
        $elsewhere = $this->comment($other, $other->user_id, 'Somewhere else');

        $this->actingAs($mine->worker)
            ->post(route('comments.store', $mine), ['body' => 'Sneaky reply', 'parent_id' => $elsewhere->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('comments', ['body' => 'Sneaky reply']);
    }

    public function test_replies_follow_the_same_rules_as_comments(): void
    {
        $confirmed = Application::factory()->confirmed()->create();
        $old = $this->comment($confirmed, $confirmed->user_id, 'Before payment');

        // After payment is confirmed the thread is read-only, replies included.
        $this->actingAs($confirmed->worker)
            ->post(route('comments.store', $confirmed), ['body' => 'Late reply', 'parent_id' => $old->id])
            ->assertForbidden();

        $this->actingAs($confirmed->worker)->get(route('applications.show', $confirmed))
            ->assertOk()
            ->assertDontSee('name="parent_id"', false);
    }

    private function comment(Application $application, int $userId, string $body, ?int $parentId = null): Comment
    {
        $comment = new Comment(['body' => $body, 'parent_id' => $parentId]);
        $comment->application_id = $application->id;
        $comment->user_id = $userId;
        $comment->save();

        return $comment;
    }
}
