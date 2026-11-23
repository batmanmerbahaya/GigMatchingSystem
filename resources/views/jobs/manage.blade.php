@extends('layouts.app')

@section('title', $job->title)
@section('page-title', 'Posted Job')

@section('content')
    <div class="stack">
        @if ($job->isHidden())
            <div class="alert alert-warning" role="status">
                This listing was hidden after several reports. It no longer appears in search,
                and its applicants cannot be accepted. You can close it and post a new one.
            </div>
        @endif

        {{-- UC10 View Posted Job --}}
        <div class="panel">
            <div class="panel-header">
                <div class="title-cell">
                    <x-cat-tile :category="$job->category" />
                    <div class="title-cell-text">
                        <h2 class="panel-title">{{ $job->title }}</h2>
                        <p class="gig-card-category">{{ $job->category->label() }}</p>
                    </div>
                </div>
                <x-badge :status="$job->status" />
            </div>
            <div class="panel-body">
                @include('jobs.partials.details', ['job' => $job])

                <div class="form-actions">
                    @can('update', $job)
                        <a href="{{ route('employer.jobs.edit', $job) }}" class="btn btn-outline">Edit job</a>
                    @elseif ($job->isOpen())
                        <span class="text-muted text-small">Editing is locked because someone has applied. Close the job and post a new one to change it.</span>
                    @endcan

                    {{-- UC12 Close Job --}}
                    @can('close', $job)
                        <form method="POST" action="{{ route('employer.jobs.close', $job) }}"
                              data-confirm="Close this job? Applicants still waiting will be cancelled and emailed. This cannot be undone."
                              data-confirm-ok="Yes, close job" data-confirm-tone="danger">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-danger">Close job</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>

        @if ($contract)
            <div class="alert alert-info alert-with-action">
                <span>
                    You accepted <strong>{{ $contract->worker->name }}</strong>.
                    Stage: <x-badge :status="$contract->stage" />
                </span>
                <a href="{{ route('applications.show', $contract) }}" class="btn btn-primary btn-sm" data-skeleton="detail">Open job tracker</a>
            </div>
        @endif

        {{-- UC13 Verify Applicants --}}
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Applicants</h2>
                    <p class="panel-subtitle">Accepting one applicant rejects the others automatically.</p>
                </div>
                <span class="section-count">{{ $applications->count() }}</span>
            </div>

            @if ($applications->isEmpty())
                <div class="panel-body">
                    <p class="text-muted">No one has applied yet.</p>
                </div>
            @else
                <div class="table-wrap table-flat">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Rating</th>
                                <th>Message</th>
                                <th>Applied</th>
                                <th>Stage</th>
                                <th class="table-actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($applications as $application)
                                <tr>
                                    <td>
                                        <div class="title-cell">
                                            <x-avatar :user="$application->worker" />
                                            <div class="title-cell-text">
                                                <strong>{{ $application->worker->name }}</strong>
                                                @if (filled($application->worker->gigWorker?->bio))
                                                    <span class="text-muted text-small">{{ Str::limit($application->worker->gigWorker->bio, 120) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td><x-rating :user="$application->worker" /></td>
                                    <td class="text-prewrap">{{ $application->message ?: '-' }}</td>
                                    <td>{{ $application->applied_at->format('j M Y') }}</td>
                                    <td><x-badge :status="$application->stage" /></td>
                                    <td class="table-actions">
                                        @can('accept', $application)
                                            <form method="POST" action="{{ route('employer.applications.accept', $application) }}"
                                                  data-confirm="Accept {{ $application->worker->name }}? All other applicants will be rejected."
                                                  data-confirm-ok="Yes, accept">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                                            </form>
                                        @endcan
                                        @can('reject', $application)
                                            <form method="POST" action="{{ route('employer.applications.reject', $application) }}"
                                                  data-confirm="Reject {{ $application->worker->name }}?"
                                                  data-confirm-ok="Yes, reject" data-confirm-tone="danger">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline btn-sm">Reject</button>
                                            </form>
                                        @endcan
                                        @if ($application->isContract())
                                            <a href="{{ route('applications.show', $application) }}" class="btn btn-outline btn-sm">Tracker</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
