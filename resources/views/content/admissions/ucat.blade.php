<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Admissions · UCAT', 'title' => 'UCAT for Nigerian students: dates, structure, fees and sitting the test in Nigeria', 'lede' => 'The UCAT is required by most UK medical schools and is sat in the summer before you apply, at Pearson VUE centres. From Nigeria the practical centres are Lagos and Abuja, and capacity is limited. Here are the facts for the 2026 cycle (2027 entry), each with its source, and what to do if you missed it.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section><h2>UCAT 2026 cycle (for 2027 entry)</h2>
                <dl class="card mt-4">
                    <x-fact-row :fact="$ucat?->fact('registration_window')" label="Registration" />
                    <x-fact-row :fact="$ucat?->fact('booking_opens')" label="Booking opens" />
                    <x-fact-row :fact="$ucat?->fact('testing_window')" label="Testing window" />
                    <x-fact-row :fact="$ucat?->fact('results_to_universities')" label="Results sent to universities" />
                    <x-fact-row :fact="$ucat?->fact('structure')" label="Structure and scoring" />
                    <x-fact-row :fact="$ucat?->fact('fee_uk_gbp')" label="Fee (UK centres)" />
                    <x-fact-row :fact="$ucat?->fact('fee_international_gbp')" label="Fee (centres outside the UK, including Nigeria)" />
                    <x-fact-row :fact="$ucat?->fact('test_centres_nigeria')" label="Test centres in Nigeria" />
                </dl></section>
            <section class="prose-site">
                <h2>If you have no UCAT result today</h2>
                <p>For <strong>2027 entry</strong> the window has closed, and the UCAS medicine deadline follows within weeks. Schools that require the UCAT cannot consider you for 2027. Two honest options remain: apply for 2027 only to the small number of schools that do not use the UCAT for international applicants, or plan for <strong>2028 entry</strong>, registering for the UCAT in May–June 2027 and booking a Lagos or Abuja slot the day booking opens. Our <a href="{{ route('admissions.ucas2027') }}">timeline</a> lays out both.</p>
                <h2>Booking from Nigeria</h2>
                <ul><li>Register on the UCAT Consortium site as soon as registration opens; booking opens a few weeks later and Nigerian slots go quickly.</li><li>You need a valid international passport as identification at the centre.</li><li>Pay the international fee by card; if your Nigerian card is declined for foreign transactions, arrange a dollar/pound card in advance.</li><li>Sit the test early in the window so a problem at the centre leaves time to rebook.</li></ul>
            </section>
            <section><h2>Which medical schools require which test</h2>
                <p class="text-ink-700 mt-2">Schools open to international applicants, grouped by the test recorded in our directory. Each school's own page carries the source.</p>
                @foreach(['UCAT' => 'UCAT required', 'UCAT/GAMSAT' => 'UCAT or GAMSAT (depends on route)', 'GAMSAT' => 'GAMSAT (graduate programmes)', 'NONE' => 'No admissions test for international applicants', 'NOT_PUBLISHED' => 'Not yet established'] as $key => $label)
                    @if(($byTest[$key] ?? collect())->isNotEmpty())
                        <h3 class="mt-6">{{ $label }} ({{ $byTest[$key]->count() }})</h3>
                        <ul class="mt-2 flex flex-wrap gap-2">@foreach($byTest[$key]->sortBy(fn($c)=>$c->university->name) as $c)<li><a href="{{ route('schools.show', $c->university) }}" class="chip chip-pending no-underline hover:bg-navy-100">{{ $c->university->name }}</a></li>@endforeach</ul>
                    @endif
                @endforeach
            </section>
            <x-cta-band title="Planning 2028 entry?" :href="route('apply.index')" label="Apply Online" :secondary-href="route('apply.eligibility')" secondary-label="Check your eligibility">Start your application now; your UCAT plan, documents and university shortlist will be ready long before the window opens.</x-cta-band>
        </div>
        <aside class="lg:col-span-4 space-y-5">
            <div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('admissions.ucas2027') }}">UCAS timeline</a></li><li><a href="{{ route('schools.index') }}?test=NONE">Schools without a test</a></li><li><a href="{{ route('requirements.alevels') }}">A-levels route</a></li></ul></div>
            <div class="card"><p class="eyebrow mb-3">About the figures</p><p class="text-[0.9375rem] text-ink-700">Dates come from the UCAT Consortium's own pages. Fees and the Nigerian centre list were not confirmed on the official page during our research and are marked accordingly; we will not show them to students until they are.</p></div>
        </aside>
    </div>
</article>
</x-layouts.public>
