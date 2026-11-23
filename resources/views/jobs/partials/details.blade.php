{{-- Job facts shared by the worker details page and the employer page. --}}
<dl class="facts">
    <div class="fact fact-pay">
        <x-icon name="wallet" />
        <div>
            <dt>Pay</dt>
            <dd>{{ config('gigs.currency') }} {{ number_format((float) $job->pay_rate, 2) }} <span class="unit">{{ $job->pay_type->suffix() }}</span></dd>
        </div>
    </div>
    <div class="fact">
        <x-icon name="calendar" />
        <div>
            <dt>Dates</dt>
            <dd>{{ $job->dateRangeLabel() }} ({{ $job->durationDays() }} {{ Str::plural('day', $job->durationDays()) }})</dd>
        </div>
    </div>
    <div class="fact">
        <x-icon name="pin" />
        <div>
            <dt>Location</dt>
            <dd>{{ $job->location->label() }}</dd>
        </div>
    </div>
    <div class="fact">
        <x-icon name="clock" />
        <div>
            <dt>Posted</dt>
            <dd>{{ $job->created_at->format('j M Y') }}</dd>
        </div>
    </div>
</dl>

<h3 class="detail-section-title">About this job</h3>
<p class="detail-text text-prewrap">{{ $job->description }}</p>
