@props(['rating'])

<span {{ $attributes->merge(['class' => 'stars']) }} role="img" aria-label="{{ $rating }} out of 5">@for ($i = 1; $i <= 5; $i++)<x-icon name="star" :class="$i > $rating ? 'off' : null" />@endfor</span>
