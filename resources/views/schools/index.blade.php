<x-layouts.public :seo="$seo">
    <section class="container-site pt-6 pb-10">
        <div class="max-w-3xl">
            <p class="eyebrow mb-3">Directory</p>
            <h1 class="text-balance">UK medical schools: who accepts international applicants, and what they publish</h1>
            <p class="lede mt-5">Filter by nation, international eligibility, admissions test and application route. Every value shows whether it has been verified on the official page. We do not rank schools. Applying from Nigeria? Read the <a href="{{ route('medicine.nigeria') }}">guide for Nigerian applicants</a> first, then the <a href="{{ route('requirements.waec') }}">WAEC statements</a> school by school.</p>
        <x-photo slug="directory" class="mt-8 max-w-4xl" ratio="21/9" sizes="(min-width: 1024px) 56rem, 100vw" />
            <x-reviewed :date="$seo->lastReviewed" :intake="$seo->intakeYear" class="mt-4" />
        </div>

        <form method="get" action="{{ route('schools.index') }}" class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end" role="search" aria-label="Filter medical schools">
            <div class="field">
                <label for="q" class="label">Search</label>
                <input id="q" name="q" value="{{ $filters['q'] }}" class="input" placeholder="University or city">
            </div>
            <div class="field">
                <label for="nation" class="label">Nation</label>
                <select id="nation" name="nation" class="input">
                    <option value="">All</option>
                    @foreach(['England','Scotland','Wales','Northern Ireland'] as $n)<option value="{{ $n }}" @selected($filters['nation']===$n)>{{ $n }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="international" class="label">International applicants</label>
                <select id="international" name="international" class="input">
                    <option value="">All</option>
                    <option value="accepts" @selected($filters['international']==='accepts')>Accepted</option>
                    <option value="international_only" @selected($filters['international']==='international_only')>International only</option>
                    <option value="home_only" @selected($filters['international']==='home_only')>Home students only</option>
                    <option value="not_published" @selected($filters['international']==='not_published')>Not yet established</option>
                </select>
            </div>
            <div class="field">
                <label for="test" class="label">Admissions test</label>
                <select id="test" name="test" class="input">
                    <option value="">All</option>
                    @foreach(['UCAT'=>'UCAT','GAMSAT'=>'GAMSAT','UCAT/GAMSAT'=>'UCAT or GAMSAT','NONE'=>'No test'] as $v=>$l)<option value="{{ $v }}" @selected($filters['test']===$v)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-secondary flex-1">Filter</button>
                @if($isFiltered)<a href="{{ route('schools.index') }}" class="btn btn-tertiary">Clear</a>@endif
            </div>
        </form>

        <p class="mt-6 text-[0.875rem] text-ink-500" aria-live="polite">{{ $universities->count() }} {{ Str::plural('school', $universities->count()) }} shown. Statuses: <x-verified-badge status="VERIFIED" /> <x-verified-badge status="VERIFY-ON-PAGE" /> <x-verified-badge status="NOT_PUBLISHED" /></p>

        <ul class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($universities as $u)
                @php $c = $u->primaryCourse(); $fee = $c?->internationalFee(); $places = $u->fact('international_places'); @endphp
                <li class="card flex flex-col">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-serif font-semibold leading-tight"><a href="{{ route('schools.show', $u) }}" class="no-underline hover:underline text-ink-900">{{ $u->name }}</a></h2>
                            <p class="text-[0.875rem] text-ink-500 mt-1">{{ $u->medical_school_name ?? '' }}{{ $u->city ? ' · '.$u->city : '' }}{{ $u->nation ? ', '.$u->nation : '' }}</p>
                        </div>
                        @switch($u->international_policy)
                            @case('accepts') <span class="chip chip-verified">International: yes</span> @break
                            @case('international_only') <span class="chip chip-info">International only</span> @break
                            @case('home_only') <span class="chip chip-danger">Home students only</span> @break
                            @default <span class="chip chip-notpublished">International: not established</span>
                        @endswitch
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-[0.875rem]">
                        <div><dt class="text-ink-500">Course</dt><dd class="font-medium">{{ $c?->title ?? 'Medicine' }}{{ $c?->shortUcasCode() ? ' · '.$c->shortUcasCode() : '' }}</dd></div>
                        <div><dt class="text-ink-500">Admissions test</dt><dd class="font-medium">{{ match($c?->admissions_test) { 'UCAT' => 'UCAT', 'GAMSAT' => 'GAMSAT', 'UCAT/GAMSAT' => 'UCAT or GAMSAT', 'NONE' => 'None required', default => 'Not established' } }}</dd></div>
                        <div><dt class="text-ink-500">Application route</dt><dd class="font-medium">{{ match($c?->application_route) { 'UCAS' => 'UCAS', 'DIRECT' => 'Direct to university', 'BOTH' => 'UCAS or direct', default => 'Not established' } }}</dd></div>
                        <div>
                            <dt class="text-ink-500">International fee</dt>
                            <dd class="font-medium">
                                @if($fee && $fee->isPublishable() && $fee->value_number)
                                    {{ $fee->displayValue() }}<span class="text-ink-500 font-normal"> /yr{{ $fee->academic_year ? ' · '.$fee->academic_year : '' }}</span>
                                @elseif($fee && $fee->verification_status === 'NOT_PUBLISHED')
                                    <span class="text-ink-500 font-normal">Not published</span>
                                @else
                                    <span class="text-ink-500 font-normal">Being verified</span>
                                @endif
                            </dd>
                        </div>
                        @if($places && $places->isPublishable() && $places->displayValue())
                            <div class="col-span-2"><dt class="text-ink-500">International places</dt><dd class="font-medium">{{ $places->displayValue() }}</dd></div>
                        @endif
                        @php $waecFact = $u->fact('waec_neco_statement'); $waecShown = $waecFact && $waecFact->isPublishable() && ! in_array($waecFact->verification_status, ['NOT_FOUND', 'NOT_PUBLISHED'], true); @endphp
                        <div class="col-span-2"><dt class="text-ink-500">WAEC / NECO statement</dt><dd class="font-medium">{{ $waecShown ? 'Published by the university' : 'No Nigeria-specific statement located' }}</dd></div>
                    </dl>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        @if($fee)<x-verified-badge :status="$fee->verification_status" :date="$fee->verified_at?->format('j M Y')" />@endif
                        @if($u->publicGmcStatus() && stripos($u->publicGmcStatus(), 'review') !== false)<span class="chip chip-review">GMC: new school under review</span>@endif
                    </div>
                    <div class="mt-5 pt-4 border-t border-ink-100 flex items-center justify-between gap-3">
                        <a href="{{ route('schools.show', $u) }}" class="btn btn-tertiary">View what the university publishes</a>
                        <a href="{{ route('apply.index') }}" class="btn btn-primary btn-sm">Apply Online</a>
                    </div>
                </li>
            @endforeach
        </ul>

        <x-alert type="info" class="mt-10 max-w-3xl" title="How to read this directory">
            Values marked <em>Verification pending</em> were located on official pages during research but have not yet been re-read on the page itself. In production they are hidden until a reviewer confirms them. "Not published" means the university's pages did not state it; confirm directly with the university.
        </x-alert>
    </section>
    <section class="container-site pb-4">
        <x-cta-band title="Ready to begin your application?">Create your account, tell us your qualifications, and we will build your document checklist for the schools you are considering.</x-cta-band>
    </section>
</x-layouts.public>
