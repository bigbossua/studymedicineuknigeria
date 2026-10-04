{{-- Compact applicant-journey stepper for page heads (App\Support\MedicineRoute). The accessible "you are here" step
     marker lives in <x-route-map> at the foot of each page; this one is a visual orientation aid. --}}
@props(['current' => null])
@php($steps = \App\Support\MedicineRoute::steps())
@php($index = collect($steps)->search(fn ($s) => $s['key'] === $current))
<nav {{ $attributes->merge(['class' => '']) }} aria-label="Applicant journey">
    <ol class="stepper">
        @foreach($steps as $i => $step)
            <li><a href="{{ route($step['route']) }}" @if($step['key'] === $current) data-current @endif @class(['done' => $index !== false && $i < $index])><span class="num">{{ $i + 1 }}</span>{{ $step['label'] }}@if($step['key'] === $current)<span class="sr-only"> (this section)</span>@endif</a></li>
        @endforeach
    </ol>
</nav>
