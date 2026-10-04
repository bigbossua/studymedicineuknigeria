<x-layouts.portal :seo="$seo">
    <div class="max-w-3xl">
        <p class="eyebrow mb-3">Welcome, {{ Str::of(auth()->user()->name)->explode(' ')->first() }}</p>
        <h1 class="text-h2">Start your application</h1>
        <p class="lede mt-3">Create your application and tell us about your qualifications and plans; everything saves as you go. Our team then reviews your profile, and your portal shows the service options open to you with their exact fees. You choose one, and nothing is charged before you have seen the fee and the terms.</p>
        <x-fee-disclaimer class="mt-5" />
    </div>
    <form method="post" action="{{ route('portal.start') }}" class="mt-8 grid gap-5 lg:grid-cols-3">
        @csrf
        {{-- No fees here: they are shown only after our team has approved the profile (User::canSeeServicePrices). --}}
        @foreach($tiers as $tier)
            <section class="card flex flex-col relative {{ $tier->badge ? 'border-2 border-navy-700' : '' }}" aria-labelledby="st-{{ $tier->code }}">
                @if($tier->badge)<span class="absolute -top-3 left-6 rounded-full bg-navy-700 px-3 py-1 text-[0.75rem] font-semibold uppercase tracking-wide text-white">{{ $tier->badge }}</span>@endif
                <p class="eyebrow">{{ $tier->tagline ?? $tier->code }}</p><h2 id="st-{{ $tier->code }}" class="text-xl font-serif font-semibold mt-1">{{ $tier->name }}</h2>
                <p class="mt-3 text-[0.9375rem] text-ink-700">{{ $tier->summary }}</p>
                <ul class="mt-4 space-y-1.5 text-[0.875rem] text-ink-700 list-disc pl-5">@foreach($tier->deliverables as $d)<li>{{ $d }}</li>@endforeach</ul>
            </section>
        @endforeach
        <p class="lg:col-span-3 text-[0.9375rem] text-ink-700"><span class="font-semibold">Service options and pricing are provided after your profile has been reviewed.</span></p>
        <div class="lg:col-span-3 card flex flex-col sm:flex-row sm:items-end gap-4 justify-between">
            @php($defaultYear = \App\Support\Intake::defaultYear())
            <div class="field max-w-xs"><label for="intake_year" class="label">Intended entry year</label>
                <select id="intake_year" name="intake_year" class="input">@foreach(range(now()->year + 1, now()->year + 3) as $y)<option value="{{ $y }}" @selected(old('intake_year', $defaultYear)==$y)>September {{ $y }}</option>@endforeach</select>
                @php($deadline = \App\Models\Topic::bySlug('ucas-2027')?->fact('deadline_medicine'))
                <p class="hint">@if($deadline?->isPublishable())The UCAS deadline for Medicine for {{ $deadline->subject->cycle ?? 'the current cycle' }} entry is {{ $deadline->value_text }} (official source: UCAS), and the UCAT is sat in the summer before it. If either has passed for the entry year you want, choose the year after.@else Medicine applications close in October of the year before entry, and the UCAT is sat in the summer before that. If either has passed for the entry year you want, choose the year after.@endif</p></div>
            <button type="submit" class="btn btn-primary btn-lg">Create my application</button>
        </div>
    </form>
</x-layouts.portal>
