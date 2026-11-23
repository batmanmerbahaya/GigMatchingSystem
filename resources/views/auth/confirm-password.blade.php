@extends('layouts.guest')

@section('title', 'Confirm password')

@section('content')
    <h1 class="auth-title">Confirm your password</h1>
    <p class="auth-subtitle">
        This is a secure area of the application. Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
        @csrf

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" class="form-input @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block">Confirm</button>
    </form>
@endsection
