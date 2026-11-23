{{-- Job tracker: one step per main stage, each with its timestamp (spec section 4). --}}
@php
    $steps = \App\Enums\ApplicationStage::mainFlow();
@endphp

<ol class="tracker" aria-label="Job progress">
    @foreach ($steps as $step)
        @php
            $reachedAt = $application->{$step->timestampColumn()};
        @endphp
        <li class="tracker-step {{ $reachedAt ? 'is-done' : '' }} {{ $application->stage === $step ? 'is-current' : '' }}">
            <span class="tracker-dot" aria-hidden="true">@if ($reachedAt)<x-icon name="check" />@endif</span>
            <span class="tracker-label">{{ $step->label() }}</span>
            <span class="tracker-time">
                @if ($reachedAt)
                    {{ $reachedAt->format('j M Y, g:i A') }}
                @elseif ($step === \App\Enums\ApplicationStage::Rated)
                    Optional
                @else
                    Not yet
                @endif
            </span>
        </li>
    @endforeach
</ol>

@if ($application->stage->isEarlyEnding())
    <p class="tracker-ended">
        <x-badge :status="$application->stage" />
        on {{ $application->ended_at?->format('j M Y, g:i A') }}.
        @switch($application->stage)
            @case(\App\Enums\ApplicationStage::Withdrawn)
                The worker withdrew the application.
                @break
            @case(\App\Enums\ApplicationStage::Rejected)
                The employer chose not to go ahead with this application.
                @break
            @case(\App\Enums\ApplicationStage::Cancelled)
                The job was closed or an account was deleted.
                @break
        @endswitch
    </p>
@endif
