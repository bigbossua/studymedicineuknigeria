@props(['current' => null])
{{-- The Medicine route (App\Support\MedicineRoute): where this page sits in the applicant's journey and the next step. --}}
@php($steps = \App\Support\MedicineRoute::steps())
@php($index = collect($steps)->search(fn ($s) => $s['key'] === $current))
<nav {{ $attributes->merge(['class' => 'route-map mt-14']) }} aria-labelledby="route-map-heading">
    <p id="route-map-heading" class="eyebrow mb-3">Your route to UK Medicine</p>
    <ol class="flex flex-wrap gap-2 text-[0.875rem]">
        @foreach($steps as $i => $step)
            <li class="flex items-center gap-2">
                @if($step['key'] === $current && url()->current() === route($step['route']))
                    <span class="chip chip-info" aria-current="step"><span class="sr-only">You are here: </span>{{ $i + 1 }}. {{ $step['label'] }}</span>
                @elseif($step['key'] === $current)
                    {{-- a spoke inside this step (e.g. WAEC within requirements): highlighted, and still a link up to the step's own page --}}
                    <a href="{{ route($step['route']) }}" class="chip chip-info no-underline" aria-current="step"><span class="sr-only">You are here: </span>{{ $i + 1 }}. {{ $step['label'] }}</a>
                @else
                    <a href="{{ route($step['route']) }}" class="chip chip-pending no-underline">{{ $i + 1 }}. {{ $step['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
    @if($index !== false && isset($steps[$index + 1]))
        <p class="mt-3 text-[0.9375rem] text-ink-700">Next step: <a href="{{ route($steps[$index + 1]['route']) }}">{{ $steps[$index + 1]['label'] }}</a></p>
    @endif
</nav>
