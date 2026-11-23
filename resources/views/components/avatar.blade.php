{{-- Initials avatar. Gig Workers are amber, Contract Entities teal. size: null, "md" or "lg". --}}
@props(['user', 'size' => null])

@php
    $words = preg_split('/\s+/', trim($user->displayName())) ?: [];
    $initials = mb_strtoupper(collect($words)->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode(''));
@endphp

<span {{ $attributes->merge(['class' => 'avatar'.($user->isContractEntity() ? ' avatar-emp' : '').($size ? ' avatar-'.$size : '')]) }} aria-hidden="true">{{ $initials ?: '?' }}</span>
