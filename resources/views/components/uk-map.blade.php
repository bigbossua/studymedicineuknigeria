{{-- Illustrative UK map with a pin per medical-school city (outlines: Natural Earth via ops/design/build-maps.mjs;
     positions: data/geo/cities.json). Decorative: the same information is in the page's text and lists.
     $universities: the schools to pin; $highlight: a slug drawn larger and labelled (school pages). --}}
@props(['universities', 'highlight' => null, 'label' => null])
@php
    $map = json_decode(file_get_contents(resource_path('data/map-uk.json')), true);
    $pins = collect($universities)->filter(fn ($u) => isset($map['points'][$u->city]))->groupBy('city')->map(function ($group, $city) use ($map, $highlight) {
        return ['xy' => $map['points'][$city], 'count' => $group->count(), 'city' => $city,
            'accepts' => $group->contains(fn ($u) => in_array($u->international_policy, ['accepts', 'international_only'], true)),
            'highlight' => $highlight && $group->contains('slug', $highlight)];
    })->sortBy(fn ($p) => $p['highlight'] ? 1 : 0);
@endphp
<svg viewBox="{{ $map['viewBox'] }}" {{ $attributes->merge(['class' => 'w-full h-auto']) }} aria-hidden="true" focusable="false">
    <path d="{{ $map['ie'] }}" fill="#E7ECF0" stroke="#D2DAE1" stroke-width=".8"/>
    <path d="{{ $map['gb'] }}" fill="#DCE7EF" stroke="#9FB8CB" stroke-width=".8"/>
    @foreach($pins as $p)
        @php([$x, $y] = $p['xy'])
        @if($p['highlight'])
            <circle cx="{{ $x }}" cy="{{ $y }}" r="16" fill="#B8322F" fill-opacity=".16"/>
            <circle cx="{{ $x }}" cy="{{ $y }}" r="7" fill="#B8322F" stroke="#fff" stroke-width="2.5"/>
            @if($label)<text x="{{ $x + 14 }}" y="{{ $y + 5 }}" font-family="Inter, system-ui, sans-serif" font-size="15" font-weight="600" fill="#0F1E2E" stroke="#fff" stroke-width="4" paint-order="stroke">{{ $label }}</text>@endif
        @else
            <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $p['count'] > 1 ? 4.5 + $p['count'] : 4.5 }}" fill="{{ $p['accepts'] ? '#0B3D5C' : '#8A9AAA' }}" fill-opacity="{{ $highlight ? .35 : .9 }}" stroke="#fff" stroke-width="1.5"/>
            @if($p['count'] > 1 && ! $highlight)<text x="{{ $x }}" y="{{ $y + 3.5 }}" text-anchor="middle" font-family="Inter, system-ui, sans-serif" font-size="10" font-weight="700" fill="#fff">{{ $p['count'] }}</text>@endif
        @endif
    @endforeach
</svg>
