<x-layouts.portal :seo="$seo">
    <div class="max-w-3xl">
        <p class="eyebrow mb-3">Welcome, {{ Str::of(auth()->user()->name)->explode(' ')->first() }}</p>
        <h1 class="text-h2">Start your application</h1>
        <p class="lede mt-3">Choose the service that fits where you are. You can read what each includes and excludes, and confirm the fee on the next page before anything is charged. Your application is saved as you go.</p>
        <x-fee-disclaimer class="mt-5" />
    </div>
    <form method="post" action="{{ route('portal.start') }}" class="mt-8 grid gap-5 lg:grid-cols-3">
        @csrf
        @php($chosen = ($intended ? $tiers->firstWhere('code', $intended)?->id : null) ?? $tiers->first()?->id)
        @foreach($tiers as $tier)
            <label class="card-raised flex flex-col cursor-pointer relative has-[:checked]:border-navy-700 has-[:checked]:ring-2 has-[:checked]:ring-navy-700/20">
                @if($tier->badge)<span class="absolute -top-3 left-6 rounded-full bg-navy-700 px-3 py-1 text-[0.75rem] font-semibold uppercase tracking-wide text-white">{{ $tier->badge }}</span>@endif
                <div class="flex items-start justify-between gap-3">
                    <div><p class="eyebrow">{{ $tier->tagline ?? $tier->code }}</p><h2 class="text-xl font-serif font-semibold mt-1">{{ $tier->name }}</h2></div>
                    <input type="radio" name="service_tier_id" value="{{ $tier->id }}" class="mt-1 w-5 h-5" aria-label="{{ $tier->name }}" @checked(old('service_tier_id', $chosen ?? $tiers->first()->id) == $tier->id)>
                </div>
                <p class="mt-3 text-[0.9375rem] text-ink-700">{{ $tier->summary }}</p>
                <p class="mt-4"><span class="text-2xl font-serif font-semibold">{{ $tier->priceFor('full')?->formatted() ?? 'Price not yet published' }}</span> <span class="text-[0.8125rem] text-ink-500">service fee, paid before work begins</span></p>
                <ul class="mt-4 space-y-1.5 text-[0.875rem] text-ink-700 list-disc pl-5">@foreach($tier->deliverables as $d)<li>{{ $d }}</li>@endforeach</ul>
                <p class="mt-4 text-[0.8125rem] text-ink-500"><span class="font-semibold">Not included:</span> {{ implode('; ', $tier->exclusions ?? []) }}.</p>
            </label>
        @endforeach
        <div class="lg:col-span-3 card flex flex-col sm:flex-row sm:items-end gap-4 justify-between">
            @php($defaultYear = \App\Support\Intake::defaultYear())
            <div class="field max-w-xs"><label for="intake_year" class="label">Intended entry year</label>
                <select id="intake_year" name="intake_year" class="input">@foreach(range(now()->year + 1, now()->year + 3) as $y)<option value="{{ $y }}" @selected(old('intake_year', $defaultYear)==$y)>September {{ $y }}</option>@endforeach</select>
                @php($deadline = \App\Models\Topic::bySlug('ucas-2027')?->fact('deadline_medicine'))
                <p class="hint">@if($deadline?->isPublishable())The UCAS deadline for Medicine for {{ $deadline->subject->cycle ?? 'the current cycle' }} entry is {{ $deadline->value_text }} (official source: UCAS), and the UCAT is sat in the summer before it. If either has passed for the entry year you want, choose the year after.@else Medicine applications close in October of the year before entry, and the UCAT is sat in the summer before that. If either has passed for the entry year you want, choose the year after.@endif</p></div>
            <button type="submit" class="btn btn-primary btn-lg">Create my application</button>
        </div>
        @error('service_tier_id')<p class="error-text lg:col-span-3">{{ $message }}</p>@enderror
    </form>
</x-layouts.portal>
