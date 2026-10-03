<x-layouts.portal :seo="$seo">
    <div class="max-w-3xl">
        <p class="eyebrow mb-3">Welcome, {{ Str::of(auth()->user()->name)->explode(' ')->first() }}</p>
        <h1 class="text-h2">Start your application</h1>
        <p class="lede mt-3">Choose the service that fits where you are. You can read what each includes and excludes before anything is charged. Your application is saved as you go.</p>
    </div>
    <form method="post" action="{{ route('portal.start') }}" class="mt-8 grid gap-5 lg:grid-cols-3">
        @csrf
        @foreach($tiers as $tier)
            <label class="card-raised flex flex-col cursor-pointer has-[:checked]:border-navy-700 has-[:checked]:ring-2 has-[:checked]:ring-navy-700/20">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="eyebrow">{{ $tier->code }}</p><h2 class="text-xl font-serif font-semibold mt-1">{{ $tier->name }}</h2></div>
                    <input type="radio" name="service_tier_id" value="{{ $tier->id }}" class="mt-1 w-5 h-5" @checked(old('service_tier_id', $loop->first ? $tier->id : null) == $tier->id)>
                </div>
                <p class="mt-3 text-[0.9375rem] text-ink-700">{{ $tier->summary }}</p>
                <p class="mt-4 font-semibold">{{ $tier->hasPrices() ? $tier->prices->filter(fn($p)=>$p->amount_minor!==null)->map->formatted()->join(' + ') : 'Price to be confirmed before any payment is taken' }}</p>
                <ul class="mt-4 space-y-1.5 text-[0.875rem] text-ink-700 list-disc pl-5">@foreach($tier->deliverables as $d)<li>{{ $d }}</li>@endforeach</ul>
                <p class="mt-4 text-[0.8125rem] text-ink-500"><span class="font-semibold">Not included:</span> {{ implode('; ', $tier->exclusions ?? []) }}.</p>
            </label>
        @endforeach
        <div class="lg:col-span-3 card flex flex-col sm:flex-row sm:items-end gap-4 justify-between">
            <div class="field max-w-xs"><label for="intake_year" class="label">Intended entry year</label>
                <select id="intake_year" name="intake_year" class="input">@foreach(range(now()->year + 1, now()->year + 3) as $y)<option value="{{ $y }}" @selected(old('intake_year', 2028)==$y)>September {{ $y }}</option>@endforeach</select>
                <p class="hint">For 2027 UCAS medicine entry the deadline is 15 October 2026 and the UCAT window has closed; most students starting now plan for 2028.</p></div>
            <button type="submit" class="btn btn-primary btn-lg">Create my application</button>
        </div>
        @error('service_tier_id')<p class="error-text lg:col-span-3">{{ $message }}</p>@enderror
    </form>
</x-layouts.portal>
