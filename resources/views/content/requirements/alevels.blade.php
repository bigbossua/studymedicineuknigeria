<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements · standard entry', 'title' => 'A-levels for UK Medicine from Nigeria: grades, subjects and how international applicants are assessed', 'lede' => 'Standard-entry Medicine (A100) is offered on A-levels or the IB. Each school publishes its own grades and subjects; the records below show them with sources. Here is what schools publish, how to read an offer, and how the A-level years and the UCAT fit together for a student in Nigeria.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section><h2>1. What schools publish ({{ $reqs->count() }} records)</h2>
                <p class="mt-2 text-ink-700">Grade and subject requirements located on official pages. Only figures confirmed on the official page are shown.</p>
                @include('content._statement-list', ['items' => $reqs])
            </section>

            <section class="prose-site">
                <h2>2. How to read an offer</h2>
                <ul>
                    <li><strong>AAA, A*AA and the position of the A*.</strong> Where a school asks for A*AA it usually also says which subject must carry the A* (often Chemistry or Biology). Achieving the grades in the wrong subjects does not meet the offer.</li>
                    <li><strong>Subject rules differ.</strong> Schools name Chemistry, Biology or both, sometimes with a second science, and some exclude particular third subjects such as General Studies or Critical Thinking; read each school's subject rule in the records above.</li>
                    <li><strong>Achieved versus predicted.</strong> Most applicants apply with predicted grades in their final A-level year; a few schools also state how they treat applicants who already hold results, resits, or more than two sittings. The policy is on each school's page.</li>
                    <li><strong>The GCSE layer sits underneath.</strong> Several schools also require specified grades at GCSE level in English, Mathematics and the sciences. For a Nigerian applicant that layer is the WASSCE or NECO; the grades schools ask for are on the <a href="{{ route('requirements.waec') }}">WAEC page</a>.</li>
                    <li><strong>How international applicants are ranked.</strong> Some schools publish that international applicants are ranked separately or by their <a href="{{ route('admissions.ucat') }}">UCAT</a> score; the school pages show each published method.</li>
                </ul>
            </section>

            <section class="prose-site">
                <h2>3. Cambridge International A-levels taken in Nigeria</h2>
                <p>The school records in our directory state A-level requirements without separate rules for Cambridge International (CAIE); where it matters, confirm with the school. Three practical points need checking. <strong>Sittings:</strong> Cambridge International offers June and November series; check each school's policy on the series and on resits before planning a November final sitting. <strong>Predicted grades</strong> come from your college, and a school that is not a UCAS registered centre must still provide them through your referee. <strong>Practical endorsements</strong> in science A-levels differ between boards; where a school requires a pass in the practical component, check how your board reports it.</p>
                <h2>4. The International Baccalaureate as the alternative</h2>
                <p>Schools that publish IB requirements state a points total and the Higher Level grades they ask for in named sciences; the records above show each school's figures. Schools that publish IB requirements accept it alongside A-levels; choose on the basis of the school you can attend.</p>
            </section>

            <section class="prose-site">
                <h2>5. The A-level years and the UCAT, in order</h2>
                <ol>
                    <li><strong>Lower sixth (first A-level year):</strong> choose Chemistry, Biology and a third subject; arrange work experience or shadowing in Nigeria that you can reflect on; sit or plan your English test if needed.</li>
                    <li><strong>Summer between the two years:</strong> register and sit the UCAT in the window below; missing it closes UCAT schools for that cycle.</li>
                    <li><strong>Upper sixth, before the UCAS medicine deadline (below):</strong> submit UCAS with up to four medicine choices on predicted grades.</li>
                    <li><strong>Winter and spring:</strong> interviews and offers; <strong>after final results:</strong> conditions met, deposit, CAS and visa.</li>
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

            <x-cta-band title="On the A-level route?" :href="route('admissions.ucat')" label="Plan your UCAT" :secondary-href="route('apply.eligibility')" secondary-label="Check your eligibility">The UCAT is sat between your two A-level years, in the testing window on the UCAT page. Plan it now.</x-cta-band>
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria: the guide</a></li><li><a href="{{ route('admissions.ucat') }}">UCAT from Nigeria</a></li><li><a href="{{ route('requirements.waec') }}">WAEC and the GCSE layer</a></li><li><a href="{{ route('requirements.english') }}">English requirements</a></li><li><a href="{{ route('medicine.foundation') }}">Foundation routes instead of A-levels</a></li><li><a href="{{ route('schools.index') }}?international=accepts">Directory</a></li><li><a href="{{ route('fees.index') }}">Fee guide</a></li></ul></div></aside>
    </div>
    <x-route-map current="requirements" />
</article>
</x-layouts.public>
