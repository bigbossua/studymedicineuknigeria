<x-layouts.public :seo="$seo">
    @php
        $fee = $course?->internationalFee();
        $facts = $university->facts->whereNotIn('key', ['research_notes'])->reject(fn ($f) => str_starts_with($f->key, 'source_') || $f->verification_status === 'NOT_FOUND');
        $labels = [
            'international_accepted' => 'International applicants accepted', 'international_places' => 'International places', 'gmc_status' => 'GMC status',
            'msc_member' => 'Medical Schools Council member', 'english_language_requirement' => 'English language requirement',
            'waec_neco_statement' => 'What the university says about WAEC / NECO', 'a_level_requirement' => 'A-level / IB requirement',
            'gem_international' => 'Graduate entry for international applicants', 'english_requirement' => 'English language requirement',
            'foundation_route' => 'Foundation route', 'international_places_open' => 'International places / eligibility',
            'ucas_code' => 'UCAS code', 'course_length_years' => 'Course length (years)', 'admissions_test' => 'Admissions test', 'interview_format' => 'Interview format',
            'application_route' => 'Application route', 'intake_month' => 'Intake', 'graduate_entry_course' => 'Graduate-entry course', 'international_fee_gbp' => 'International tuition fee (per year)', 'clinical_years_fee_differs' => 'Clinical years charged at a different fee',
        ];
        $order = array_keys($labels);
        $sorted = fn ($col) => $col->sortBy(fn ($f) => array_search($f->key, $order) === false ? 99 : array_search($f->key, $order));
        $publishable = fn ($f) => $f->isPublishable();
    @endphp
    <article class="container-site pt-6 pb-10">
        <header class="bleed -mt-6 bg-paper-warm border-b border-ink-200 pt-8 pb-10 sm:pt-10 sm:pb-14">
            <x-journey current="universities" class="mb-8 hidden md:block" />
            <div class="grid lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-8 max-w-3xl">
            <p class="eyebrow mb-4 flex items-center gap-2"><x-icon name="map-pin" :size="16" class="text-navy-500" />{{ $university->medical_school_name ?? 'Medical school' }}{{ $university->city ? ' · '.$university->city : '' }}{{ $university->nation ? ', '.$university->nation : '' }}</p>
            <h1 class="text-balance">{{ $university->name }}: Medicine for international applicants</h1>
            <p class="lede mt-5">What this university publishes for international applicants to {{ $course?->title ?? 'Medicine' }}{{ $course?->shortUcasCode() ? ' ('.$course->shortUcasCode().')' : '' }}, with each statement's official source. Where a Nigerian-specific requirement is not published, we say so.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @switch($university->international_policy)
                    @case('accepts') <span class="chip chip-verified">International applicants: accepted</span> @break
                    @case('international_only') <span class="chip chip-info">International applicants only</span> @break
                    @case('home_only') <span class="chip chip-danger">Home students only — not open to international applicants</span> @break
                    @default <span class="chip chip-notpublished">International eligibility not yet established</span>
                @endswitch
                @if($university->publicGmcStatus())<span class="chip {{ stripos($university->publicGmcStatus(),'review')!==false ? 'chip-review' : 'chip-pending' }}">GMC: {{ $university->publicGmcStatus() }}</span>@endif
            </div>
            <x-reviewed :date="$seo->lastReviewed" :intake="$seo->intakeYear" class="mt-4" />
                </div>
                <div class="hidden lg:block lg:col-span-4">
                    <x-uk-map :universities="[$university]" :highlight="$university->slug" :label="$university->city" class="w-full max-w-[16rem] mx-auto h-auto" />
                </div>
            </div>
        </header>

        @if($university->publicGmcStatus() && stripos($university->publicGmcStatus(), 'review') !== false)
            <x-alert type="warning" class="mt-8 max-w-3xl" title="New medical school">
                The General Medical Council lists this school among new schools under review; it is not yet an awarding body in its own right. Check the GMC's current position and the university's statement about which institution awards the degree before applying.
            </x-alert>
        @endif

        <div class="mt-10 grid lg:grid-cols-12 gap-10">
            <div class="lg:col-span-8 space-y-10">
                <section aria-labelledby="h-course">
                    <h2 id="h-course">Course facts</h2>
                    <div class="mt-5 grid sm:grid-cols-2 gap-3">
                        @forelse($sorted(($course?->facts ?? collect())->reject(fn ($f) => $f->verification_status === 'NOT_FOUND')->filter($publishable)) as $f)
                            <x-fact :status="$f->verification_status" :source="$f->source_url" :verified-at="$f->verified_at?->format('j M Y')">
                                <p class="text-[0.8125rem] text-ink-500">{{ $labels[$f->key] ?? Str::headline($f->key) }}@if($f->academic_year) · {{ $f->academic_year }}@endif</p>
                                <p class="font-medium text-lg">{{ $f->displayValue() ?? '—' }}@if($f->key==='international_fee_gbp' && $f->value_number)<span class="text-ink-500 font-normal text-base"> per year</span>@endif</p>
                            </x-fact>
                        @empty
                            <p class="text-ink-500">No course facts recorded yet.</p>
                        @endforelse
                    </div>
                </section>

                <section aria-labelledby="h-nigeria">
                    <h2 id="h-nigeria">For Nigerian applicants: what the university publishes</h2>
                    @php $nigAll = $facts->whereIn('key', ['waec_neco_statement','a_level_requirement','english_requirement','english_language_requirement','foundation_route','gem_international','international_places_open']); $nig = $sorted($nigAll->filter($publishable)); @endphp
                    @if($nig->isEmpty() && $nigAll->isNotEmpty())
                        <x-alert type="info" class="mt-4" title="Statement being checked">We have recorded what this university publishes for applicants like you and are checking it against the official page; it appears here once verified. Until then, confirm directly with the admissions team.</x-alert>
                    @elseif($nig->isEmpty())
                        <x-alert type="info" class="mt-4" title="No Nigeria-specific statement located">We did not find a published statement about WAEC, NECO or Nigerian qualifications for Medicine at this university. Treat this as <em>not published</em> and confirm directly with the admissions team.</x-alert>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach($nig as $f)
                                <x-fact :status="$f->verification_status" :source="$f->source_url" :verified-at="$f->verified_at?->format('j M Y')">
                                    <p class="text-[0.8125rem] text-ink-500">{{ $labels[$f->key] ?? Str::headline($f->key) }}</p>
                                    <p class="mt-1">{{ $f->displayValue() }}</p>
                                </x-fact>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section aria-labelledby="h-meaning" class="prose-site">
                    <h2 id="h-meaning">What this means if you are applying from Nigeria</h2>
                    @php
                        $waecF = $university->fact('waec_neco_statement'); $engF = $university->fact('english_requirement') ?? $university->fact('english_language_requirement');
                        $foundF = $university->fact('foundation_route'); $gemF = $university->fact('gem_international'); $placesF = $university->fact('international_places');
                        // A statement counts as published only when it is also publishable here: in production an unverified fact is hidden above, so the guidance must not refer to it.
                        $published = fn ($f) => $f && $f->isPublishable() && ! in_array($f->verification_status, ['NOT_FOUND', 'NOT_PUBLISHED'], true) && trim((string) $f->value_text) !== '' && ! str_starts_with(trim((string) $f->value_text), 'n/a');
                    @endphp
                    <ul>
                        @switch($university->international_policy)
                            @case('home_only')<li><strong>Not open to you.</strong> This school publishes that it admits home students only; it is listed so that you do not spend an application choice on it. The <a href="{{ route('schools.index') }}?international=accepts">directory filter</a> shows the schools that do admit international applicants.</li>@break
                            @case('international_only')<li><strong>Open, and international-only</strong> for the current cohort according to the university's published pages. Check the application route and fee below; there is no home-student comparison to rely on.</li>@break
                            @case('accepts')<li><strong>Open to international applicants</strong>{{ $published($placesF) ? ' with a published number of international places: '.$placesF->displayValue().'.' : '; the number of international places is not published in our records.' }} International applicants are usually ranked against each other, so the admissions test matters as much as grades.</li>@break
                            @default<li><strong>International eligibility not yet established</strong> in our records. Ask the admissions team the one question that matters before anything else: does the school consider applicants who need a Student visa?</li>
                        @endswitch
                        <li><strong>Your WAEC or NECO:</strong> {{ $published($waecF) ? 'the university addresses it (statement above). Read the exact wording: in every case we have recorded, WASSCE or NECO is the GCSE layer and the entry qualification is A-levels, the IB, a recognised foundation year or a degree.' : 'no Nigeria-specific statement located; treat WASSCE or NECO as the GCSE layer and plan on A-levels, the IB, a foundation year that leads to Medicine, or a degree.' }} <a href="{{ route('requirements.waec') }}">WAEC statements for every school</a>.</li>
                        <li><strong>English:</strong> {{ $published($engF) ? 'the published requirement is above; where it names WAEC or NECO English, confirm that the rule applies to Medicine, which usually carries a higher bar.' : 'no school-specific statement located; Medicine typically asks for IELTS 7.0 to 7.5 with component minimums.' }} <a href="{{ route('requirements.english') }}">English requirements</a>.</li>
                        <li><strong>Foundation route:</strong> {{ $published($foundF) ? 'a foundation programme is named above; progression to Medicine counts only where the university publishes it.' : 'no foundation route to Medicine is published for international applicants in our records.' }} <a href="{{ route('medicine.foundation') }}">Foundation routes that publish Medicine as a destination</a>.</li>
                        <li><strong>Graduates:</strong> {{ $published($gemF) ? 'the graduate-entry statement is above.' : 'graduate-entry eligibility for international applicants is not established here; graduates can usually apply to the standard course on degree class plus the admissions test.' }} <a href="{{ route('requirements.gem') }}">Graduate entry with a Nigerian degree</a>.</li>
                    </ul>
                </section>

                <section aria-labelledby="h-apply">
                    <h2 id="h-apply">How to apply to this school</h2>
                    <dl class="card mt-4">
                        <div class="grid sm:grid-cols-12 gap-y-1 py-2 border-b border-ink-100"><dt class="sm:col-span-4 text-ink-500 text-[0.875rem]">Application route</dt><dd class="sm:col-span-8 font-medium">{{ match($course?->application_route) { 'UCAS' => 'UCAS, by the medicine deadline', 'DIRECT' => 'Directly to the university, on its own calendar', 'BOTH' => 'UCAS or directly to the university', default => 'Not established' } }}</dd></div>
                        <div class="grid sm:grid-cols-12 gap-y-1 py-2 border-b border-ink-100"><dt class="sm:col-span-4 text-ink-500 text-[0.875rem]">Admissions test</dt><dd class="sm:col-span-8 font-medium">{{ match($course?->admissions_test) { 'UCAT' => 'UCAT, sat the summer before you apply', 'GAMSAT' => 'GAMSAT', 'UCAT/GAMSAT' => 'UCAT or GAMSAT depending on route', 'NONE' => 'No admissions test published for international applicants', default => 'Not established' } }}</dd></div>
                        @if(($course?->application_route ?? null) !== 'DIRECT')<x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="UCAS medicine deadline (2027 entry)" />@endif
                        @if(in_array($course?->admissions_test, ['UCAT', 'UCAT/GAMSAT'], true))<x-fact-row :fact="$ucat?->fact('testing_window')" label="UCAT testing window (2027 entry)" />@endif
                    </dl>
                    <p class="mt-3 text-[0.9375rem] text-ink-700">The full process, including references, the three-question statement and document upload, is on <a href="{{ route('admissions.howto') }}">how to apply</a>; dates are on the <a href="{{ route('admissions.ucas2027') }}">timeline</a>. Our service prepares and checks your application with you; the university alone decides.</p>
                </section>

                <section aria-labelledby="h-other">
                    <h2 id="h-other">Other recorded facts</h2>
                    <div class="mt-4 space-y-3">
                        @foreach($sorted($facts->whereIn('key', ['international_accepted','international_places','msc_member'])->filter($publishable)) as $f)
                            <x-fact :status="$f->verification_status" :source="$f->source_url" :verified-at="$f->verified_at?->format('j M Y')">
                                <p class="text-[0.8125rem] text-ink-500">{{ $labels[$f->key] ?? Str::headline($f->key) }}</p>
                                <p class="font-medium">{{ $f->displayValue() ?? '—' }}</p>
                            </x-fact>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="lg:col-span-4 space-y-5">
                <div class="card-raised">
                    <p class="eyebrow mb-3">Official sources</p>
                    <ul class="space-y-2 text-[0.875rem] break-words">
                        @foreach($university->facts->filter(fn($f)=>str_starts_with($f->key,'source_'))->pluck('value_text')->merge($university->courses->pluck('official_url'))->filter()->unique()->take(8) as $url)
                            <li><a href="{{ $url }}" rel="noopener nofollow" target="_blank">{{ Str::limit(preg_replace('#^https?://(www\.)?#','',$url), 60) }} ↗</a></li>
                        @endforeach
                    </ul>
                    <p class="mt-4 text-[0.8125rem] text-ink-500">We link to the university's own pages. We are not affiliated with {{ $university->name }}.</p>
                </div>
                <div class="card">
                    <p class="eyebrow mb-3">Next step</p>
                    <p class="text-[0.9375rem] text-ink-700">Tell us your qualifications and we will show which routes appear open on published requirements, including whether this school is realistic for you.</p>
                    <a href="{{ route('apply.eligibility') }}" class="btn btn-primary mt-4 w-full">Check your eligibility</a>
                </div>
            </aside>
        </div>

        <x-related :items="[
            ['label' => 'WAEC and UK Medicine', 'url' => route('requirements.waec'), 'description' => 'What each medical school says about WASSCE'],
            ['label' => 'Fee guide', 'url' => route('fees.index'), 'description' => 'International fees by school, with fee years'],
            ['label' => 'UCAT for Nigerian students', 'url' => route('admissions.ucat'), 'description' => 'Windows, fees and test centres'],
            ['label' => 'How to apply: UCAS or direct', 'url' => route('admissions.howto'), 'description' => 'The application route this school uses, step by step'],
            ['label' => 'All medical schools', 'url' => route('schools.index'), 'description' => 'Back to the directory'],
        ]" />
        <x-route-map current="universities" />
    </article>
</x-layouts.public>
