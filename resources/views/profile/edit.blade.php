@extends('layouts.app')

@section('title', 'Profile')
@section('page-title', 'Profile')

@section('content')
    <div class="settings-stack">

        <div class="panel">
            <div class="panel-body profile-summary">
                <div>
                    <p class="party-role">{{ $user->role->label() }}</p>
                    <p class="party-name">{{ $user->displayName() }}</p>
                </div>
                <x-rating :user="$user" />
            </div>
        </div>

        {{-- UC16 Manage Profile --}}
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Profile information</h2>
                    <p class="panel-subtitle">Your phone number is only shown to the other party after a worker is accepted.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('profile.update') }}" class="panel-body">
                @csrf
                @method('PATCH')

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name" class="form-label">Full name</label>
                        <input id="name" name="name" type="text" class="form-input @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required autocomplete="name">
                        @error('name')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone_number" class="form-label">Phone number</label>
                        <input id="phone_number" name="phone_number" type="tel" maxlength="20"
                               class="form-input @error('phone_number') is-invalid @enderror"
                               value="{{ old('phone_number', $user->phone_number) }}" required autocomplete="tel">
                        @error('phone_number')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" class="form-input @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email) }}" required autocomplete="username">
                    <p class="form-hint">
                        {{ $user->hasVerifiedEmail() ? 'Verified.' : 'Not verified yet.' }}
                        Changing your email means you need to verify the new address.
                    </p>
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                @if ($user->isGigWorker())
                    <div class="form-group">
                        <label for="bio" class="form-label">Bio <span class="form-hint">(shown to employers when you apply)</span></label>
                        <textarea id="bio" name="bio" rows="4" maxlength="1000"
                                  class="form-textarea @error('bio') is-invalid @enderror">{{ old('bio', $user->gigWorker?->bio) }}</textarea>
                        @error('bio')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="entity_type" class="form-label">Account type</label>
                            <select id="entity_type" name="entity_type" class="form-select @error('entity_type') is-invalid @enderror">
                                @foreach ($entityTypes as $type)
                                    <option value="{{ $type->value }}" @selected(old('entity_type', $user->contractEntity?->entity_type?->value) === $type->value)>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('entity_type')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="organisation_name" class="form-label">Organisation name <span class="form-hint">(businesses)</span></label>
                            <input id="organisation_name" name="organisation_name" type="text" maxlength="150"
                                   class="form-input @error('organisation_name') is-invalid @enderror"
                                   value="{{ old('organisation_name', $user->contractEntity?->organisation_name) }}">
                            @error('organisation_name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save profile</button>
                </div>
            </form>
        </div>

        {{-- Update password --}}
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Update password</h2>
                    <p class="panel-subtitle">Use a long, random password to keep your account secure.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('password.update') }}" class="panel-body">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="current_password" class="form-label">Current password</label>
                    <input id="current_password" name="current_password" type="password"
                           class="form-input @error('current_password', 'updatePassword') is-invalid @enderror"
                           autocomplete="current-password">
                    @error('current_password', 'updatePassword')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">New password</label>
                    <input id="password" name="password" type="password"
                           class="form-input @error('password', 'updatePassword') is-invalid @enderror"
                           autocomplete="new-password">
                    @error('password', 'updatePassword')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                           class="form-input" autocomplete="new-password">
                    @error('password_confirmation', 'updatePassword')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update password</button>
                </div>
            </form>
        </div>

        {{-- UC21 Delete Account --}}
        <div class="panel panel-danger">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Delete account</h2>
                    <p class="panel-subtitle">
                        Your name, email, phone number and profile details are removed. Your open jobs are closed and
                        pending applications cancelled. Past jobs, submissions, comments and reviews are kept, under
                        the name "Deleted account", so the other party keeps their records.
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('profile.destroy') }}" class="panel-body"
                  data-confirm="Delete your account? You will not be able to log in again.">
                @csrf
                @method('DELETE')

                @if ($hasUnfinishedWork)
                    <div class="alert alert-warning">
                        You have accepted work that is not yet confirmed. Finish it and get payment confirmed before deleting your account.
                    </div>
                @endif

                <div class="form-group">
                    <label for="delete_password" class="form-label">Confirm with your password</label>
                    <input id="delete_password" name="password" type="password"
                           class="form-input @error('password', 'userDeletion') is-invalid @enderror"
                           autocomplete="current-password" required>
                    @error('password', 'userDeletion')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-danger" @disabled($hasUnfinishedWork)>Delete account</button>
                </div>
            </form>
        </div>

    </div>
@endsection
