<?php

namespace Tests\Feature;

use App\Enums\JobCategory;
use App\Enums\PayType;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UC01 Search Job: the pay facet. Hourly, daily and fixed pay are filtered
 * separately, and the slider's ends and dots come from the open jobs.
 */
class PayFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = User::factory()->gigWorker()->create();

        Job::factory()->create(['title' => 'Hourly helper', 'pay_type' => PayType::Hourly, 'pay_rate' => 12, 'category' => JobCategory::FoodAndBeverage]);
        Job::factory()->create(['title' => 'Daily crew', 'pay_type' => PayType::Daily, 'pay_rate' => 80, 'category' => JobCategory::Event]);
        Job::factory()->create(['title' => 'Daily promoter', 'pay_type' => PayType::Daily, 'pay_rate' => 150, 'category' => JobCategory::Event]);
        Job::factory()->create(['title' => 'Fixed design', 'pay_type' => PayType::Fixed, 'pay_rate' => 450, 'category' => JobCategory::DesignDigital]);
        Job::factory()->closed()->create(['title' => 'Closed daily', 'pay_type' => PayType::Daily, 'pay_rate' => 999]);
    }

    public function test_pay_type_shows_only_jobs_paid_that_way(): void
    {
        $this->actingAs($this->worker)->get('/worker/jobs?pay_type=daily')
            ->assertOk()
            ->assertSee('Daily crew')
            ->assertSee('Daily promoter')
            ->assertDontSee('Hourly helper')
            ->assertDontSee('Fixed design');
    }

    public function test_pay_range_applies_within_the_pay_type(): void
    {
        // RM 100 or more per day: the RM 12 hourly job is not counted as "less pay".
        $this->actingAs($this->worker)->get('/worker/jobs?pay_type=daily&pay_min=100')
            ->assertOk()
            ->assertSee('Daily promoter')
            ->assertDontSee('Daily crew')
            ->assertDontSee('Hourly helper');
    }

    public function test_slider_ends_and_dots_come_from_open_jobs_of_that_type(): void
    {
        // Daily pay among open jobs is RM 80 to RM 150, widened and rounded to RM 60 to RM 180.
        // The closed RM 999 job is ignored. Both daily jobs are dots, even with a pay range set.
        $this->actingAs($this->worker)->get('/worker/jobs?pay_type=daily&pay_min=100')
            ->assertOk()
            ->assertSee('data-floor="60"', false)
            ->assertSee('data-ceil="180"', false)
            ->assertSee('data-amounts="[80,150]"', false)
            ->assertSee('1 gig in this range');
    }

    public function test_dots_follow_the_category_too(): void
    {
        $this->actingAs($this->worker)->get('/worker/jobs?pay_type=fixed&category=event')
            ->assertOk()
            ->assertSee('data-amounts="[]"', false)
            ->assertSee('0 gigs in this range');
    }

    public function test_without_a_pay_type_there_is_no_range(): void
    {
        $this->actingAs($this->worker)->get('/worker/jobs')
            ->assertOk()
            ->assertSee('Pick a pay type to set a range')
            ->assertDontSee('data-floor=', false)
            ->assertSee('Hourly helper')
            ->assertSee('Fixed design');
    }

    public function test_unknown_pay_type_is_rejected(): void
    {
        $this->actingAs($this->worker)->get('/worker/jobs?pay_type=weekly')
            ->assertSessionHasErrors('pay_type');
    }
}
