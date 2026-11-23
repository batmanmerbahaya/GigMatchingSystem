@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h1 class="auth-title">Forgot your password?</h1>
    <p class="auth-subtitle">
        No problem — enter your email address and we will send you a link to reset it.
    </p>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf

        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-input @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block">Email password reset link</button>

        <p class="auth-switch">
            <a class="auth-link" href="{{ route('login') }}">Back to log in</a>
        </p>
    </form>
@endsection
