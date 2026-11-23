<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Application;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory()->accepted(),
            'note' => fake()->sentence(),
            'status' => SubmissionStatus::Pending,
            'submitted_at' => now(),
        ];
    }
}
