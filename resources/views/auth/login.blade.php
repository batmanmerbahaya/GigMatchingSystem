@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Log in to your account to continue.</p>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-input @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" class="form-input @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-row-between">
            <label class="form-check">
                <input type="checkbox" name="remember" class="form-checkbox">
                <span>Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-block">Log in</button>

        <p class="auth-switch">
            No account yet?
            <a class="auth-link" href="{{ route('register') }}">Sign up</a>
        </p>
    </form>
@endsection
