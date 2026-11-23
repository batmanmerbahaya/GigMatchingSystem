<?php

namespace Tests\Feature;

use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The welcome page loads, even with no jobs yet.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * The welcome page shows the newest open jobs, never closed ones.
     */
    public function test_the_welcome_page_shows_newest_open_jobs(): void
    {
        Job::factory()->create(['title' => 'Weekend barista cover']);
        Job::factory()->closed()->create(['title' => 'Old flyer job']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Weekend barista cover')
            ->assertDontSee('Old flyer job');
    }
}
