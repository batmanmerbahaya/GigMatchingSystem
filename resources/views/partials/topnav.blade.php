{{-- Navigation bar across the top. Profile and Log out live in the account menu under the user's name. --}}
@php
    $user = auth()->user();
    $links = $user->isContractEntity()
        ? [
            ['route' => 'employer.jobs.index', 'active' => ['employer.jobs.index', 'employer.jobs.show', 'employer.jobs.edit'], 'icon' => 'briefcase', 'label' => 'My Postings', 'skeleton' => 'table'],
            ['route' => 'employer.jobs.create', 'active' => ['employer.jobs.create'], 'icon' => 'plus', 'label' => 'Post a Job', 'skeleton' => 'form'],
            ['route' => 'employer.history', 'active' => ['employer.history'], 'icon' => 'history', 'label' => 'History', 'skeleton' => 'table'],
        ]
        : [
            ['route' => 'worker.jobs.index', 'active' => ['worker.jobs.*'], 'icon' => 'search', 'label' => 'Find Jobs', 'skeleton' => 'jobs'],
            ['route' => 'worker.applications.index', 'active' => ['worker.applications.*', 'applications.show'], 'icon' => 'file', 'label' => 'My Applications', 'skeleton' => 'list'],
            ['route' => 'worker.history', 'active' => ['worker.history'], 'icon' => 'history', 'label' => 'History', 'skeleton' => 'table'],
        ];
    $firstName = \Illuminate\Support\Str::before($user->name, ' ') ?: $user->name;
@endphp

<header class="topnav" data-topnav>
    <div class="topnav-inner">
        <a href="{{ route('dashboard') }}" class="brand">
            <x-logo />
            <span class="brand-name">{{ config('app.name') }}</span>
        </a>

        <nav class="tnav" id="main-nav" aria-label="Main navigation">
            @foreach ($links as $link)
                @php $active = request()->routeIs(...$link['active']); @endphp
                <a href="{{ route($link['route']) }}" @class(['on' => $active]) @if ($active) aria-current="page" @endif
                   data-skeleton="{{ $link['skeleton'] }}">
                    <x-icon :name="$link['icon']" />{{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="account">
            <button type="button" class="who-btn" data-account-toggle aria-expanded="false" aria-controls="account-menu"
                    aria-label="Account menu for {{ $user->displayName() }}">
                <x-avatar :user="$user" />
                <span class="who-text"><b>{{ $user->displayName() }}</b><span>{{ $user->role->label() }}</span></span>
                <x-icon name="chevron" class="chev" />
            </button>

            <div class="acct-menu" id="account-menu" data-account-menu hidden>
                <div class="acct-head">
                    <x-avatar :user="$user" size="lg" />
                    <span class="acct-hi">Hi, {{ $firstName }}!</span>
                    <span class="acct-email">{{ $user->email }}</span>
                    <span class="role-tag">{{ $user->role->label() }}</span>
                </div>
                <a href="{{ route('profile.edit') }}" class="btn btn-outline btn-block acct-manage" data-skeleton="form">
                    <x-icon name="user" />Manage your profile
                </a>
                <form method="POST" action="{{ route('logout') }}" class="acct-list">
                    @csrf
                    <button type="submit" class="acct-item danger"><x-icon name="logout" />Log out</button>
                </form>
            </div>
        </div>

        <button type="button" class="menu-btn" data-menu-toggle aria-expanded="false" aria-controls="main-nav" aria-label="Open navigation">
            <x-icon name="menu" />
        </button>
    </div>
</header>
