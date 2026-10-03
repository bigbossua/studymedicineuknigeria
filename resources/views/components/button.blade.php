@props(['href' => null, 'variant' => 'primary', 'size' => null, 'type' => 'button'])
@php $classes = 'btn btn-'.$variant.($size ? ' btn-'.$size : ''); @endphp
@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
