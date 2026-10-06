<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Admissions · timeline', 'title' => 'UCAS deadlines and timeline for Medicine, 2027 entry, and how to plan 2028', 'lede' => 'Every date that matters, in order, for an applicant in Nigeria. Medicine has its own UCAS deadline, earlier than the main one, and the UCAT must be sat before it, so the whole year hinges on two windows. Each date below shows its official source and whether we have verified it on the page.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section><h2>1. 2027 entry: the official dates</h2>
                <dl class="card mt-4">
                    <x-fact-row :fact="$ucas?->fact('applications_open')" label="UCAS applications open" />
                    <x-fact-row :fact="$ucat?->fact('registration_window')" label="UCAT registration" />
                    <x-fact-row :fact="$ucat?->fact('testing_window')" label="UCAT testing window" />
                    <x-fact-row :fact="$ucas?->fact('submission_opens')" label="UCAS submissions open" />
                    <x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="Medicine deadline (equal consideration)" />
                    <x-fact-row :fact="$ucat?->fact('results_to_universities')" label="UCAT results to universities" />
                    <x-fact-row :fact="$ucas?->fact('deadline_main')" label="Main deadline (other courses)" />
                    <x-fact-row :fact="$ucas?->fact('extra_opens')" label="UCAS Extra opens" />
                    <x-fact-row :fact="$ucas?->fact('clearing_opens')" label="Clearing opens" />
                    <x-fact-row :fact="$ucas?->fact('final_date')" label="Final date to add choices" />
                </dl>
            </section>

            <section class="prose-site">
                <h2>2. What each deadline means for you</h2>
                <ul>
                    <li><strong>The medicine deadline is an equal-consideration deadline.</strong> Applications received by it must be considered; later applications may be considered only at a school's discretion. Treat it as absolute.</li>
                    <li><strong>The UCAT must be complete before it.</strong> Results reach universities after the deadline (date above), so you apply knowing your score but the schools receive it directly; you do not enter it in UCAS.</li>
                    <li><strong>The January main deadline does not apply to medicine</strong>, except to your fifth, non-medicine choice if you add one.</li>
                    <li><strong>Extra and Clearing.</strong> They exist; do not plan around Extra or Clearing for Medicine.</li>
                    <li><strong>Deferred entry.</strong> Some schools allow a 2027 applicant to defer to 2028 with a 2026 UCAT score; each school's policy differs and is on its page where published.</li>
                </ul>
            </section>

            <section class="prose-site">
                <h2>3. Your document timeline alongside the dates</h2>
                <ol>
                    <li><strong>Before UCAS applications open (date above):</strong> confirm your route on the <a href="{{ route('requirements.index') }}">requirements hub</a>; book an <a href="{{ route('requirements.english') }}">English test</a> if your shortlist needs one, allowing for a second sitting.</li>
                    <li><strong>When UCAT registration opens:</strong> register for the <a href="{{ route('admissions.ucat') }}">UCAT</a> the day registration opens and book a test centre slot (the official Pearson VUE locator lists centres) the day booking opens.</li>
                    <li><strong>In the UCAT testing window:</strong> sit the UCAT early in the window; agree your referee; gather certificates, transcripts and translations for upload.</li>
                    <li><strong>Before the medicine deadline:</strong> finalise four medicine choices against each school's published UCAT use, write the three statement answers, submit with the fee, and allow your referee time (<a href="{{ route('admissions.howto') }}">how to apply</a>).</li>
                </ol>
            </section>

            <section class="prose-site">
                <h2>4. What happens after you apply</h2>
                <ol><li><strong>Interviews:</strong> dates and formats are published by each school (some in person, some online).</li><li><strong>Decisions:</strong> you reply to offers by the UCAS deadline for your decision date, choosing a firm and an insurance choice.</li><li><strong>Before the course:</strong> meet conditions (final grades, English test), pay the university's international deposit, receive your Confirmation of Acceptance for Studies (CAS).</li><li><strong>When GOV.UK allows</strong> (see the official <a href="https://www.gov.uk/student-visa" rel="noopener" target="_blank">Student visa page</a>): apply for the Student visa with CAS, maintenance evidence and a TB test certificate; attend biometrics; receive the decision; travel for the September start.</li></ol>
                <dl class="card not-prose mt-4">
                    <x-fact-row :fact="$visa?->fact('tb_test')" label="TB test" />
                    <x-fact-row :fact="$visa?->fact('maintenance_outside_london_monthly_gbp')" label="Maintenance funds to show (outside London, per month)" suffix=" per month" />
                    <x-fact-row :fact="$visa?->fact('maintenance_london_monthly_gbp')" label="Maintenance funds to show (London, per month)" suffix=" per month" />
                </dl>
                <p class="mt-4">The tightest stretch for an applicant in Nigeria is the gap between CAS issue and course start: maintenance funds must usually be held for a set period before the application, TB clinic and biometric appointments need booking, and a refusal leaves no time to reapply. Start the money and documents early; the <a href="{{ route('fees.total') }}">total cost page</a> lists every fee in the sequence.</p>
            </section>

            <section class="prose-site">
                <h2>5. Planning 2028 entry from Nigeria</h2>
                <ul><li><strong>Now:</strong> confirm your qualification route, shortlist <a href="{{ route('schools.index') }}?international=accepts">schools that accept international applicants</a>, book an English test if needed, and start work experience or shadowing you can reflect on.</li><li><strong>UCAT registration for 2028 entry:</strong> when the UCAT Consortium publishes the dates (we add them with sources), register on the first day and book a test centre slot as soon as booking opens.</li><li><strong>In that cycle's testing window:</strong> sit the UCAT early in the window.</li><li><strong>Before that cycle's medicine deadline:</strong> submit UCAS with up to four medicine choices.</li><li><strong>If the UCAS route is closed to you for 2027:</strong> the <a href="{{ route('admissions.howto') }}">direct-application schools</a> run their own calendars; check each school's page.</li></ul>
                <p class="text-[0.9375rem] text-ink-500">Dates for 2028 entry are published by UCAS and the UCAT Consortium; we will add them with sources when they appear.</p>
            </section>

            <section>
                <h2>Questions about timing</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach
                </div>
            </section>

            <x-cta-band title="Start now, submit when the window opens" :href="route('apply.index')" label="Apply Online">Your application, documents and shortlist are prepared in your portal; nothing is sent to any university until you approve it.</x-cta-band>
        </div>
        <aside class="lg:col-span-4 space-y-5"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria: the guide</a></li><li><a href="{{ route('admissions.ucat') }}">UCAT from Nigeria</a></li><li><a href="{{ route('admissions.howto') }}">How to apply</a></li><li><a href="{{ route('fees.total') }}">Total cost</a></li><li><a href="{{ route('faq.index') }}">Questions applicants ask</a></li></ul></div></aside>
    </div>
    <x-route-map current="application" />
</article>
</x-layouts.public>
