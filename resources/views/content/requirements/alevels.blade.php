<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements · standard entry', 'title' => 'A-levels for UK Medicine from Nigeria: grades, subjects and how international applicants are assessed', 'lede' => 'Standard-entry Medicine (A100) is offered on A-levels or the IB. Typical offers are AAA to A*AA including Chemistry and Biology, or IB 36 to 38 with 6s and 7s in Higher Level sciences. Cambridge International A-levels taken in Nigeria are assessed in the same way as UK A-levels, subject to each school\'s published subject rules. Here is what schools publish, how to read an offer, and how the A-level years and the UCAT fit together for a student in Nigeria.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section><h2>1. What schools publish ({{ $reqs->count() }} records)</h2>
                <p class="mt-2 text-ink-700">Grade and subject requirements located on official pages. "Verify" in a note means the figure was seen in a search result or a secondary source and is awaiting confirmation on the page; we show the status rather than hide the uncertainty.</p>
                @include('content._statement-list', ['items' => $reqs])
            </section>

            <section class="prose-site">
                <h2>2. How to read an offer</h2>
                <ul>
                    <li><strong>AAA, A*AA and the position of the A*.</strong> Where a school asks for A*AA it usually also says which subject must carry the A* (often Chemistry or Biology). Achieving the grades in the wrong subjects does not meet the offer.</li>
                    <li><strong>Chemistry is required almost everywhere</strong>; Biology is required or strongly expected; the third subject is usually free, though some schools exclude General Studies, Critical Thinking or a second Mathematics.</li>
                    <li><strong>Achieved versus predicted.</strong> Most applicants apply with predicted grades in their final A-level year; a few schools also state how they treat applicants who already hold results, resits, or more than two sittings. The policy is on each school's page.</li>
                    <li><strong>The GCSE layer sits underneath.</strong> Several schools also require specified grades at GCSE level in English, Mathematics and the sciences. For a Nigerian applicant that layer is the WASSCE or NECO; the grades schools ask for are on the <a href="{{ route('requirements.waec') }}">WAEC page</a>.</li>
                    <li><strong>International applicants are ranked separately at many schools</strong>, because international places are capped. The academic threshold is usually the same as for home applicants; the <a href="{{ route('admissions.ucat') }}">UCAT</a> then decides who is interviewed.</li>
                </ul>
            </section>

            <section class="prose-site">
                <h2>3. Cambridge International A-levels taken in Nigeria</h2>
                <p>UK medical schools treat Cambridge International (CAIE) A-levels as A-levels: the same grades, the same subject rules, the same timing. Three practical differences matter. <strong>Sittings:</strong> Cambridge International offers June and November series; a November final sitting puts your results after the UCAS deadline, so you would apply on predicted grades and confirm later, which schools accept but which leaves less margin. <strong>Predicted grades</strong> come from your college, and a school that is not a UCAS registered centre must still provide them through your referee. <strong>Practical endorsements</strong> in science A-levels differ between boards; where a school requires a pass in the practical component, check how your board reports it.</p>
                <h2>4. The International Baccalaureate as the alternative</h2>
                <p>Schools that publish IB requirements typically ask for 36 to 38 points overall with Chemistry and Biology (or another science) at Higher Level grade 6 or 7. IB is offered by a smaller number of schools in Nigeria; the IB and A-level routes are equivalent in the eyes of medical schools, so choose on the basis of the school you can attend, not on any supposed preference.</p>
            </section>

            <section class="prose-site">
                <h2>5. The A-level years and the UCAT, in order</h2>
                <ol>
                    <li><strong>Lower sixth (first A-level year):</strong> choose Chemistry, Biology and a third subject; arrange work experience or shadowing in Nigeria that you can reflect on; sit or plan your English test if needed.</li>
                    <li><strong>Summer between the two years:</strong> register and sit the UCAT (the window below); this is the single step students in Nigeria most often miss.</li>
                    <li><strong>Upper sixth, September to mid-October:</strong> submit UCAS with up to four medicine choices on predicted grades.</li>
                    <li><strong>December to March:</strong> interviews; <strong>by May:</strong> offers; <strong>June or November:</strong> final results, then conditions met, deposit, CAS and visa.</li>
                </ol>
                <dl class="card not-prose mt-4">
                    <x-fact-row :fact="$ucat?->fact('testing_window')" label="UCAT testing window (2027 entry)" />
                    <x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="UCAS medicine deadline (2027 entry)" />
                </dl>
            </section>

            <section class="prose-site">
                <h2>6. Choosing where to take A-levels in Nigeria</h2>
                <p>Several colleges in Lagos, Abuja and elsewhere offer Cambridge International A-levels. We do not rank or recommend providers and we have no arrangement with any. Ask for the college's Cambridge centre number, its recent grade distribution in Chemistry and Biology, whether it is a UCAS registered centre or will provide predicted grades and a reference as an independent referee, and whether it supports UCAT preparation and timing. Keep the written answers.</p>
            </section>

            <section>
                <h2>Questions about the A-level route</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach
                </div>
            </section>

            <x-cta-band title="On the A-level route?" :href="route('admissions.ucat')" label="Plan your UCAT" :secondary-href="route('apply.eligibility')" secondary-label="Check your eligibility">The UCAT window falls in July–September between your two A-level years. Plan it now.</x-cta-band>
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria: the guide</a></li><li><a href="{{ route('admissions.ucat') }}">UCAT from Nigeria</a></li><li><a href="{{ route('requirements.waec') }}">WAEC and the GCSE layer</a></li><li><a href="{{ route('requirements.english') }}">English requirements</a></li><li><a href="{{ route('medicine.foundation') }}">Foundation routes instead of A-levels</a></li><li><a href="{{ route('schools.index') }}?international=accepts">Directory</a></li><li><a href="{{ route('fees.index') }}">Fee guide</a></li></ul></div></aside>
    </div>
    <x-route-map current="requirements" />
</article>
</x-layouts.public>
