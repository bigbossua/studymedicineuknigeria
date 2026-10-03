@props(['slug', 'sizes' => '(min-width: 1024px) 50vw, 100vw', 'priority' => false, 'ratio' => null, 'caption' => true])
@php $p = \App\Support\Photos::get($slug); @endphp
@if($p && ! empty($p['sizes']))
    @php
        $srcset = fn ($ext) => implode(', ', array_map(fn ($w) => "/images/photos/{$p['slug']}-{$w}.{$ext} {$w}w", $p['sizes']));
        $largest = max($p['sizes']);
        $style = 'background-image:url('.$p['placeholder'].');background-size:cover;background-position:'.$p['focal'].';'.($ratio ? "aspect-ratio:{$ratio};" : '');
    @endphp
    <figure {{ $attributes->merge(['class' => 'photo']) }}>
        <picture>
            <source type="image/webp" srcset="{{ $srcset('webp') }}" sizes="{{ $sizes }}">
            <img src="/images/photos/{{ $p['slug'] }}-{{ $largest }}.jpg" srcset="{{ $srcset('jpg') }}" sizes="{{ $sizes }}" alt="{{ $p['alt'] }}" width="{{ $p['width'] }}" height="{{ $p['height'] }}" @if($priority) fetchpriority="high" decoding="async" @else loading="lazy" decoding="async" @endif class="block w-full h-full object-cover" style="{{ $style }}object-position:{{ $p['focal'] }};">
        </picture>
        @if($caption && ! empty($p['credit']))<figcaption class="mt-2 text-[0.75rem] text-ink-500">Photo: {{ $p['credit'] }}</figcaption>@endif
    </figure>
@else
    {{-- No photograph published for this slot yet: render nothing rather than a placeholder box --}}
@endif
