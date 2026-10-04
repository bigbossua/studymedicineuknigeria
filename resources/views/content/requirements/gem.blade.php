<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements · graduate entry', 'title' => 'Graduate Entry Medicine in the UK with a Nigerian degree', 'lede' => 'Four-year graduate-entry programmes (UCAS codes A101, A102, A109) exist at many UK schools, but most are home-only and only some accept applicants who need a Student visa. Many Nigerian graduates instead apply to the standard five-year course, where graduates are assessed on degree class plus the admissions test. This page shows what schools publish, and marks plainly where nothing is published yet.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section class="prose-site">
                <h2>1. Two routes for a graduate, and why most Nigerian graduates take the second</h2>
                <ul>
                    <li><strong>Graduate-entry medicine (GEM), four years.</strong> Designed for people who already hold a degree. Places are small, many programmes are funded for home students only, and each school publishes separately whether it admits international applicants. Where a school says nothing, treat the programme as not confirmed for you.</li>
                    <li><strong>Standard entry (A100), five or six years, as a graduate.</strong> Open at most schools that admit international applicants. Your degree replaces or supplements A-level grades (for example, <a href="{{ route('schools.show', 'kent-medway') }}">Kent and Medway</a> publishes a UK 2:1-equivalent rule for graduate international applicants), and you sit the <a href="{{ route('admissions.ucat') }}">UCAT</a> like every other applicant. Fees are the standard international rate for five years.</li>
                </ul>
                <p>Because a GEM place is a smaller target, the practical plan for most applicants is to apply to GEM programmes that are confirmed open to international applicants <em>and</em> to standard-entry courses in the same UCAS cycle.</p>
            </section>

            <section>
                <h2>2. Graduate-entry programmes that publish international eligibility ({{ $gem->count() }})</h2>
                <p class="mt-2 text-ink-700">Statements located on official pages. A statement that reads "yes" is what the university publishes, with its source; it is not an offer or a guarantee.</p>
                @include('content._statement-list', ['items' => $gem, 'empty' => 'No graduate-entry statements for international applicants have been recorded yet.'])
            </section>

            <section>
                <h2>3. Graduate-entry courses in the directory ({{ $gemCourses->count() }})</h2>
                <p class="mt-2 text-ink-700">Grouped by what the university publishes about international applicants in general. "Not established" means we have not located a statement, not that the answer is no.</p>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach($gemCourses as $c)
                        @php $fee = $c->facts->where('key', 'international_fee_gbp')->sortByDesc('academic_year')->first(); @endphp
                        <li class="card">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('schools.show', $c->university) }}" class="font-semibold">{{ $c->university->name }}</a>
                                @switch($c->university->international_policy)
                                    @case('accepts') <span class="chip chip-verified">International: yes</span> @break
                                    @case('international_only') <span class="chip chip-info">International only</span> @break
                                    @case('home_only') <span class="chip chip-danger">Home students only</span> @break
                                    @default <span class="chip chip-notpublished">Not established</span>
                                @endswitch
                            </div>
                            <p class="text-[0.9375rem] text-ink-500 mt-1">{{ $c->title }}{{ $c->shortUcasCode() ? ' · '.$c->shortUcasCode() : '' }}{{ $c->admissions_test ? ' · '.$c->admissions_test : '' }}</p>
                            @if($fee)
                                <p class="text-[0.875rem] mt-2">International fee: @if($fee->isPublishable() && $fee->value_number)<span class="font-semibold">{{ $fee->displayValue() }}</span> <span class="text-ink-500">/yr{{ $fee->academic_year ? ' · '.$fee->academic_year : '' }}</span>@elseif($fee->verification_status === 'NOT_PUBLISHED')<span class="text-ink-500">not published</span>@else<span class="text-ink-500">being verified</span>@endif <x-verified-badge :status="$fee->verification_status" :date="$fee->verified_at?->format('j M Y')" /></p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>

            @if($unknown->isNotEmpty())
            <section>
                <h2>4. Programmes located, international eligibility not yet established ({{ $unknown->count() }})</h2>
                <p class="mt-2 text-ink-700">These universities publish a graduate-entry programme page, but our research has not yet located a statement on whether applicants who need a Student visa are considered. We list them so you can ask the admissions team the one question that matters, rather than assume either answer.</p>
                <ul class="mt-4 columns-2 gap-6 text-[0.9375rem]">
                    @foreach($unknown as $f)<li class="break-inside-avoid"><a href="{{ route('schools.show', $f->subject) }}">{{ $f->subject->name }}</a> <span class="text-ink-500">· {{ Str::before($f->value_text, ';') }}</span></li>@endforeach
                </ul>
            </section>
            @endif

            <section class="prose-site">
                <h2>5. How your Nigerian degree is compared</h2>
                <ul>
                    <li><strong>Comparability.</strong> UK universities compare overseas degrees using UK ENIC statements and their own country guidance. A Nigerian bachelor's degree at Second Class Upper is commonly treated as comparable to a UK 2:1 and Second Class Lower to a 2:2, but this is an inference from general practice, not a rule: each medical school decides, and some publish a minimum class or CGPA for your country. Ask the school in writing before you rely on it.</li>
                    <li><strong>Subject.</strong> Some graduate-entry programmes require a science or health-related first degree; others accept any discipline (Chester publishes "any subject"). Standard entry usually still expects science at school-leaving level (Chemistry and Biology or another science), so a non-science graduate may need those qualifications too.</li>
                    <li><strong>Recency and transcripts.</strong> Expect to supply certified transcripts and, for some schools, a statement of comparability. Your name must match your passport on every document.</li>
                    <li><strong>Healthcare experience.</strong> Several graduate programmes publish an hours requirement (Chester: 70 hours in the last three years). Experience gained in Nigeria counts where the school's wording allows; read the published criteria for each programme.</li>
                </ul>
            </section>

            <section class="prose-site">
                <h2>6. GAMSAT or UCAT: fixed windows either way</h2>
                <p>Graduate programmes use the UCAT, the GAMSAT, or accept either; the directory records what each course publishes. Both tests have fixed sittings and registration deadlines months before the UCAS deadline. UCAT dates for the current cycle are recorded as facts on the <a href="{{ route('admissions.ucat') }}">UCAT page</a>; GAMSAT sittings are published by ACER and are not yet recorded as verified facts here, so check the official GAMSAT site before you plan. Pearson VUE centres in Nigeria for the UCAT fill quickly: book when registration opens.</p>
                @if($ucat)
                <dl class="card not-prose mt-4">
                    <x-fact-row :fact="$ucat->fact('testing_window')" label="UCAT testing window (2027 entry)" />
                    <x-fact-row :fact="$ucat->fact('test_centres_nigeria')" label="UCAT test centres in Nigeria" />
                </dl>
                @endif
            </section>

            <section class="prose-site">
                <h2>7. Cost: four years at a graduate-entry fee, or five at the standard rate</h2>
                <p>Graduate-entry fees are published per programme and sometimes differ between year one and the clinical years; the cards above show every published figure we hold with its fee year and verification status. Compare the four-year total with five years at a standard-entry international fee from the <a href="{{ route('fees.index') }}">fee guide</a>, then add visa, health surcharge and living costs on the <a href="{{ route('fees.total') }}">total cost page</a>. Scholarships for international graduate-entry medicine are rare; we list none because none has been verified.</p>
            </section>

            <section>
                <h2>Questions graduates ask</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach
                </div>
            </section>

            <x-cta-band title="Hold a Nigerian degree?" :href="route('apply.eligibility')" label="Check your eligibility">We will show which graduate and standard-entry routes appear open on published requirements, and which schools still need a direct question.</x-cta-band>
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria: the guide</a></li><li><a href="{{ route('schools.index') }}?test=GAMSAT">Schools using the GAMSAT</a></li><li><a href="{{ route('schools.index') }}?international=accepts">Schools accepting international applicants</a></li><li><a href="{{ route('fees.index') }}">Fee guide</a></li><li><a href="{{ route('admissions.howto') }}">How to apply</a></li><li><a href="{{ route('requirements.english') }}">English requirements</a></li></ul></div></aside>
    </div>
    <x-route-map current="qualifications" />
</article>
</x-layouts.public>
