<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @include('partials.head-icons')
    <link rel="preload" href="{{ asset('fonts/plus-jakarta-sans-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    {{-- ?v= changes whenever the file changes, so browsers never use an old cached copy. --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</head>
<body class="app-body">
    @include('partials.topnav')

    <main class="page-content" id="main" data-page>
        @php
            $statusMessages = [
                'verification-link-sent' => 'A new verification link has been sent to your email address.',
                'profile-updated' => 'Profile saved.',
                'password-updated' => 'Password updated.',
            ];
        @endphp

        {{-- Verify-later: the account works, but posting and applying need a verified email. --}}
        @unless (auth()->user()->hasVerifiedEmail())
            <div class="alert alert-warning alert-with-action" role="status">
                <span>
                    Please verify your email address. You need a verified email to
                    {{ auth()->user()->isGigWorker() ? 'apply for or report jobs' : 'post jobs' }}.
                </span>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">Resend link</button>
                </form>
            </div>
        @endunless

        @if (session('error'))
            <div class="alert alert-error" role="alert">{{ session('error') }}</div>
        @endif

        @hasSection('page-title')
            <div class="page-head">
                <h1 class="page-title">@yield('page-title')</h1>
                @hasSection('page-subtitle')
                    <p class="page-subtitle">@yield('page-subtitle')</p>
                @endif
            </div>
        @endif

        @yield('content')
    </main>

    {{-- Short confirmations appear as a toast and fade away (app.js). --}}
    @if (session('status'))
        <div class="toast" data-flash role="status">
            <x-icon name="check-circle" />
            <span>{{ $statusMessages[session('status')] ?? session('status') }}</span>
        </div>
    @endif

    @include('partials.confirm-dialog')
    @include('partials.skeletons')
</body>
</html>
