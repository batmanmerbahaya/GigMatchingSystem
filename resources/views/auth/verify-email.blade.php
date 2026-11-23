@extends('layouts.guest')

@section('title', 'Verify email')

@section('content')
    <h1 class="auth-title">Verify your email</h1>
    <p class="auth-subtitle">
        You need a verified email address to post a job, apply for a job or report a listing.
        Click the link we emailed to you. If it did not arrive, we can send another.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success" role="status">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="auth-form">
        @csrf
        <button type="submit" class="btn btn-primary btn-block">Resend verification email</button>
    </form>

    <p class="auth-switch">
        <a class="auth-link" href="{{ route('dashboard') }}">Back to the app</a>
    </p>
@endsection
