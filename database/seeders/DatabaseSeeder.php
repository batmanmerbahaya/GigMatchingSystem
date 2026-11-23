<?php

namespace Database\Seeders;

use App\Enums\ApplicationStage;
use App\Enums\JobCategory;
use App\Enums\PayType;
use App\Enums\SubmissionStatus;
use App\Models\Application;
use App\Models\Comment;
use App\Models\Job;
use App\Models\Location;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Locations plus demo data for local development.
 *
 * Demo logins, password Password123 for all:
 *   employer@example.com   Contract Entity (business)
 *   employer2@example.com  Contract Entity (individual)
 *   worker@example.com     Gig Worker
 *   worker2@example.com    Gig Worker
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(LocationSeeder::class);

        $password = Hash::make('Password123');

        $employer = User::factory()->business('Kopitiam Jasin Enterprise')->create([
            'name' => 'Demo Employer', 'email' => 'employer@example.com',
            'password' => $password, 'phone_number' => '012-3456789',
        ]);
        $employer2 = User::factory()->contractEntity()->create([
            'name' => 'Aisyah Rahman', 'email' => 'employer2@example.com',
            'password' => $password, 'phone_number' => '017-2223344',
        ]);
        $worker = User::factory()->gigWorker()->create([
            'name' => 'Demo Worker', 'email' => 'worker@example.com',
            'password' => $password, 'phone_number' => '013-9876543',
        ]);
        $worker2 = User::factory()->gigWorker()->create([
            'name' => 'Hafiz Kamal', 'email' => 'worker2@example.com',
            'password' => $password, 'phone_number' => '019-5556677',
        ]);

        // Open listings that appear in search.
        $open = [
            [$employer, 'Event crew for wedding reception', JobCategory::Event, 'Melaka Tengah', 120, PayType::Daily, 5, 1],
            [$employer, 'Cafe kitchen helper', JobCategory::FoodAndBeverage, 'Jasin', 10, PayType::Hourly, 3, 4],
            [$employer, 'Pasar malam stall helper', JobCategory::Retail, 'Alor Gajah', 80, PayType::Daily, 7, 2],
            [$employer, 'Parcel sorting at warehouse', JobCategory::Delivery, 'Klang', 11, PayType::Hourly, 10, 5],
            [$employer2, 'SPM Mathematics tutor, four sessions', JobCategory::Tutoring, 'Jasin', 200, PayType::Fixed, 4, 14],
            [$employer2, 'Office cleaning after renovation', JobCategory::Cleaning, 'Johor Bahru', 350, PayType::Fixed, 6, 1],
            [$employer2, 'Data entry for inventory list', JobCategory::DataEntryAdmin, 'W.P. Kuala Lumpur', 15, PayType::Hourly, 2, 3],
            [$employer2, 'Logo and menu design for a food truck', JobCategory::DesignDigital, 'Seremban', 450, PayType::Fixed, 1, 10],
            [$employer2, 'Food delivery rider, evening shift', JobCategory::Delivery, 'Petaling', 12, PayType::Hourly, 8, 6],
            [$employer, 'Roadshow promoter at shopping mall', JobCategory::Event, 'Petaling', 150, PayType::Daily, 12, 3],
        ];

        $jobs = [];
        foreach ($open as [$owner, $title, $category, $district, $pay, $payType, $startsIn, $days]) {
            $jobs[$title] = $this->job($owner, $title, $category, $district, $pay, $payType, $startsIn, $days);
        }

        // Two applicants waiting for a decision on one open job.
        foreach ([$worker, $worker2] as $applicant) {
            Application::factory()->for($jobs['Event crew for wedding reception'])->for($applicant, 'worker')->create([
                'message' => 'I have done wedding events before and can work the full day.',
            ]);
        }

        // In progress: accepted, one submission sent back, a newer one pending.
        $poster = $this->job($employer, 'Poster design for kopitiam promotion', JobCategory::DesignDigital, 'Jasin', 180, PayType::Fixed, 1, 7);
        $contract = Application::factory()->accepted()->for($poster)->for($worker, 'worker')->create();
        $first = Submission::factory()->for($contract)->create([
            'note' => 'First draft of the poster, A3 size.',
            'status' => SubmissionStatus::ChangesRequested,
            'submitted_at' => now()->subDay(),
        ]);
        $feedback = new Comment(['body' => 'Please make the price bigger and use our logo colours.', 'submission_id' => $first->id]);
        $feedback->application_id = $contract->id;
        $feedback->user_id = $employer->id;
        $feedback->save();
        // The worker answers that comment directly, so the thread shows a reply.
        $reply = new Comment([
            'body' => 'Done. The price is bigger now and I used your logo colours. It is in the second draft.',
            'submission_id' => $first->id,
            'parent_id' => $feedback->id,
        ]);
        $reply->application_id = $contract->id;
        $reply->user_id = $worker->id;
        $reply->save();
        Submission::factory()->for($contract)->create(['note' => 'Second draft with the changes.']);

        // Payment confirmed and ready for feedback from both sides.
        $barista = $this->job($employer2, 'Weekend barista cover', JobCategory::FoodAndBeverage, 'Melaka Tengah', 90, PayType::Daily, 1, 2);
        Application::factory()->confirmed()->for($barista)->for($worker, 'worker')->create();

        // A closed job whose pending applicant was cancelled.
        $closed = $this->job($employer, 'Flyer distribution', JobCategory::Other, 'Jasin', 60, PayType::Daily, 2, 1);
        $closed->closeListing();
        Application::factory()->for($closed)->for($worker2, 'worker')->create([
            'stage' => ApplicationStage::Cancelled,
            'ended_at' => now(),
        ]);
    }

    private function job(User $owner, string $title, JobCategory $category, string $district, float $pay, PayType $payType, int $startsIn, int $days): Job
    {
        $start = now()->addDays($startsIn)->startOfDay();

        return Job::factory()->for($owner, 'employer')->create([
            'title' => $title,
            'category' => $category,
            'location_id' => Location::where('district', $district)->value('id'),
            'description' => 'Demo listing: '.$title.'. Please read the dates and pay before applying.',
            'pay_rate' => $pay,
            'pay_type' => $payType,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays($days - 1)->toDateString(),
        ]);
    }
}
