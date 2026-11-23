@extends('layouts.app')

@section('title', 'My Postings')
@section('page-title', 'My Postings')
@section('page-subtitle', 'Your open and in-progress listings. Closed jobs move to History.')

@section('content')
    <div class="section-header">
        <h2 class="section-title">Your active listings</h2>
        <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary" data-skeleton="form"><x-icon name="plus" />Post a Job</a>
    </div>

    @if ($jobs->isEmpty())
        <div class="empty-state">
            <span class="tile t-all" aria-hidden="true"><x-icon name="briefcase" /></span>
            <h3 class="empty-state-title">No active listings</h3>
            <p class="empty-state-text">Jobs you post appear here. Closed jobs move to History.</p>
            <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary btn-sm" data-skeleton="form">Post your first job</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Status</th>
                        <th>Dates</th>
                        <th>Pay</th>
                        <th>Waiting applicants</th>
                        <th>Worker</th>
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
                                        <a href="{{ route('employer.jobs.show', $job) }}" class="gig-card-link" data-skeleton="detail">{{ $job->title }}</a>
                                        <span class="text-muted text-small">{{ $job->location->label() }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><x-badge :status="$job->status" /></td>
                            <td>{{ $job->dateRangeLabel() }}</td>
                            <td><x-pay :job="$job" /></td>
                            <td>
                                @if ($job->waiting_count > 0)
                                    <span class="badge badge-applied">{{ $job->waiting_count }} waiting</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td>{{ $job->contract?->worker->name ?? '-' }}</td>
                            <td class="table-actions">
                                <a href="{{ route('employer.jobs.show', $job) }}" class="btn btn-soft btn-sm" data-skeleton="detail">Manage</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $jobs->links() }}
    @endif
@endsection
