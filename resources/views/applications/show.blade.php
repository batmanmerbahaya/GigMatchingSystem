@extends('layouts.app')

@section('title', 'Job Tracker')
@section('page-title', 'Job Tracker')

@section('content')
    @php
        use App\Enums\ApplicationStage;
        $worker = $application->worker;
        $employer = $job->employer;
        // "Submit work" for online jobs, "Mark the job as done" for in-person jobs.
        $words = \App\Support\WorkWording::for($job);
    @endphp

    <div class="stack">
        {{-- UC18 Track Job Progress --}}
        <div class="panel">
            <div class="panel-header">
                <div class="title-cell">
                    <x-cat-tile :category="$job->category" />
                    <div class="title-cell-text">
                        <h2 class="panel-title">{{ $job->title }}</h2>
                        <p class="panel-subtitle">
                            {{ $employer->displayName() }} &middot; {{ $job->dateRangeLabel() }} &middot; {{ $job->location->label() }}
                        </p>
                    </div>
                </div>
                <div class="panel-tags">
                    <x-pay :job="$job" />
                    <x-badge :status="$application->stage" />
                </div>
            </div>
            <div class="panel-body">
                @include('applications.partials.tracker')

                @if (filled($application->message))
                    <h3 class="detail-section-title">Message from {{ $worker->name }}</h3>
                    <p class="detail-text text-prewrap">{{ $application->message }}</p>
                @endif

                <div class="form-actions">
                    @if ($isWorker)
                        @can('view', $job)
                            <a href="{{ route('worker.jobs.show', $job) }}" class="btn btn-outline btn-sm" data-skeleton="detail">Job details</a>
                        @endcan
                    @else
                        <a href="{{ route('employer.jobs.show', $job) }}" class="btn btn-outline btn-sm" data-skeleton="detail">Job and applicants</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Next step for whoever is viewing. --}}
        @include('applications.partials.actions')

        {{-- Both parties, with phones only after acceptance. --}}
        <div class="party-grid">
            <div class="panel">
                <div class="panel-body">
                    <p class="party-role">Gig Worker</p>
                    <div class="poster-row">
                        <x-avatar :user="$worker" size="md" />
                        <p class="party-name">{{ $worker->name }}</p>
                    </div>
                    <x-rating :user="$worker" />
                    @if ($application->isContract())
                        <p class="party-phone">Phone: {{ $worker->phone_number ?: 'Not provided' }}</p>
                    @endif
                    @if (filled($worker->gigWorker?->bio))
                        <p class="text-muted text-small text-prewrap">{{ $worker->gigWorker->bio }}</p>
                    @endif
                </div>
            </div>
            <div class="panel">
                <div class="panel-body">
                    <p class="party-role">Contract Entity</p>
                    <div class="poster-row">
                        <x-avatar :user="$employer" size="md" />
                        <p class="party-name">{{ $employer->displayName() }}</p>
                    </div>
                    @if ($employer->displayName() !== $employer->name)
                        <p class="text-muted text-small">Contact: {{ $employer->name }}</p>
                    @endif
                    <x-rating :user="$employer" />
                    @if ($application->isContract())
                        <p class="party-phone">Phone: {{ $employer->phone_number ?: 'Not provided' }}</p>
                    @endif
                </div>
            </div>
        </div>
        @unless ($application->isContract())
            <p class="text-muted text-small">Phone numbers are shown to both of you after the worker is accepted.</p>
        @endunless

        @if ($application->isContract())
            @include('work.partials.section')
            @include('applications.partials.thread')
        @endif

        @if ($application->isAt(ApplicationStage::Confirmed, ApplicationStage::Rated))
            @include('applications.partials.feedback')
        @endif
    </div>
@endsection
