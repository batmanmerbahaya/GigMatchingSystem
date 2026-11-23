{{-- Average rating and completed job count, both calculated when needed (spec section 9). --}}
@props(['user'])

@php
    $average = $user->averageRating();
    $completed = $user->completedJobCount();
@endphp

<span {{ $attributes->merge(['class' => 'rating-summary']) }}>
    @if ($average !== null)
        <span class="stars" aria-hidden="true"><x-icon name="star" /></span>
        <strong>{{ number_format($average, 1) }}</strong> / 5
    @else
        <span class="text-muted">No ratings yet</span>
    @endif
    <span class="text-muted">&middot; {{ $completed }} {{ Str::plural('job', $completed) }} completed</span>
</span>
