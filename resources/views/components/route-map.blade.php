@props(['current' => null])
{{-- The Medicine route (App\Support\MedicineRoute): where this page sits in the applicant's journey and the next step. --}}
@php($steps = \App\Support\MedicineRoute::steps())
@php($index = collect($steps)->search(fn ($s) => $s['key'] === $current))
<nav {{ $attributes->merge(['class' => 'route-map mt-14 rounded-lg border border-ink-200 bg-paper-warm p-6 sm:p-8 grid gap-6 lg:grid-cols-12 lg:items-center']) }} aria-labelledby="route-map-heading">
    <div class="lg:col-span-8">
        <p id="route-map-heading" class="eyebrow mb-4">Your route to UK Medicine</p>
        <ol class="flex flex-wrap gap-2 text-[0.8125rem]">
            @foreach($steps as $i => $step)
                <li class="flex items-center gap-2">
                    @if($step['key'] === $current && url()->current() === route($step['route']))
                        <span class="chip bg-navy-700 text-white py-1.5 px-3" aria-current="step"><span class="sr-only">You are here: </span>{{ $i + 1 }}. {{ $step['label'] }}</span>
                    @elseif($step['key'] === $current)
                        {{-- a spoke inside this step (e.g. WAEC within requirements): highlighted, and still a link up to the step's own page --}}
                        <a href="{{ route($step['route']) }}" class="chip bg-navy-700 text-white py-1.5 px-3 no-underline" aria-current="step"><span class="sr-only">You are here: </span>{{ $i + 1 }}. {{ $step['label'] }}</a>
                    @else
                        <a href="{{ route($step['route']) }}" class="chip bg-paper ring-1 ring-ink-200 text-ink-700 hover:ring-navy-300 py-1.5 px-3 no-underline">{{ $i + 1 }}. {{ $step['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
    @if($index !== false && isset($steps[$index + 1]))
        <div class="lg:col-span-4">
            <a href="{{ route($steps[$index + 1]['route']) }}" class="card card-hover flex items-center justify-between gap-4 no-underline text-inherit">
                <span><span class="block eyebrow mb-1">Next step</span><span class="font-serif text-xl font-semibold text-ink-900">{{ $steps[$index + 1]['label'] }}</span></span>
                <span class="icon-badge bg-navy-700! text-white! ring-0!"><x-icon name="arrow-right" :size="20" /></span>
            </a>
        </div>
    @endif
</nav>
