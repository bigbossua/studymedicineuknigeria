<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Admissions · UCAT', 'title' => 'UCAT for Nigerian students: dates, structure, fees and sitting the test in Nigeria', 'lede' => 'The UCAT is required by most UK medical schools and is sat in the summer before you apply, at Pearson VUE centres. From Nigeria the practical centres are Lagos and Abuja, and capacity is limited. Here are the facts for the 2026 cycle (2027 entry), each with its source and verification status, how to book from Nigeria, how schools use the score, and what to do if you missed it.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section><h2>1. UCAT 2026 cycle (for 2027 entry): every date that matters</h2>
                <p class="text-ink-700 mt-2">All deadlines are UK time. Nigeria (West Africa Time, UTC+1) and British Summer Time (UTC+1) coincide throughout the registration and testing window; from late October the UK is one hour behind Nigeria. Each row shows the UCAT Consortium source and whether we have verified it on the page.</p>
                <dl class="card mt-4">
                    <x-fact-row :fact="$ucat?->fact('registration_window')" label="Registration" />
                    <x-fact-row :fact="$ucat?->fact('booking_opens')" label="Booking opens" />
                    <x-fact-row :fact="$ucat?->fact('testing_window')" label="Testing window" />
                    <x-fact-row :fact="$ucat?->fact('access_arrangements_deadline')" label="Access arrangements deadline" />
                    <x-fact-row :fact="$ucat?->fact('booking_deadline')" label="Booking deadline" />
                    <x-fact-row :fact="$ucat?->fact('results_to_candidates')" label="Results to candidates" />
                    <x-fact-row :fact="$ucat?->fact('results_to_universities')" label="Results sent to universities" />
                    <x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="UCAS medicine deadline that follows" />
                </dl>
            </section>

            <section><h2>2. What the test is</h2>
                <dl class="card mt-4">
                    <x-fact-row :fact="$ucat?->fact('structure')" label="Structure and scoring" />
                    <x-fact-row :fact="$ucat?->fact('subtests')" label="Subtests, questions and timings" />
                    <x-fact-row :fact="$ucat?->fact('fee_uk_gbp')" label="Fee (UK centres)" />
                    <x-fact-row :fact="$ucat?->fact('fee_international_gbp')" label="Fee (centres outside the UK, including Nigeria)" />
                    <x-fact-row :fact="$ucat?->fact('test_centres_nigeria')" label="Test centres in Nigeria" />
                </dl>
                <div class="prose-site mt-4">
                    <p>The UCAT is a computer-based test of about two hours of reasoning and judgement, not of biology or chemistry. Since 2025 it has three cognitive subtests plus the Situational Judgement Test; Abstract Reasoning no longer exists, and the cognitive total is out of 2,700, so any "good score" you read that is out of 3,600 is from an earlier format. Your result is valid for one application cycle only: a 2026 score serves a 2027-entry (or, at some schools, a deferred 2028) application and nothing later.</p>
                </div>
            </section>

            <section class="prose-site">
                <h2>3. Booking from Nigeria, step by step</h2>
                <ol>
                    <li><strong>Register on the UCAT Consortium site the day registration opens.</strong> Use your name exactly as it appears on your international passport; the centre will refuse admission if the two differ.</li>
                    <li><strong>Create the Pearson VUE account the Consortium directs you to</strong> and, when booking opens, search centres by city. Not every Pearson VUE centre in Nigeria delivers the UCAT; the official centre search is the only reliable list.</li>
                    <li><strong>Book on the day booking opens</strong>, early in the testing window, and keep a later date in mind as a fallback. Applicants and consultancies alike report Lagos and Abuja slots going within days.</li>
                    <li><strong>Pay the international fee by card.</strong> If your Nigerian card is declined for foreign-currency transactions, arrange a dollar or pound card, or a relative's card, before booking opens. Bursaries exist for UK candidates; eligibility for candidates outside the UK is not confirmed on the official page in our records.</li>
                    <li><strong>Apply for access arrangements first if you need them</strong> (extra time or adjustments), because they must be approved before you book and have their own earlier deadline.</li>
                    <li><strong>Sit the test early in the window</strong> so a centre problem leaves time to rebook, and travel with the passport you registered with.</li>
                    <li><strong>Send your score to no one.</strong> The Consortium delivers results to the universities you apply to through UCAS in early November; you only need to enter your UCAS personal ID correctly.</li>
                </ol>
            </section>

            <section class="prose-site">
                <h2>4. How medical schools use the score</h2>
                <p>Each school publishes its own method, and the method matters more than a national average. The common patterns are: a <strong>threshold</strong> below which an application is not considered further; a <strong>weighted score</strong> in which the UCAT is combined with academic achievement to rank applicants for interview; a <strong>Situational Judgement band</strong> rule, often rejecting Band 4; and, at a number of schools, a <strong>separate ranking of international applicants</strong>, because international places are capped. Some schools publish the previous year's international threshold; where they do, it appears on the school's page in our <a href="{{ route('schools.index') }}">directory</a> with its source and date. We do not publish cut-offs we cannot source, and we do not predict this year's.</p>
            </section>

            <section><h2>5. Which medical schools require which test</h2>
                <p class="text-ink-700 mt-2">Schools open to international applicants, grouped by the test recorded in our directory. Each school's own page carries the source; "not yet established" means we have not located the statement, not that no test is required.</p>
                @foreach(['UCAT' => 'UCAT required', 'UCAT/GAMSAT' => 'UCAT or GAMSAT (depends on route)', 'GAMSAT' => 'GAMSAT (graduate programmes)', 'NONE' => 'No admissions test for international applicants', 'NOT_PUBLISHED' => 'Not yet established'] as $key => $label)
                    @if(($byTest[$key] ?? collect())->isNotEmpty())
                        <h3 class="mt-6">{{ $label }} ({{ $byTest[$key]->count() }})</h3>
                        <ul class="mt-2 flex flex-wrap gap-2">@foreach($byTest[$key]->sortBy(fn($c)=>$c->university->name) as $c)<li><a href="{{ route('schools.show', $c->university) }}" class="chip chip-pending no-underline hover:bg-navy-100">{{ $c->university->name }}</a></li>@endforeach</ul>
                    @endif
                @endforeach
            </section>

            <section class="prose-site">
                <h2>6. If you have no UCAT result today</h2>
                <p>For <strong>2027 entry</strong> the UCAT 2026 testing window has closed (dates above) and the UCAS medicine deadline falls in mid-October 2026. Schools that require the UCAT cannot consider you for 2027. Two honest options remain: apply for 2027 only to the <a href="{{ route('schools.index') }}?test=NONE">schools that do not use the UCAT for international applicants</a>, several of which take <a href="{{ route('admissions.howto') }}">direct applications</a> on their own calendars, or plan for <strong>2028 entry</strong>: register for the UCAT when registration opens in 2027, book a Lagos or Abuja slot the day booking opens, and apply through UCAS by mid-October 2027. Our <a href="{{ route('admissions.ucas2027') }}">timeline</a> lays out both.</p>
            </section>

            <section class="prose-site">
                <h2>7. Preparing without spending money you do not need to</h2>
                <p>The Consortium publishes free official practice tests and question banks in the real test interface; they are the closest thing to the exam and should be the core of any plan. Timed practice matters more than volume: each subtest is tightly timed, and most score loss comes from running out of time rather than from not knowing. Start three to four months before your test date, practise in the morning hours you will sit, and sit at least two full-length timed mocks. Paid courses exist in the UK; none is required and we recommend none.</p>
            </section>

            <section>
                <h2>Questions about the UCAT from Nigeria</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach
                </div>
            </section>

            <x-cta-band title="Planning 2028 entry?" :href="route('apply.index')" label="Apply Online" :secondary-href="route('apply.eligibility')" secondary-label="Check your eligibility">Start your application now; your UCAT plan, documents and university shortlist will be ready long before the window opens.</x-cta-band>
        </div>
        <aside class="lg:col-span-4 space-y-5">
            <div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria: the guide</a></li><li><a href="{{ route('admissions.ucas2027') }}">UCAS timeline</a></li><li><a href="{{ route('admissions.howto') }}">How to apply</a></li><li><a href="{{ route('schools.index') }}?test=NONE">Schools without a test</a></li><li><a href="{{ route('requirements.alevels') }}">A-levels route</a></li><li><a href="{{ route('requirements.gem') }}">Graduate entry (GAMSAT or UCAT)</a></li></ul></div>
            <div class="card"><p class="eyebrow mb-3">About the figures</p><p class="text-[0.9375rem] text-ink-700">Dates come from the UCAT Consortium's own pages. Fees, subtest timings and the Nigerian centre list were not confirmed on the official page during our research and are marked accordingly; we do not show them to students until they are verified.</p></div>
        </aside>
    </div>
</article>
</x-layouts.public>
