@extends('layouts.guest')

@section('title', 'Sign up')

@section('content')
    @php
        $selectedRole = old('role', 'gig_worker');
    @endphp

    <h1 class="auth-title">Create your account</h1>
    <p class="auth-subtitle">Each account has one role. You cannot change it later.</p>

    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf

        <fieldset class="role-picker">
            <legend class="form-label">I want to&hellip;</legend>

            <label class="role-option {{ $selectedRole === 'gig_worker' ? 'is-selected' : '' }}">
                <input type="radio" name="role" value="gig_worker" class="role-radio" data-role-radio
                       @checked($selectedRole === 'gig_worker')>
                <span class="role-option-title">Find gigs</span>
                <span class="role-option-text">I am a Gig Worker looking for short-term jobs.</span>
            </label>

            <label class="role-option {{ $selectedRole === 'contract_entity' ? 'is-selected' : '' }}">
                <input type="radio" name="role" value="contract_entity" class="role-radio" data-role-radio
                       @checked($selectedRole === 'contract_entity')>
                <span class="role-option-title">Post gigs</span>
                <span class="role-option-text">I am a Contract Entity who needs work done.</span>
            </label>
        </fieldset>
        @error('role')
            <p class="form-error">{{ $message }}</p>
        @enderror

        <div class="form-group">
            <label for="name" class="form-label">Full name</label>
            <input id="name" name="name" type="text" class="form-input @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Contract Entity only: shown or hidden by app.js when the role changes. --}}
        <div class="role-section" data-role-section="contract_entity" @if ($selectedRole !== 'contract_entity') hidden @endif>
            <div class="form-group">
                <span class="form-label">Registering as</span>
                <div class="radio-row">
                    @foreach ($entityTypes as $type)
                        <label class="form-check">
                            <input type="radio" name="entity_type" value="{{ $type->value }}"
                                   @checked(old('entity_type', 'individual') === $type->value)>
                            {{ $type->label() }}
                        </label>
                    @endforeach
                </div>
                @error('entity_type')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label for="organisation_name" class="form-label">Organisation name <span class="form-hint">(required for a business)</span></label>
                <input id="organisation_name" name="organisation_name" type="text" maxlength="150"
                       class="form-input @error('organisation_name') is-invalid @enderror"
                       value="{{ old('organisation_name') }}">
                @error('organisation_name')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-input @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autocomplete="username">
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="phone_number" class="form-label">Phone number</label>
            <input id="phone_number" name="phone_number" type="tel" maxlength="20" placeholder="012-3456789"
                   class="form-input @error('phone_number') is-invalid @enderror"
                   value="{{ old('phone_number') }}" required autocomplete="tel">
            <p class="form-hint">Never shown on listings. Shared only with the other party after a worker is accepted.</p>
            @error('phone_number')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" class="form-input @error('password') is-invalid @enderror"
                   required autocomplete="new-password">
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-input"
                   required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Create account</button>

        <p class="auth-switch">
            Already registered?
            <a class="auth-link" href="{{ route('login') }}">Log in</a>
        </p>
    </form>
@endsection
