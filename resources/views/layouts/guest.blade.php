<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Welcome') — {{ config('app.name') }}</title>
    @include('partials.head-icons')
    <link rel="preload" href="{{ asset('fonts/plus-jakarta-sans-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</head>
<body class="auth-body">
    <div class="auth-page">
        <a href="{{ route('home') }}" class="auth-brand">
            <x-logo />
            <span class="brand-name">{{ config('app.name') }}</span>
        </a>

        <div class="auth-card">
            @yield('content')
        </div>
    </div>
</body>
</html>
