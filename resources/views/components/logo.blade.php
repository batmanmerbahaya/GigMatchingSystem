{{--
    Gig Matching System logo: a job ticket with an amber spark. The notches and dashed
    tear line match the job cards. :small="true" is the 16 to 24px version: no tear line,
    spark centred. Decorative by default, because the name is always written next to it.
--}}
@props(['small' => false])

<svg {{ $attributes->merge(['class' => 'logo-mark']) }} viewBox="0 0 48 48" aria-hidden="true" focusable="false">
    <path d="M10 8H38A6 6 0 0 1 44 14V24.5A4.5 4.5 0 0 0 44 33.5V34A6 6 0 0 1 38 40H10A6 6 0 0 1 4 34V33.5A4.5 4.5 0 0 0 4 24.5V14A6 6 0 0 1 10 8Z" fill="#0F766E"/>
    @unless ($small)
        <path d="M10.5 29H37.5" fill="none" stroke="#FFFFFF" stroke-opacity=".6" stroke-width="2.2" stroke-dasharray="2.6 3.2" stroke-linecap="round"/>
    @endunless
    <path d="M24 10.5Q25.3 17.2 32 18.5Q25.3 19.8 24 26.5Q22.7 19.8 16 18.5Q22.7 17.2 24 10.5Z" fill="#FBBF24" @if ($small) transform="translate(0 5.5)" @endif/>
</svg>
