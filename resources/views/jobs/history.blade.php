@extends('layouts.app')

@section('title', 'History')
@section('page-title', 'History')
@section('page-subtitle', 'Jobs you have closed or finished with payment confirmed.')

@section('content')
    <div class="section-header">
        <h2 class="section-title">Closed jobs</h2>
    </div>

    @if ($jobs->isEmpty())
        <div class="empty-state">
            <span class="tile t-all" aria-hidden="true"><x-icon name="history" /></span>
            <h3 class="empty-state-title">No closed jobs yet</h3>
            <p class="empty-state-text">Jobs appear here after you close them or confirm payment.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Dates</th>
                        <th>Pay</th>
                        <th>Worker</th>
                        <th>Outcome</th>
                        <th class="table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jobs as $job)
                        <tr>
                            <td>
                                <div class="title-cell">
                                    <x-cat-tile :category="$job->category" size="sm" />
                                    <div class="title-cell-text">
                                        <strong>{{ $job->title }}</strong>
                                        <span class="text-muted text-small">{{ $job->location->label() }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $job->dateRangeLabel() }}</td>
                            <td><x-pay :job="$job" /></td>
                            <td>{{ $job->contract?->worker->name ?? 'No worker hired' }}</td>
                            <td>
                                @if ($job->contract)
                                    <x-badge :status="$job->contract->stage" />
                                @else
                                    <x-badge :status="$job->status" />
                                @endif
                            </td>
                            <td class="table-actions">
                                @if ($job->contract)
                                    <a href="{{ route('applications.show', $job->contract) }}" class="btn btn-soft btn-sm" data-skeleton="detail">Job tracker</a>
                                @endif
                                <a href="{{ route('employer.jobs.show', $job) }}" class="btn btn-outline btn-sm" data-skeleton="detail">Details</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $jobs->links() }}
    @endif
@endsection
