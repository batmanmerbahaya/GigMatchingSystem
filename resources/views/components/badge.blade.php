{{-- Status badge for any enum with a label(): job status, tracker stage, submission status.
     label overrides the words (for example in-person wording) but keeps the colour. --}}
@props(['status', 'label' => null])

<span {{ $attributes->merge(['class' => 'badge badge-'.$status->value]) }}>{{ $label ?? $status->label() }}</span>
