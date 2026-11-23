<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\ApplicationStage;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Notifications\ApplicationCancelled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * UC16 Manage Profile and UC21 Delete Account.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();
        $this->actingAs(User::factory()->contractEntity()->create())->get('/profile')->assertOk();
    }

    public function test_worker_can_update_profile_and_bio(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'phone_number' => '019-8887777',
                'bio' => 'I can cook.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('019-8887777', $user->phone_number);
        $this->assertSame('I can cook.', $user->gigWorker->bio);
        $this->assertNull($user->email_verified_at, 'A new email must be verified again.');
    }

    public function test_email_verification_is_kept_when_the_email_does_not_change(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_delete_account_anonymises_the_user_and_keeps_records(): void
    {
        Notification::fake();

        $employer = User::factory()->contractEntity()->create(['email' => 'boss@example.com']);
        $openJob = Job::factory()->for($employer, 'employer')->create();
        $waiting = Application::factory()->for($openJob)->create();
        $doneJob = Job::factory()->for($employer, 'employer')->create();
        $finished = Application::factory()->confirmed()->for($doneJob)->create();

        $this->actingAs($employer)
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();

        $employer->refresh();
        $this->assertSame(AccountStatus::Deleted, $employer->account_status);
        $this->assertNotNull($employer->deleted_at);
        $this->assertSame('Deleted account', $employer->name);
        $this->assertNotSame('boss@example.com', $employer->email);
        $this->assertNull($employer->password);
        $this->assertNull($employer->phone_number);

        // Open jobs are closed and waiting applicants cancelled and emailed.
        $this->assertSame(JobStatus::Closed, $openJob->refresh()->status);
        $this->assertSame(ApplicationStage::Cancelled, $waiting->refresh()->stage);
        Notification::assertSentTo($waiting->worker, ApplicationCancelled::class);

        // The other party keeps their records.
        $this->assertSame(ApplicationStage::Confirmed, $finished->refresh()->stage);
        $this->assertDatabaseHas('jobs', ['id' => $doneJob->id]);
    }

    public function test_deleted_account_cannot_log_in_again(): void
    {
        $user = User::factory()->create(['email' => 'gone@example.com']);

        $this->actingAs($user)->delete('/profile', ['password' => 'password']);

        $this->post('/login', ['email' => 'gone@example.com', 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_delete_account_is_blocked_while_accepted_work_is_unconfirmed(): void
    {
        $application = Application::factory()->accepted()->create();
        $worker = $application->worker;

        $this->actingAs($worker)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertSame(AccountStatus::Active, $worker->refresh()->account_status);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', ['password' => 'wrong-password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertSame(AccountStatus::Active, $user->refresh()->account_status);
    }
}
