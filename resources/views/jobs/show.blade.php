@extends('layouts.app')

@section('title', $job->title)

@section('content')
    <h1 class="visually-hidden">Job details: {{ $job->title }}</h1>
    <a href="{{ route('worker.jobs.index') }}" class="back-link" data-skeleton="jobs"><x-icon name="arrow-left" />Back to jobs</a>

    <div class="detail-page">
        @include('jobs.partials.job-detail')
    </div>
@endsection
