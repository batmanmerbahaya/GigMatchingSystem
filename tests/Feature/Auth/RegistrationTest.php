<?php

namespace Tests\Feature\Auth;

use App\Enums\EntityType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_a_gig_worker_can_register_and_use_the_account_before_verifying(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'role' => 'gig_worker',
            'name' => 'Test Worker',
            'email' => 'worker@test.com',
            'phone_number' => '012-3456789',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'worker@test.com')->first();
        $this->assertSame(UserRole::GigWorker, $user->role);
        $this->assertNotNull($user->gigWorker);
        $this->assertNull($user->email_verified_at);

        // Verify-later: the verification email is sent, and search works straight away.
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/worker/jobs')->assertOk();
    }

    public function test_a_business_contract_entity_must_give_an_organisation_name(): void
    {
        $data = [
            'role' => 'contract_entity',
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'phone_number' => '0123456789',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'entity_type' => 'business',
        ];

        $this->post('/register', $data)->assertSessionHasErrors('organisation_name');
        $this->assertGuest();

        $this->post('/register', $data + ['organisation_name' => 'Kedai Runcit Ali']);

        $user = User::where('email', 'owner@test.com')->first();
        $this->assertSame(EntityType::Business, $user->contractEntity->entity_type);
        $this->assertSame('Kedai Runcit Ali', $user->displayName());
    }

    public function test_registration_requires_a_valid_role_and_phone_number(): void
    {
        $response = $this->post('/register', [
            'role' => 'admin',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '12345',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors(['role', 'phone_number']);
        $this->assertGuest();
    }
}
