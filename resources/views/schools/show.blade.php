<x-layouts.public :seo="$seo">
    @php
        $fee = $course?->internationalFee();
        $facts = $university->facts->whereNotIn('key', ['research_notes'])->reject(fn ($f) => str_starts_with($f->key, 'source_') || $f->verification_status === 'NOT_FOUND');
        $labels = [
            'international_accepted' => 'International applicants accepted', 'international_places' => 'International places', 'gmc_status' => 'GMC status',
            'msc_member' => 'Medical Schools Council member', 'english_language_requirement' => 'English language requirement',
            'waec_neco_statement' => 'What the university says about WAEC / NECO', 'a_level_requirement' => 'A-level / IB requirement',
            'gem_international' => 'Graduate entry for international applicants', 'english_requirement' => 'English language requirement (Nigeria page)',
            'foundation_route' => 'Foundation route', 'international_places_open' => 'International places / eligibility',
            'ucas_code' => 'UCAS code', 'course_length_years' => 'Course length (years)', 'admissions_test' => 'Admissions test', 'interview_format' => 'Interview format',
            'application_route' => 'Application route', 'intake_month' => 'Intake', 'graduate_entry_course' => 'Graduate-entry course', 'international_fee_gbp' => 'International tuition fee (per year)', 'clinical_years_fee_differs' => 'Clinical years charged at a different fee',
        ];
        $order = array_keys($labels);
        $sorted = fn ($col) => $col->sortBy(fn ($f) => array_search($f->key, $order) === false ? 99 : array_search($f->key, $order));
        $publishable = fn ($f) => $f->isPublishable();
    @endphp
    <article class="container-site pt-6 pb-10">
        <header class="max-w-3xl">
            <p class="eyebrow mb-3">{{ $university->medical_school_name ?? 'Medical school' }}{{ $university->city ? ' · '.$university->city : '' }}{{ $university->nation ? ', '.$university->nation : '' }}</p>
            <h1 class="text-balance">{{ $university->name }}: Medicine for international applicants</h1>
            <p class="lede mt-5">What this university publishes for international applicants to {{ $course?->title ?? 'Medicine' }}{{ $course?->ucas_code ? ' ('.$course->ucas_code.')' : '' }}, with each statement's official source. Where a Nigerian-specific requirement is not published, we say so.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @switch($university->international_policy)
                    @case('accepts') <span class="chip chip-verified">International applicants: accepted</span> @break
                    @case('international_only') <span class="chip chip-info">International applicants only</span> @break
                    @case('home_only') <span class="chip chip-danger">Home students only — not open to international applicants</span> @break
                    @default <span class="chip chip-notpublished">International eligibility not yet established</span>
                @endswitch
                @if($university->gmc_status)<span class="chip {{ stripos($university->gmc_status,'review')!==false ? 'chip-review' : 'chip-pending' }}">GMC: {{ $university->gmc_status }}</span>@endif
            </div>
            <x-reviewed :date="$seo->lastReviewed" :intake="$seo->intakeYear" class="mt-4" />
        </header>

        @if($university->gmc_status && stripos($university->gmc_status, 'review') !== false)
            <x-alert type="warning" class="mt-8 max-w-3xl" title="New medical school">
                The General Medical Council lists this school among new schools under review; it is not yet an awarding body in its own right. Check the GMC's current position and the university's statement about which institution awards the degree before applying.
            </x-alert>
        @endif

        <div class="mt-10 grid lg:grid-cols-12 gap-10">
            <div class="lg:col-span-8 space-y-10">
                <section aria-labelledby="h-course">
                    <h2 id="h-course">Course facts</h2>
                    <div class="mt-4 space-y-3">
                        @forelse($sorted(($course?->facts ?? collect())->reject(fn ($f) => $f->verification_status === 'NOT_FOUND')) as $f)
                            <x-fact :status="$f->verification_status" :source="$f->source_url" :verified-at="$f->verified_at?->format('j M Y')">
                                <p class="text-[0.8125rem] text-ink-500">{{ $labels[$f->key] ?? Str::headline($f->key) }}@if($f->academic_year) · {{ $f->academic_year }}@endif</p>
                                <p class="font-medium text-lg">{{ $f->displayValue() ?? '—' }}@if($f->key==='international_fee_gbp' && $f->value_number)<span class="text-ink-500 font-normal text-base"> per year</span>@endif</p>
                                @if($f->notes)<p class="mt-1 text-[0.875rem] text-ink-500">{{ $f->notes }}</p>@endif
                            </x-fact>
                        @empty
                            <p class="text-ink-500">No course facts recorded yet.</p>
                        @endforelse
                    </div>
                </section>

                <section aria-labelledby="h-nigeria">
                    <h2 id="h-nigeria">For Nigerian applicants: what the university publishes</h2>
                    @php $nig = $sorted($facts->whereIn('key', ['waec_neco_statement','a_level_requirement','english_requirement','english_language_requirement','foundation_route','gem_international','international_places_open'])); @endphp
                    @if($nig->isEmpty())
                        <x-alert type="info" class="mt-4" title="No Nigeria-specific statement located">We did not find a published statement about WAEC, NECO or Nigerian qualifications for Medicine at this university. Treat this as <em>not published</em> and confirm directly with the admissions team.</x-alert>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach($nig as $f)
                                <x-fact :status="$f->verification_status" :source="$f->source_url" :verified-at="$f->verified_at?->format('j M Y')">
                                    <p class="text-[0.8125rem] text-ink-500">{{ $labels[$f->key] ?? Str::headline($f->key) }}</p>
                                    <p class="mt-1">{{ $f->displayValue() }}</p>
                                    @if($f->notes)<p class="mt-1 text-[0.875rem] text-ink-500">{{ $f->notes }}</p>@endif
                                </x-fact>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section aria-labelledby="h-other">
                    <h2 id="h-other">Other recorded facts</h2>
                    <div class="mt-4 space-y-3">
                        @foreach($sorted($facts->whereIn('key', ['international_accepted','international_places','msc_member'])) as $f)
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
            ['label' => 'All medical schools', 'url' => route('schools.index'), 'description' => 'Back to the directory'],
        ]" />
    </article>
</x-layouts.public>
