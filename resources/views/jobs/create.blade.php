@extends('layouts.app')

@section('title', 'Post a Job')
@section('page-title', 'Post a Job')

@section('content')
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">New job listing</h2>
                <p class="panel-subtitle">
                    You can edit the listing until the first person applies. Phone numbers are never shown on listings.
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('employer.jobs.store') }}" class="panel-body">
            @csrf
            @include('jobs.partials.form-fields', ['job' => null])
            <div class="form-actions">
                <a href="{{ route('employer.jobs.index') }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Post job</button>
            </div>
        </form>
    </div>
@endsection
