@extends('layouts.app')

@section('title', 'My Applications')
@section('page-title', 'My Applications')
@section('page-subtitle', 'Jobs you have applied for that are still in progress. Finished and ended ones move to History.')

@section('content')
    @php
        use App\Enums\ApplicationStage;
        use App\Enums\SubmissionStatus;

        $flow = ApplicationStage::mainFlow();
        $tiles = [
            ['stage' => ApplicationStage::Applied, 'icon' => 'clock', 'label' => 'Waiting for a reply'],
            ['stage' => ApplicationStage::Accepted, 'icon' => 'briefcase', 'label' => 'Working on it'],
            ['stage' => ApplicationStage::Completed, 'icon' => 'wallet', 'label' => 'Waiting for payment'],
        ];
    @endphp

    <div class="sum-row">
        @foreach ($tiles as $tile)
            <div class="sum-tile">
                <span class="sum-ic s-{{ $tile['stage']->value }}" aria-hidden="true"><x-icon :name="$tile['icon']" /></span>
                <div class="sum-text">
                    <b>{{ (int) ($stageCounts[$tile['stage']->value] ?? 0) }}</b>
                    <span>{{ $tile['label'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    @if ($applications->isEmpty())
        <div class="empty-state">
            <span class="tile t-all" aria-hidden="true"><x-icon name="file" /></span>
            <h3 class="empty-state-title">No applications in progress</h3>
            <p class="empty-state-text">
                Jobs you apply for from Find Jobs appear here.
                Finished and ended applications are in History.
            </p>
            <a href="{{ route('worker.jobs.index') }}" class="btn btn-primary btn-sm" data-skeleton="jobs">Find a gig</a>
        </div>
    @else
        <div class="apps-list">
            @foreach ($applications as $application)
                @php
                    $job = $application->job;
                    $employerName = $job->employer->displayName();
                    $step = array_search($application->stage, $flow, true) + 1;
                    $latest = $application->submissions->first();

                    $words = \App\Support\WorkWording::for($job);

                    $next = match ($application->stage) {
                        ApplicationStage::Applied => "Waiting for {$employerName} to choose a worker. You can withdraw until they accept you.",
                        ApplicationStage::Accepted => match (true) {
                            $application->hasApprovedWork() => $words->nextApproved($employerName),
                            $latest?->status === SubmissionStatus::ChangesRequested => $words->nextSentBack($employerName),
                            $latest?->status === SubmissionStatus::Pending => $words->nextWaiting($employerName),
                            default => $words->nextStart(),
                        },
                        ApplicationStage::Completed => "You marked this job as finished. Waiting for {$employerName} to confirm payment.",
                        default => '',
                    };
                @endphp

                <article class="app-card">
                    <div class="app-top">
                        <x-cat-tile :category="$job->category" />
                        <div class="app-main">
                            <div class="app-title-row">
                                <h2 class="app-title">
                                    <a href="{{ route('applications.show', $application) }}" data-skeleton="detail">{{ $job->title }}</a>
                                </h2>
                                <x-badge :status="$application->stage" />
                            </div>
                            <span class="app-sub">{{ $employerName }} &middot; {{ $job->location->label() }}</span>
                            <div class="app-meta">
                                <x-pay :job="$job" />
                                <span class="app-meta-item"><x-icon name="calendar" />{{ $job->dateRangeLabel() }}</span>
                                <span class="app-meta-item"><x-icon name="clock" />Applied {{ $application->applied_at->format('j M Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="app-progress">
                        <div class="steps-bar" aria-hidden="true">
                            @foreach ($flow as $index => $flowStage)
                                <span @class(['on' => $index < $step])></span>
                            @endforeach
                        </div>
                        <span class="steps-label">Step {{ $step }} of {{ count($flow) }} &middot; {{ $application->stage->label() }}</span>
                    </div>

                    <div class="app-bottom">
                        <p class="app-next"><x-icon name="info" /><span>{{ $next }}</span></p>
                        <div class="app-actions">
                            @can('withdraw', $application)
                                <form method="POST" action="{{ route('worker.applications.withdraw', $application) }}"
                                      data-confirm="Withdraw this application? You cannot apply for the same job again."
                                      data-confirm-ok="Yes, withdraw" data-confirm-tone="danger">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-danger btn-sm">Withdraw</button>
                                </form>
                            @endcan
                            <a href="{{ route('applications.show', $application) }}" class="btn btn-soft btn-sm" data-skeleton="detail">
                                Open job tracker <x-icon name="arrow" />
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{ $applications->links() }}
    @endif
@endsection
