<x-layouts.public :seo="$seo">
    <section class="container-site pt-6 pb-10">
        <header class="bleed -mt-6 bg-paper-warm border-b border-ink-200 pt-8 pb-24 sm:pt-10">
            <x-journey current="universities" class="mb-8 hidden md:block" />
            <div class="grid lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-7">
                    <p class="eyebrow mb-4 flex items-center gap-2"><x-icon name="school" :size="16" class="text-navy-500" />Directory</p>
                    <h1 class="text-balance">UK medical schools: who accepts international applicants, and what they publish</h1>
                    <p class="lede mt-5 text-pretty">Filter by nation, international eligibility, admissions test and application route. Every value shows whether it has been verified on the official page. We do not rank schools. Applying from Nigeria? Read the <a href="{{ route('medicine.nigeria') }}">guide for Nigerian applicants</a> first, then the <a href="{{ route('requirements.waec') }}">WAEC statements</a> school by school.</p>
                    <x-reviewed :date="$seo->lastReviewed" :intake="$seo->intakeYear" class="mt-5" />
                    @php $byNation = $all->groupBy('nation'); @endphp
                    <ul class="mt-7 flex flex-wrap gap-2" aria-label="Browse by nation">
                        <li><a href="{{ route('schools.index') }}" class="chip no-underline {{ $isFiltered ? 'bg-paper ring-1 ring-ink-200 text-ink-700' : 'bg-navy-700 text-white' }} py-2 px-3.5">All {{ $all->count() }}</a></li>
                        @foreach(['England', 'Scotland', 'Wales', 'Northern Ireland'] as $n)
                            @if($byNation->has($n))<li><a href="{{ route('schools.index') }}?nation={{ urlencode($n) }}" class="chip no-underline py-2 px-3.5 {{ $filters['nation'] === $n ? 'bg-navy-700 text-white' : 'bg-paper ring-1 ring-ink-200 text-ink-700 hover:ring-navy-300' }}">{{ $n }} <span class="opacity-70">{{ $byNation[$n]->count() }}</span></a></li>@endif
                        @endforeach
                        <li><a href="{{ route('schools.index') }}?international=accepts" class="chip no-underline py-2 px-3.5 {{ $filters['international'] === 'accepts' ? 'bg-navy-700 text-white' : 'bg-success-100 text-success-600 hover:ring-1 hover:ring-success-600/40' }}"><x-icon name="check" :size="13" />Admit international applicants</a></li>
                    </ul>
                </div>
                <div class="hidden lg:block lg:col-span-5">
                    <x-uk-map :universities="$all" class="w-full max-w-[22rem] mx-auto h-auto" />
                    <p class="mt-2 text-center text-[0.75rem] text-ink-500">Dark pins: schools that publish that they admit international applicants. Larger pins: several schools in one city.</p>
                </div>
            </div>
        </header>

        <form method="get" action="{{ route('schools.index') }}" class="relative -mt-14 card-raised grid gap-4 grid-cols-2 lg:grid-cols-6 items-end" role="search" aria-label="Filter medical schools">
            <div class="field col-span-2 lg:col-span-1">
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
                <label for="international" class="label">International</label>
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
            <div class="field">
                <label for="waec" class="label">WAEC / NECO</label>
                <select id="waec" name="waec" class="input">
                    <option value="">Any</option>
                    <option value="published" @selected($filters['waec']==='published')>Published by the university</option>
                </select>
            </div>
            <div class="flex gap-2 col-span-2 lg:col-span-1">
                <button type="submit" class="btn btn-secondary flex-1 min-h-12"><x-icon name="search" :size="17" />Filter</button>
                @if($isFiltered)<a href="{{ route('schools.index') }}" class="btn btn-tertiary">Clear</a>@endif
            </div>
        </form>

        <p class="mt-8 text-[0.875rem] text-ink-500" aria-live="polite"><strong class="text-ink-900 font-semibold">{{ $universities->count() }} {{ Str::plural('school', $universities->count()) }}</strong> shown. Statuses: <x-verified-badge status="VERIFIED" /> <x-verified-badge status="VERIFY-ON-PAGE" /> <x-verified-badge status="NOT_PUBLISHED" /></p>

        <ul class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($universities as $u)
                @php $c = $u->primaryCourse(); $fee = $c?->internationalFee(); $places = $u->fact('international_places'); @endphp
                <li class="card card-hover flex flex-col">
                    <p class="text-[0.75rem] font-semibold uppercase tracking-[0.1em] text-ink-500 flex items-center gap-1"><x-icon name="map-pin" :size="13" />{{ $u->city }}{{ $u->nation ? ', '.$u->nation : '' }}</p>
                    <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-xl font-serif font-semibold leading-tight"><a href="{{ route('schools.show', $u) }}" class="stretched-link no-underline text-ink-900">{{ $u->name }}</a></h2>
                            <p class="text-[0.875rem] text-ink-500 mt-1">{{ $u->medical_school_name ?? '' }}</p>
                        </div>
                        @switch($u->international_policy)
                            @case('accepts') <span class="chip chip-verified">International: yes</span> @break
                            @case('international_only') <span class="chip chip-info">International only</span> @break
                            @case('home_only') <span class="chip chip-danger">Home students only</span> @break
                            @default <span class="chip chip-notpublished">International: not established</span>
                        @endswitch
                    </div>
                    <dl class="mt-5 pt-4 border-t border-ink-100 grid grid-cols-2 gap-x-4 gap-y-3 text-[0.875rem]">
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
                    <div class="mt-4 flex flex-wrap items-center gap-2 relative z-10">
                        @if($fee)<x-verified-badge :status="$fee->verification_status" :date="$fee->verified_at?->format('j M Y')" />@endif
                        @if($u->publicGmcStatus() && stripos($u->publicGmcStatus(), 'review') !== false)<span class="chip chip-review">GMC: new school under review</span>@endif
                    </div>
                    <p class="mt-auto pt-5 text-[0.9375rem] font-semibold text-navy-700 inline-flex items-center gap-1" aria-hidden="true">View what the university publishes<x-icon name="arrow-right" :size="16" /></p>
                </li>
            @endforeach
        </ul>

        <x-alert type="info" class="mt-10 max-w-3xl" title="How to read this directory">
            Values marked <em>Verification pending</em> were located on official pages during research but have not yet been re-read on the page itself. In production they are hidden until a reviewer confirms them. "Not published" means the university's pages did not state it; confirm directly with the university.
        </x-alert>
    </section>
    <section class="container-site pb-4">
        <x-cta-band title="Ready to begin your application?">Create your account, tell us your qualifications, and we will build your document checklist for the schools you are considering.</x-cta-band>
        <x-route-map current="universities" />
    </section>
</x-layouts.public>
