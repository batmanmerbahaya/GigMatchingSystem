<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @include('partials.head-icons')
    <link rel="preload" href="{{ asset('fonts/plus-jakarta-sans-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="landing-body">
    <header class="landing-header">
        <div class="landing-header-inner">
            <a href="{{ route('home') }}" class="landing-brand">
                <x-logo />
                <span class="brand-name">{{ config('app.name') }}</span>
            </a>
            <nav class="landing-nav">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Go to the app</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign up</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="landing-hero">
        <div class="landing-text">
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            @if ($openCount > 0)
                <span class="landing-pill"><span class="new">New</span>{{ $openCount }} {{ Str::plural('gig', $openCount) }} open across Malaysia</span>
            @endif
            <h1 class="landing-title">Short-term gigs, <span class="hl">all in one place.</span></h1>
            <p class="landing-subtitle">
                Stop chasing gigs across WhatsApp, Telegram and Facebook groups. Search one listing,
                apply, track every stage of the job and keep your work and messages together.
            </p>
            <div class="landing-actions">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Get started, it's free</a>
                <a href="{{ route('login') }}" class="btn btn-outline btn-lg">I already have an account</a>
            </div>
            <ul class="trust">
                <li><x-icon name="check-circle" />Jobs of up to {{ config('gigs.max_job_days') }} days</li>
                <li><x-icon name="check-circle" />Phone numbers stay private until you're accepted</li>
                <li><x-icon name="check-circle" />Fake listings can be reported</li>
            </ul>
        </div>

        @if ($sampleJobs->isNotEmpty())
            <div class="board" aria-hidden="true">
                @foreach ($sampleJobs as $job)
                    <div class="gig">
                        <div class="gig-head">
                            <x-cat-tile :category="$job->category" />
                            <div class="gig-head-text">
                                <span class="gig-title">{{ $job->title }}</span>
                                <span class="gig-cat">{{ $job->category->label() }} &middot; {{ $job->location->label() }}</span>
                            </div>
                        </div>
                        <div class="gig-pay-row">
                            <x-pay :job="$job" />
                            @if ($job->created_at->gt(now()->subDay()))
                                <span class="new">New</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </main>

    <section class="landing-features" aria-label="What you can do">
        <div class="feature-card">
            <span class="tile t-tutor" aria-hidden="true"><x-icon name="search" /></span>
            <h2 class="feature-title">For Gig Workers</h2>
            <p class="feature-text">Search jobs by keyword, category, location, pay and date. Apply, submit your work and get rated.</p>
        </div>
        <div class="feature-card">
            <span class="tile t-other" aria-hidden="true"><x-icon name="briefcase" /></span>
            <h2 class="feature-title">For Contract Entities</h2>
            <p class="feature-text">Post a job of up to {{ config('gigs.max_job_days') }} days, choose one worker, review the submitted work and confirm payment.</p>
        </div>
        <div class="feature-card">
            <span class="tile t-food" aria-hidden="true"><x-icon name="shield" /></span>
            <h2 class="feature-title">Safer together</h2>
            <p class="feature-text">Phone numbers stay private until a worker is accepted, and the community can report fake listings.</p>
        </div>
    </section>

    <footer class="landing-footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
    </footer>
</body>
</html>
