{{--
    UC02 View Job Details, with Apply (UC03) and Report Listing (UC08).
    Used as the full page and, on wide screens, inside the Find Jobs split view (app.js reads [data-job-detail]).
    Phone numbers are never shown here.
--}}
@php
    $employer = $job->employer;
@endphp

<article class="job-detail" data-job-detail>
    <div class="detail-hero">
        <x-cat-tile :category="$job->category" size="lg" />
        <div class="detail-hero-text">
            <h2 class="detail-title">{{ $job->title }}</h2>
            <span class="detail-sub">{{ $job->category->label() }} &middot; {{ $employer->displayName() }}</span>
        </div>
        <div class="panel-tags">
            <x-badge :status="$job->status" />
            @if ($job->created_at->gt(now()->subDay()))
                <span class="new">New</span>
            @endif
        </div>
    </div>

    <div class="detail-section">
        @include('jobs.partials.details', ['job' => $job])
    </div>

    <div class="detail-section poster-body">
        <span class="panel-label">Posted by</span>
        <div class="poster-row">
            <x-avatar :user="$employer" size="md" />
            <div class="poster-text">
                <b>{{ $employer->displayName() }}</b>
                <span>{{ $employer->contractEntity?->entity_type?->label() ?? 'Contract Entity' }}</span>
            </div>
        </div>
        <x-rating :user="$employer" />
        <div class="privacy"><x-icon name="shield" /><span>Phone numbers are shared only after you are accepted.</span></div>
    </div>

    {{-- UC03 Apply Job --}}
    <div class="detail-section" id="apply">
        @if ($application)
            <div class="alert alert-info alert-with-action">
                <span>
                    You applied on {{ $application->applied_at->format('j M Y') }}.
                    Current stage: <x-badge :status="$application->stage" />
                </span>
                <a href="{{ route('applications.show', $application) }}" class="btn btn-outline btn-sm" data-skeleton="detail">Open job tracker</a>
            </div>
        @elseif (! $job->isOpen())
            <p class="text-muted">This job is no longer open for applications.</p>
        @elseif (! auth()->user()->hasVerifiedEmail())
            <div class="alert alert-warning">Verify your email address to apply for this job.</div>
        @else
            <h3 class="detail-section-title">Interested in this gig?</h3>
            <form method="POST" action="{{ route('worker.jobs.apply', $job) }}"
                  data-confirm="Apply for this job? You can withdraw until the employer accepts you."
                  data-confirm-ok="Yes, apply">
                @csrf
                <div class="form-group">
                    <label for="message" class="form-label">Message to the employer <span class="form-hint">(optional)</span></label>
                    <textarea id="message" name="message" rows="3" maxlength="1000"
                              class="form-textarea @error('message') is-invalid @enderror"
                              placeholder="Tell them why you are a good fit.">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="apply-actions">
                    <button type="submit" class="btn btn-primary">Apply for this job</button>
                </div>
            </form>
        @endif
    </div>

    {{-- UC08 Report Listing --}}
    @if ($hasReported)
        <div class="detail-section">
            <p class="text-muted text-small">You have reported this listing.</p>
        </div>
    @elseif ($job->isOpen())
        <div class="detail-section">
            <details class="report" @if ($errors->hasAny(['reason', 'note'])) open @endif>
                <summary><x-icon name="flag" />Report this listing<x-icon name="chevron" class="chev" /></summary>
                <div class="report-body">
                    @if (auth()->user()->hasVerifiedEmail())
                        <form method="POST" action="{{ route('worker.jobs.report', $job) }}">
                            @csrf
                            <div class="form-group">
                                <label for="reason" class="form-label">Reason</label>
                                <select id="reason" name="reason" class="form-select @error('reason') is-invalid @enderror"
                                        data-report-reason required>
                                    <option value="" disabled @selected(! old('reason'))>Choose a reason</option>
                                    @foreach ($reasons as $reason)
                                        <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                                    @endforeach
                                </select>
                                @error('reason')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="note" class="form-label">Note <span class="form-hint">(required for Other)</span></label>
                                <textarea id="note" name="note" rows="3" maxlength="1000" data-report-note
                                          class="form-textarea @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                                @error('note')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm">Submit report</button>
                        </form>
                    @else
                        <p class="text-muted text-small">Verify your email address to report a listing.</p>
                    @endif
                </div>
            </details>
        </div>
    @endif
</article>
