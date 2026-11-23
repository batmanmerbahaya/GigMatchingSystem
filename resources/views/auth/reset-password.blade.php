@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <h1 class="auth-title">Reset your password</h1>

    <form method="POST" action="{{ route('password.store') }}" class="auth-form">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-input @error('email') is-invalid @enderror"
                   value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">New password</label>
            <input id="password" name="password" type="password" class="form-input @error('password') is-invalid @enderror"
                   required autocomplete="new-password">
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-input"
                   required autocomplete="new-password">
            @error('password_confirmation')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block">Reset password</button>
    </form>
@endsection
