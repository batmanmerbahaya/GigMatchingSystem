{{-- Tinted icon tile for a job category. size: null (default), "sm" or "lg". --}}
@props(['category', 'size' => null])

<span {{ $attributes->merge(['class' => 'tile t-'.$category->tone().($size ? ' tile-'.$size : '')]) }} aria-hidden="true"><x-icon :name="$category->icon()" /></span>
