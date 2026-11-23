{{--
    Star rating input: five radio buttons named "rating" (1 to 5), drawn as stars.
    The inputs are in reverse order so CSS can light up a star and the ones before it.
--}}
@php
    $words = [5 => 'Excellent', 4 => 'Good', 3 => 'Okay', 2 => 'Poor', 1 => 'Very poor'];
    $selected = (int) old('rating');
@endphp

<fieldset class="rate-input" data-rate-input>
    <legend class="visually-hidden">Rating</legend>
    @foreach ($words as $value => $word)
        <input type="radio" id="{{ $idPrefix }}-{{ $value }}" name="rating" value="{{ $value }}" required
               data-word="{{ $word }}" @checked($selected === $value)>
        <label for="{{ $idPrefix }}-{{ $value }}" title="{{ $value }} - {{ $word }}">
            <x-icon name="star" /><span class="visually-hidden">{{ $value }} - {{ $word }}</span>
        </label>
    @endforeach
</fieldset>
<p class="rate-label" data-rate-label aria-live="polite">{{ $selected ? $words[$selected].' · '.$selected.' out of 5' : 'Choose a rating' }}</p>
