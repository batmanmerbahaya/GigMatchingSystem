<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStage;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Enums\PayType;
use App\Enums\ReportReason;
use App\Http\Requests\JobSearchRequest;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Models\Job;
use App\Models\Location;
use App\Notifications\ApplicationCancelled;
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class JobController extends Controller
{
    /**
     * UC01 Search Job: keyword, faceted filters and sort. Only open jobs
     * appear. There is no ranking or recommendation.
     */
    public function index(JobSearchRequest $request): View
    {
        $filters = $request->validated();
        $payType = filled($filters['pay_type'] ?? null) ? PayType::from($filters['pay_type']) : null;

        // Every filter except the pay range and the category.
        $base = Job::query()
            ->open()
            ->when($filters['q'] ?? null, function (Builder $query, string $keyword) {
                $like = '%'.addcslashes($keyword, '%_\\').'%';
                $query->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like));
            })
            ->when($filters['state'] ?? null, fn (Builder $query, string $state) => $query
                ->whereHas('location', fn (Builder $location) => $location->where('state', $state)))
            ->when($filters['location_id'] ?? null, fn (Builder $query, $id) => $query->where('location_id', $id))
            ->when($payType, fn (Builder $query, PayType $type) => $query->where('pay_type', $type->value))
            // Date facet: jobs whose period overlaps the chosen range.
            ->when($filters['date_from'] ?? null, fn (Builder $query, $from) => $query->whereDate('end_date', '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $to) => $query->whereDate('start_date', '<=', $to));

        // Pay facet: the pay of every job that matches the other filters, drawn as dots on the slider.
        $payAmounts = $payType
            ? (clone $base)
                ->when($filters['category'] ?? null, fn (Builder $query, $category) => $query->where('category', $category))
                ->orderBy('pay_rate')
                ->limit(500)
                ->pluck('pay_rate')
                ->map(fn ($amount) => (float) $amount)
            : collect();

        $query = (clone $base)
            ->when($filters['pay_min'] ?? null, fn (Builder $query, $min) => $query->where('pay_rate', '>=', $min))
            ->when($filters['pay_max'] ?? null, fn (Builder $query, $max) => $query->where('pay_rate', '<=', $max));

        // Facet counts per category, given every other filter.
        $categoryCounts = (clone $query)->toBase()
            ->select('category')
            ->selectRaw('count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $query->when($filters['category'] ?? null, fn (Builder $query, $category) => $query->where('category', $category));

        match ($filters['sort'] ?? 'newest') {
            'pay_high' => $query->orderByDesc('pay_rate'),
            'pay_low' => $query->orderBy('pay_rate'),
            'earliest' => $query->orderBy('start_date'),
            default => $query->orderByDesc('created_at'),
        };

        $jobs = $query->orderByDesc('id')
            ->with('location', 'employer.contractEntity')
            ->paginate(9)
            ->withQueryString();

        // The worker's own stage on the listed jobs, shown as a badge on each card.
        $myStages = $request->user()->applications()
            ->whereIn('job_id', $jobs->getCollection()->pluck('id'))
            ->pluck('stage', 'job_id');

        return view('jobs.index', [
            'jobs' => $jobs,
            'filters' => $filters,
            'categories' => JobCategory::cases(),
            'categoryCounts' => $categoryCounts,
            'locations' => Location::groupedByState(),
            'sorts' => JobSearchRequest::SORTS,
            'myStages' => $myStages,
            'payTypes' => PayType::cases(),
            'payType' => $payType,
            'payRange' => $payType ? $this->payRange($payType) : null,
            'payAmounts' => $payAmounts,
        ]);
    }

    /**
     * The pay slider's ends for one pay type: the lowest and highest pay of its
     * open jobs, widened a little and rounded to friendly numbers.
     *
     * @return array{floor: int, ceil: int, step: int}
     */
    private function payRange(PayType $type): array
    {
        $stats = Job::query()->open()->where('pay_type', $type->value)
            ->toBase()
            ->selectRaw('min(pay_rate) as low, max(pay_rate) as high')
            ->first();

        $round = $type->sliderRounding();
        [$floor, $ceil] = $stats?->low === null
            ? $type->defaultRange()
            : [
                (int) (floor(((float) $stats->low) * 0.8 / $round) * $round),
                (int) (ceil(((float) $stats->high) * 1.2 / $round) * $round),
            ];

        if ($ceil <= $floor) {
            $ceil = $floor + $round;
        }

        return ['floor' => $floor, 'ceil' => $ceil, 'step' => $type->sliderStep()];
    }

    /**
     * UC02 View Job Details, with Apply (UC03) and Report Listing (UC08).
     * Phone numbers are never shown here.
     */
    public function show(Request $request, Job $job): View
    {
        Gate::authorize('view', $job);

        $job->load('location', 'employer.contractEntity');

        return view('jobs.show', [
            'job' => $job,
            'application' => $job->applications()->where('user_id', $request->user()->id)->first(),
            'hasReported' => $job->reports()->where('user_id', $request->user()->id)->exists(),
            'reasons' => ReportReason::cases(),
        ]);
    }

    /**
     * UC10 View Posted Job: the Contract Entity's listings that are not closed.
     */
    public function postings(Request $request): View
    {
        $jobs = $request->user()->jobs()
            ->where('status', '!=', JobStatus::Closed->value)
            ->with('location', 'contract.worker')
            ->withCount(['applications as waiting_count' => fn (Builder $query) => $query
                ->where('stage', ApplicationStage::Applied->value)])
            ->latest()
            ->paginate(10);

        return view('jobs.postings', ['jobs' => $jobs]);
    }

    public function create(): View
    {
        Gate::authorize('create', Job::class);

        return view('jobs.create', $this->formOptions());
    }

    /**
     * UC09 Post Job.
     */
    public function store(StoreJobRequest $request): RedirectResponse
    {
        $job = new Job($request->validated());
        $job->user_id = $request->user()->id;
        $job->status = JobStatus::Open;
        $job->save();

        return redirect()
            ->route('employer.jobs.show', $job)
            ->with('status', 'Job posted. It now appears in search.');
    }

    /**
     * UC10 View Posted Job (details) and UC13 Verify Applicants.
     */
    public function manage(Job $job): View
    {
        Gate::authorize('manage', $job);

        $job->load('location');

        $applications = $job->applications()
            ->with('worker.gigWorker')
            ->orderBy('applied_at')
            ->get();

        return view('jobs.manage', [
            'job' => $job,
            'applications' => $applications,
            'contract' => $applications->first(fn ($application) => $application->isContract()),
        ]);
    }

    /**
     * UC11 Edit Posted Job, only until the first application.
     */
    public function edit(Job $job): View
    {
        Gate::authorize('update', $job);

        return view('jobs.edit', ['job' => $job] + $this->formOptions());
    }

    public function update(UpdateJobRequest $request, Job $job): RedirectResponse
    {
        $job->fill($request->validated());
        $job->save();

        return redirect()
            ->route('employer.jobs.show', $job)
            ->with('status', 'Job updated.');
    }

    /**
     * UC12 Close Job. Closing replaces deleting: the job is kept, and every
     * application still at Applied is cancelled and emailed.
     */
    public function close(Job $job): RedirectResponse
    {
        Gate::authorize('close', $job);

        $cancelled = DB::transaction(fn () => $job->closeListing());

        foreach ($cancelled as $application) {
            Notifier::send($application->worker, new ApplicationCancelled($application));
        }

        return redirect()
            ->route('employer.jobs.show', $job)
            ->with('status', 'Job closed.'.($cancelled->isNotEmpty() ? ' Waiting applicants were notified.' : ''));
    }

    /**
     * UC17 View History for a Contract Entity: closed jobs.
     */
    public function history(Request $request): View
    {
        $jobs = $request->user()->jobs()
            ->where('status', JobStatus::Closed->value)
            ->with('location', 'contract.worker')
            ->latest('updated_at')
            ->paginate(10);

        return view('jobs.history', ['jobs' => $jobs]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => JobCategory::cases(),
            'payTypes' => PayType::cases(),
            'locations' => Location::groupedByState(),
            'maxDays' => config('gigs.max_job_days'),
        ];
    }
}
