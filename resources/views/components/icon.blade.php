{{-- Inline line icon. Usage: <x-icon name="search" /> (names are in App\Support\Icons). --}}
@props(['name'])

<svg {{ $attributes->merge(['class' => 'i']) }} viewBox="0 0 24 24" aria-hidden="true" focusable="false">{!! \App\Support\Icons::path($name) !!}</svg>
