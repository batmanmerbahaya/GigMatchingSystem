<?php

namespace Database\Factories;

use App\Enums\ApplicationStage;
use App\Enums\ApprovalMethod;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * States follow the job tracker, so each one also puts the job in the
 * status the spec expects at that stage.
 *
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'user_id' => User::factory()->gigWorker(),
            'message' => fake()->sentence(),
            'stage' => ApplicationStage::Applied,
            'applied_at' => now()->subDays(3),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => ApplicationStage::Accepted,
            'accepted_at' => now()->subDays(2),
        ])->afterCreating(function (Application $application) {
            $application->job->forceFill(['status' => JobStatus::InProgress])->save();
        });
    }

    public function approved(): static
    {
        return $this->accepted()->state(fn (array $attributes) => [
            'approved_at' => now()->subDay(),
            'approval_method' => ApprovalMethod::Manual,
        ]);
    }

    public function completed(): static
    {
        return $this->approved()->state(fn (array $attributes) => [
            'stage' => ApplicationStage::Completed,
            'completed_at' => now()->subHours(12),
        ]);
    }

    public function confirmed(): static
    {
        return $this->completed()->state(fn (array $attributes) => [
            'stage' => ApplicationStage::Confirmed,
            'confirmed_at' => now()->subHour(),
        ])->afterCreating(function (Application $application) {
            $application->job->forceFill(['status' => JobStatus::Closed])->save();
        });
    }
}
