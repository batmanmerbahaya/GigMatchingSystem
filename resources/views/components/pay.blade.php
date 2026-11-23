{{-- Pay chip: the amount stands out in amber, e.g. "RM 120.00 / day". --}}
@props(['job'])

<span {{ $attributes->merge(['class' => 'pay']) }}><b>{{ config('gigs.currency') }} {{ number_format((float) $job->pay_rate, 2) }}</b> {{ $job->pay_type->suffix() }}</span>
