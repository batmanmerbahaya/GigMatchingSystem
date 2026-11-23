<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Role-based access: each role reaches only its own functions.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/worker/jobs')->assertRedirect('/login');
        $this->get('/employer/jobs')->assertRedirect('/login');
    }

    public function test_each_role_lands_on_its_own_home_page(): void
    {
        $this->actingAs(User::factory()->contractEntity()->create())
            ->get('/dashboard')
            ->assertRedirect(route('employer.jobs.index'));

        $this->actingAs(User::factory()->gigWorker()->create())
            ->get('/dashboard')
            ->assertRedirect(route('worker.jobs.index'));
    }

    public function test_gig_workers_cannot_reach_contract_entity_pages(): void
    {
        $worker = User::factory()->gigWorker()->create();

        $this->actingAs($worker)->get('/employer/jobs')->assertForbidden();
        $this->actingAs($worker)->get('/employer/jobs/create')->assertForbidden();
        $this->actingAs($worker)->get('/employer/history')->assertForbidden();
    }

    public function test_contract_entities_cannot_reach_gig_worker_pages(): void
    {
        $employer = User::factory()->contractEntity()->create();

        $this->actingAs($employer)->get('/worker/jobs')->assertForbidden();
        $this->actingAs($employer)->get('/worker/applications')->assertForbidden();
        $this->actingAs($employer)->get('/worker/history')->assertForbidden();
    }

    public function test_main_pages_render_for_each_role(): void
    {
        $worker = User::factory()->gigWorker()->create();
        foreach (['/worker/jobs', '/worker/applications', '/worker/history', '/profile'] as $url) {
            $this->actingAs($worker)->get($url)->assertOk();
        }

        $employer = User::factory()->contractEntity()->create();
        foreach (['/employer/jobs', '/employer/jobs/create', '/employer/history', '/profile'] as $url) {
            $this->actingAs($employer)->get($url)->assertOk();
        }
    }

    public function test_security_headers_are_applied_to_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Strict-Transport-Security');

        $this->assertStringNotContainsString(
            'unsafe-inline',
            $response->headers->get('Content-Security-Policy'),
        );
    }
}
