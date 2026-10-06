<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements · Nigerian qualifications', 'title' => 'NECO and UK Medicine: what medical schools say about the NECO SSCE', 'lede' => 'Many statements from UK universities name WASSCE without NECO. Where a statement names NECO, it names it alongside WAEC, and none we reviewed accepts NECO alone as the entry qualification for standard-entry Medicine. This page lists every statement we hold that names NECO, where NECO English counts as English-language evidence, and the route a NECO holder can take. If a school is silent, confirm with its admissions team before you rely on it.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section class="prose-site">
                <h2>1. The short answer</h2>
                <p>No UK medical school in our review offers direct entry to the standard Medicine degree on NECO alone, just as none does on WAEC alone. Your NECO SSCE is your school-leaving certificate; UK schools then expect A-levels, the International Baccalaureate, a foundation programme that publishes Medicine as a destination, or a degree. The question that actually varies by school is narrower: <em>does the school name NECO at all, and does it accept NECO English in place of IELTS?</em></p>
            </section>

            <section>
                <h2>2. Statements that name NECO ({{ $mentionsNeco->count() }})</h2>
                @include('content._statement-list', ['items' => $mentionsNeco, 'empty' => 'None of the statements we have recorded names NECO explicitly. Treat NECO as WASSCE and confirm with the school.'])
            </section>

            <section>
                <h2>3. Where NECO English is accepted as English-language evidence ({{ $english->count() }})</h2>
                <p class="mt-2 text-ink-700">Grade thresholds differ: one school may ask for C6, another for B3, and a general university rule may exclude Medicine. Each row shows the published wording and its status.</p>
                @include('content._statement-list', ['items' => $english, 'empty' => 'No English-language statement naming NECO has been recorded yet; see the English requirements page for IELTS bands.'])
                <p class="mt-3 text-[0.9375rem] text-ink-700">The visa is a separate test: for a Student visa, UKVI may require a Secure English Language Test even where the university accepts NECO English. The <a href="{{ route('requirements.english') }}">English requirements page</a> sets out both layers.</p>
            </section>

            <section class="prose-site">
                <h2>4. Statements that do not name NECO ({{ $others }})</h2>
                <p>These statements refer to WAEC or WASSCE without naming NECO. In practice UK universities that recognise WASSCE at GCSE level generally recognise the NECO SSCE on the same terms, but that is an inference, not a published rule: ask the school to confirm in writing and keep the reply. The full list, with sources, is on the <a href="{{ route('requirements.waec') }}">WAEC page</a>.</p>
            </section>

            <section class="prose-site">
                <h2>5. NECO and WAEC side by side for a UK application</h2>
                <ul>
                    <li><strong>Level.</strong> Both are Senior School Certificate examinations. Read each statement in section 2 for how a school places them: some place them at GCSE level and ask for a foundation year, A-levels or the IB on top, and a few publish other routes, such as a National Diploma or HND.</li>
                    <li><strong>Wording.</strong> Schools write "WAEC", "WASSCE", "SSCE" or "WAEC/NECO". Only the schools in section 2 write "NECO".</li>
                    <li><strong>English.</strong> Where NECO English is accepted, the grade asked for is published per school (section 3) and may not apply to Medicine. The IELTS band each school asks for is on the English requirements page.</li>
                    <li><strong>Documents.</strong> You will upload the certificate or statement of result; universities may verify it with the examining body. Your name must match your passport exactly.</li>
                    <li><strong>Mixed results.</strong> If you hold both, enter both as certificated; ask any school that names only WASSCE how it treats NECO.</li>
                </ul>
            </section>

            <section class="prose-site">
                <h2>6. Your route with NECO</h2>
                <p>Identical to the WAEC route: A-levels or IB and then standard entry with the UCAT, or a <a href="{{ route('medicine.foundation') }}">foundation programme that publishes Medicine as a destination</a>, or a degree first and then <a href="{{ route('requirements.gem') }}">graduate or standard entry as a graduate</a>. Of the UK medical schools in our directory, {{ $accepting }} accept international undergraduate applicants; the <a href="{{ route('schools.index') }}?international=accepts">directory</a> shows what each publishes, and its <a href="{{ route('schools.index') }}?waec=published">WAEC/NECO filter</a> narrows it to schools with a published statement. Our <a href="{{ route('apply.eligibility') }}">eligibility check</a> maps your answers to these routes.</p>
            </section>

            <section>
                <h2>Questions NECO holders ask</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach
                </div>
            </section>

            <x-cta-band title="Not sure whether your Nigerian qualifications meet the requirements?" :href="route('apply.eligibility')" label="Check your eligibility" />
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria: the guide</a></li><li><a href="{{ route('requirements.waec') }}">WAEC and UK Medicine</a></li><li><a href="{{ route('requirements.english') }}">English requirements (incl. NECO English)</a></li><li><a href="{{ route('medicine.foundation') }}">Foundation routes</a></li><li><a href="{{ route('requirements.alevels') }}">A-levels for UK Medicine</a></li></ul></div></aside>
    </div>
    <x-route-map current="qualifications" />
</article>
</x-layouts.public>
