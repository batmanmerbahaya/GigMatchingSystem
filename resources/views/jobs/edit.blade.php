@extends('layouts.app')

@section('title', 'Edit Job')
@section('page-title', 'Edit Job')

@section('content')
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Edit "{{ $job->title }}"</h2>
                <p class="panel-subtitle">Editing is only possible until the first application arrives.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('employer.jobs.update', $job) }}" class="panel-body">
            @csrf
            @method('PUT')
            @include('jobs.partials.form-fields', ['job' => $job])
            <div class="form-actions">
                <a href="{{ route('employer.jobs.show', $job) }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </div>
@endsection
