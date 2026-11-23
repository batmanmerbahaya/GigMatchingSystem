<?php

namespace Database\Factories;

use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Enums\PayType;
use App\Models\Job;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 20))->startOfDay();

        return [
            'user_id' => User::factory()->contractEntity(),
            'category' => fake()->randomElement(JobCategory::cases()),
            'location_id' => Location::factory(),
            'title' => fake()->randomElement([
                'Event Crew', 'Cafe Helper', 'Data Entry Clerk', 'Delivery Rider',
                'Retail Assistant', 'Tuition Helper', 'Poster Designer', 'Office Cleaner',
            ]),
            'description' => fake()->paragraphs(2, true),
            'pay_rate' => fake()->randomFloat(2, 10, 300),
            'pay_type' => fake()->randomElement(PayType::cases()),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(fake()->numberBetween(0, 6))->toDateString(),
            'status' => JobStatus::Open,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => ['status' => JobStatus::InProgress]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['status' => JobStatus::Hidden]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => JobStatus::Closed]);
    }
}
