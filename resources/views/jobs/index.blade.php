@extends('layouts.app')

@section('title', 'Find Jobs')
@section('page-title', 'Find your next gig')
@section('page-subtitle', 'Short jobs of up to '.config('gigs.max_job_days').' days. Pick a category or search by keyword.')

@section('content')
    @php
        use App\Enums\ApplicationStage;
        use Illuminate\Support\Arr;

        $activeCategory = $filters['category'] ?? null;
        $moreKeys = ['pay_type', 'state', 'location_id', 'pay_min', 'pay_max', 'date_from', 'date_to'];
        $payMin = filled($filters['pay_min'] ?? null) ? (float) $filters['pay_min'] : null;
        $payMax = filled($filters['pay_max'] ?? null) ? (float) $filters['pay_max'] : null;
        $payInRange = $payAmounts->filter(fn ($amount) => ($payMin === null || $amount >= $payMin) && ($payMax === null || $amount <= $payMax))->count();
        $moreOpen = collect($moreKeys)->contains(fn ($key) => filled($filters[$key] ?? null)) || $errors->hasAny($moreKeys);
        // Category links keep every other filter but start again from page 1.
        $baseQuery = array_filter(Arr::except($filters, ['category', 'page']), fn ($value) => filled($value));
        $countLabel = fn (int $count) => $count.' open '.Str::plural('job', $count);
    @endphp

    {{-- UC01 Search Job: keyword, faceted filters and sort. --}}
    <form method="GET" action="{{ route('worker.jobs.index') }}" class="search" data-job-search>
        @if ($activeCategory)
            <input type="hidden" name="category" value="{{ $activeCategory }}">
        @endif

        <div class="search-row">
            <div class="form-group">
                <label for="q" class="form-label">Keyword</label>
                <div class="input-icon">
                    <x-icon name="search" />
                    <input id="q" name="q" type="search" maxlength="100" placeholder="e.g. barista, poster, tutor"
                           class="form-input" value="{{ $filters['q'] ?? '' }}">
                </div>
            </div>
            <div class="form-group">
                <label for="sort" class="form-label">Sort by</label>
                <select id="sort" name="sort" class="form-select">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </div>

        {{-- Category facet: one straight row of icons. Counts are kept in the tooltip and for screen readers. --}}
        <nav class="cat-row" aria-label="Category" data-cat-row>
            <a href="{{ route('worker.jobs.index', $baseQuery) }}" class="cat-btn" data-job-nav
               @if (! $activeCategory) aria-current="true" @endif
               title="{{ $countLabel((int) $categoryCounts->sum()) }}">
                <span class="tile t-all" aria-hidden="true"><x-icon name="grid" /></span>
                <span>All jobs</span>
                <span class="visually-hidden">, {{ $countLabel((int) $categoryCounts->sum()) }}</span>
            </a>
            @foreach ($categories as $category)
                @php $count = (int) ($categoryCounts[$category->value] ?? 0); @endphp
                <a href="{{ route('worker.jobs.index', $baseQuery + ['category' => $category->value]) }}" class="cat-btn" data-job-nav
                   @if ($activeCategory === $category->value) aria-current="true" @endif
                   title="{{ $countLabel($count) }}">
                    <x-cat-tile :category="$category" />
                    <span>{{ $category->label() }}</span>
                    <span class="visually-hidden">, {{ $countLabel($count) }}</span>
                </a>
            @endforeach
        </nav>

        <details class="more" @if ($moreOpen) open @endif>
            <summary><x-icon name="sliders" />More filters <span>pay, state, district and dates</span></summary>

            {{--
                Pay facet. Hourly, daily and fixed pay can't be compared, so the range appears after a pay type
                is chosen. The slider's ends and dots come from the server; app.js draws them.
            --}}
            <div class="pay-filter" data-pay-filter
                 @if ($payRange)
                     data-floor="{{ $payRange['floor'] }}" data-ceil="{{ $payRange['ceil'] }}" data-step="{{ $payRange['step'] }}"
                     data-amounts="{{ $payAmounts->values()->toJson() }}"
                 @endif>
                <div class="pay-filter-head">
                    <span class="form-label pay-label" id="pay-label">Pay</span>
                    <div class="seg seg-radio" role="radiogroup" aria-labelledby="pay-label"
                         title="Hourly, daily and fixed pay can't be compared directly, so pick one to set a range">
                        <input type="radio" class="visually-hidden" id="pay-type-any" name="pay_type" value="" data-pay-type @checked(! $payType)>
                        <label for="pay-type-any">Any</label>
                        @foreach ($payTypes as $type)
                            <input type="radio" class="visually-hidden" id="pay-type-{{ $type->value }}" name="pay_type" value="{{ $type->value }}"
                                   data-pay-type @checked($payType === $type)>
                            <label for="pay-type-{{ $type->value }}">{{ $type->shortLabel() }}</label>
                        @endforeach
                    </div>
                    <span class="pay-count" data-pay-count aria-live="polite">
                        @if ($payType)
                            {{ $payInRange }} {{ Str::plural('gig', $payInRange) }} in this range
                        @else
                            Pick a pay type to set a range
                        @endif
                    </span>
                </div>

                @if ($payType)
                    <div class="pay-row">
                        <label class="pay-box" for="pay_min">
                            <span class="pay-box-rm">{{ config('gigs.currency') }}</span>
                            <input id="pay_min" name="pay_min" type="number" step="0.01" min="0" inputmode="decimal" data-pay-min
                                   aria-label="Minimum pay in {{ config('gigs.currency') }} {{ $payType->rangeWords() }}"
                                   value="{{ $filters['pay_min'] ?? '' }}" placeholder="{{ $payRange['floor'] }}">
                        </label>
                        <div class="pay-slider" data-pay-slider hidden>
                            <div class="pay-dots" data-pay-dots aria-hidden="true"></div>
                            <div class="dual">
                                <div class="dual-track"><span class="dual-fill" data-pay-fill></span></div>
                                <input type="range" aria-label="Minimum pay" data-pay-lo>
                                <input type="range" aria-label="Maximum pay" data-pay-hi>
                            </div>
                        </div>
                        <label class="pay-box" for="pay_max">
                            <span class="pay-box-rm">{{ config('gigs.currency') }}</span>
                            <input id="pay_max" name="pay_max" type="number" step="0.01" min="0" inputmode="decimal" data-pay-max
                                   aria-label="Maximum pay in {{ config('gigs.currency') }} {{ $payType->rangeWords() }}"
                                   value="{{ $filters['pay_max'] ?? '' }}" placeholder="{{ $payRange['ceil'] }}">
                        </label>
                    </div>
                @endif
            </div>

            <div class="more-grid">
                <div class="form-group">
                    <label for="state" class="form-label">State</label>
                    <select id="state" name="state" class="form-select" data-state-select>
                        <option value="">All states</option>
                        @foreach ($locations->keys() as $state)
                            <option value="{{ $state }}" @selected(($filters['state'] ?? '') === $state)>{{ $state }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="location_id" class="form-label">District</label>
                    <select id="location_id" name="location_id" class="form-select" data-district-select>
                        <option value="">All districts</option>
                        @foreach ($locations as $state => $districts)
                            <optgroup label="{{ $state }}" data-state="{{ $state }}">
                                @foreach ($districts as $location)
                                    <option value="{{ $location->id }}" @selected((int) ($filters['location_id'] ?? 0) === $location->id)>
                                        {{ $location->district }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="date_from" class="form-label">Available from</label>
                    <input id="date_from" name="date_from" type="date" class="form-input"
                           value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="form-group">
                    <label for="date_to" class="form-label">Available until</label>
                    <input id="date_to" name="date_to" type="date" class="form-input"
                           value="{{ $filters['date_to'] ?? '' }}">
                </div>
            </div>
            <div class="more-actions">
                <a href="{{ route('worker.jobs.index') }}" class="btn btn-outline btn-sm" data-job-nav>Clear filters</a>
                <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
            </div>
        </details>
    </form>

    {{-- Everything below is swapped in place when the filters change (app.js). --}}
    <div class="stack" data-results-region>
        @if ($errors->any())
            <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="results-head">
            <h2 class="results-title">Open jobs</h2>
            <span class="results-count" aria-live="polite">{{ $jobs->total() }} {{ Str::plural('gig', $jobs->total()) }} found</span>
        </div>

        <div class="results-wrap" data-split>
            <div class="list-pane" data-list-pane>
                @if ($jobs->isEmpty())
                    <div class="empty-state">
                        <span class="tile t-all" aria-hidden="true"><x-icon name="search" /></span>
                        <h3 class="empty-state-title">No open jobs match your search</h3>
                        <p class="empty-state-text">New gigs are posted all the time. Try another category, a wider date range or fewer filters.</p>
                        <a href="{{ route('worker.jobs.index') }}" class="btn btn-outline btn-sm" data-job-nav>Show all jobs</a>
                    </div>
                @else
                    <div class="gigs" data-results>
                        @foreach ($jobs as $job)
                            @php $stage = $myStages[$job->id] ?? null; @endphp
                            <article class="gig" data-job-card>
                                <div class="gig-head">
                                    <x-cat-tile :category="$job->category" />
                                    <div class="gig-head-text">
                                        <h3 class="gig-heading">
                                            <a href="{{ route('worker.jobs.show', $job) }}" class="gig-title gig-open" data-job-open>{{ $job->title }}</a>
                                        </h3>
                                        <span class="gig-cat">{{ $job->category->label() }} &middot; {{ $job->employer->displayName() }}</span>
                                    </div>
                                </div>
                                <div class="gig-pay-row">
                                    <x-pay :job="$job" />
                                    @if ($job->created_at->gt(now()->subDay()))
                                        <span class="new">New</span>
                                    @endif
                                    @if ($stage instanceof ApplicationStage)
                                        <x-badge :status="$stage" />
                                    @endif
                                </div>
                                <ul class="meta">
                                    <li><x-icon name="calendar" />{{ $job->dateRangeLabel() }}</li>
                                    <li><x-icon name="pin" />{{ $job->location->label() }}</li>
                                </ul>
                                <div class="gig-foot">
                                    <span class="posted">Posted {{ $job->created_at->diffForHumans() }}</span>
                                    <span class="gig-go" aria-hidden="true"><x-icon name="arrow" /></span>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{ $jobs->links() }}
                @endif
            </div>

            {{-- Job details slide in here when a card is clicked on a wide screen. --}}
            <section class="detail-pane" data-detail-pane hidden aria-label="Job details">
                <div class="pane-bar">
                    <span class="pane-label">Job details</span>
                    <a href="#" class="pane-full" data-pane-full>Open full page</a>
                    <button type="button" class="close-btn" data-pane-close aria-label="Close job details"><x-icon name="x" /></button>
                </div>
                <div class="pane-body" data-pane-body></div>
            </section>
        </div>
    </div>
@endsection
