<x-layouts.public :seo="$seo">
    {{-- Hero: Nigeria → UK, the route illustrated (Natural Earth outlines, ops/design/build-maps.mjs) --}}
    <section class="relative overflow-hidden bg-navy-900 text-white">
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute -right-40 -top-40 w-[46rem] h-[46rem] rounded-full bg-navy-500/30 blur-3xl"></div>
        </div>
        <div class="container-site relative py-14 sm:py-20 lg:py-24 grid lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7">
                <p class="eyebrow eyebrow-rule text-gold-400! mb-5">Nigeria → United Kingdom · Medicine</p>
                <h1 class="text-white text-balance">Your route from Nigeria to a UK medical school</h1>
                <p class="mt-6 text-lg sm:text-xl text-white/80 leading-relaxed max-w-[36rem]">Understand your options, check requirements, prepare your documents and apply online. New to this? Start with <a href="{{ route('medicine.nigeria') }}" class="text-white decoration-gold-400 hover:decoration-white">studying Medicine in the UK from Nigeria</a>.</p>
                <div class="mt-9 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('apply.index') }}" class="btn btn-primary btn-lg">Apply Online</a>
                    <a href="{{ route('apply.eligibility') }}" class="btn btn-lg border border-white/50 text-white hover:bg-white/10">Check your eligibility</a>
                </div>
                <ul class="mt-9 grid sm:grid-cols-3 gap-4 text-[0.875rem] text-white/75 max-w-[40rem]">
                    <li class="flex gap-2"><x-icon name="badge-check" :size="18" class="text-gold-400 mt-0.5" />Every fact names its official source</li>
                    <li class="flex gap-2"><x-icon name="clock" :size="18" class="text-gold-400 mt-0.5" />Every fact shows when we last verified it</li>
                    <li class="flex gap-2"><x-icon name="shield-check" :size="18" class="text-gold-400 mt-0.5" />Independent, not an agent of any university</li>
                </ul>
            </div>
            <div class="hidden md:block lg:col-span-5">
                <img src="/images/maps/route-nigeria-uk.svg" alt="A globe showing the route from Lagos, Nigeria to London, United Kingdom" width="560" height="560" class="w-full max-w-[30rem] mx-auto h-auto drop-shadow-2xl" fetchpriority="high">
            </div>
        </div>
    </section>

    {{-- The journey: Nigeria → UK → Medicine → Evidence → Application support → Apply Online --}}
    <section class="section-tight border-b border-ink-200" aria-labelledby="journey-heading">
        <div class="container-site">
            <div class="section-head">
                <p class="eyebrow mb-3">How it fits together</p>
                <h2 id="journey-heading" class="text-balance">From your results in Nigeria to a submitted UK application</h2>
            </div>
            <ol class="mt-10 grid gap-3 grid-cols-2 sm:gap-4 lg:grid-cols-6 lg:gap-0">
                @foreach([
                    ['flag', 'Nigeria', 'Your WAEC, NECO, A-levels or degree, and what each one counts for', route('requirements.waec'), 'Qualifications'],
                    ['map-pin', 'United Kingdom', $schools->count().' medical schools in our directory, across '.$nations.' nations', route('schools.index'), 'Medical schools'],
                    ['stethoscope', 'Medicine', 'Five- or six-year degrees on a fixed annual calendar', route('medicine.index'), 'Medicine guide'],
                    ['scale', 'Evidence', 'Requirements, fees and dates with sources and verification dates', route('verify'), 'How we verify'],
                    ['clipboard-check', 'Application support', 'Profile assessment, document checklist and review', route('apply.services'), 'Our services'],
                    ['file-text', 'Apply Online', 'A structured application you can leave and return to', route('apply.index'), 'Start'],
                ] as $i => [$icon, $title, $text, $href, $cta])
                    <li class="card card-hover p-4! sm:p-6! lg:rounded-none lg:border-r-0 lg:first:rounded-l-lg lg:last:rounded-r-lg lg:last:border-r flex flex-col @if($i === 5) bg-navy-50 @endif">
                        <div class="flex items-center justify-between">
                            <span class="icon-badge @if($i === 5) bg-accent-600! text-white! ring-0! @endif"><x-icon :name="$icon" :size="22" /></span>
                            <span class="font-mono text-[0.75rem] text-ink-500">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <h3 class="mt-4 text-lg">{{ $title }}</h3>
                        <p class="mt-1.5 text-[0.875rem] text-ink-700 flex-1">{{ $text }}</p>
                        <a href="{{ $href }}" class="stretched-link mt-4 text-[0.875rem] font-semibold no-underline inline-flex items-center gap-1">{{ $cta }}<x-icon name="arrow-right" :size="15" /></a>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Medicine: the flagship --}}
    <section class="section section-warm" aria-labelledby="medicine-heading">
        <div class="container-site grid lg:grid-cols-12 gap-10 lg:gap-14">
            <div class="lg:col-span-5">
                <p class="eyebrow eyebrow-rule mb-4">Medicine</p>
                <h2 id="medicine-heading" class="text-balance">Built around one degree, explained school by school</h2>
                <p class="mt-5 text-lg text-ink-700 leading-relaxed">UK medical degrees are five or six years long, lead to provisional registration with the General Medical Council after a licensing assessment, and are applied for through a fixed annual calendar. For a Nigerian applicant there are three ways in.</p>
                <a href="{{ route('medicine.index') }}" class="btn btn-secondary btn-lg mt-7">Read the Medicine guide</a>
            </div>
            <div class="lg:col-span-7 grid gap-4">
                @foreach([
                    ['graduation-cap', 'Standard entry', 'The five-year degree (UCAS code A100 at most schools) for school leavers with A-levels, the IB or equivalents. The route most Nigerian applicants take; almost every school requires the UCAT.', route('requirements.alevels'), 'A-levels and IB for Medicine'],
                    ['layers', 'Foundation and gateway routes', 'A preparatory year before Medicine. Many are home-only widening-participation schemes, but a few international foundation programmes publish Medicine as a destination.', route('medicine.foundation'), 'Foundation routes into Medicine'],
                    ['book-open', 'Graduate entry', 'A four-year accelerated course for people who already hold a degree. Most programmes are home-only and only some accept international applicants.', route('requirements.gem'), 'Graduate entry with a Nigerian degree'],
                ] as [$icon, $title, $text, $href, $cta])
                    <article class="card card-hover flex gap-5">
                        <span class="icon-badge icon-badge-lg shrink-0"><x-icon :name="$icon" :size="26" /></span>
                        <div>
                            <h3>{{ $title }}</h3>
                            <p class="mt-1.5 text-ink-700">{{ $text }}</p>
                            <a href="{{ $href }}" class="stretched-link mt-3 inline-flex items-center gap-1 font-semibold text-[0.9375rem] no-underline">{{ $cta }}<x-icon name="arrow-right" :size="16" /></a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The directory, mapped --}}
    <section class="section" aria-labelledby="directory-heading">
        <div class="container-site grid lg:grid-cols-12 gap-10 lg:gap-16 items-center">
            <div class="lg:col-span-5 order-2 lg:order-1">
                <div class="relative max-w-[26rem] mx-auto">
                    <x-uk-map :universities="$schools" />
                    <p class="mt-3 text-[0.8125rem] text-ink-500 flex flex-wrap gap-x-4 gap-y-1 justify-center">
                        <span class="inline-flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full bg-navy-700"></span>Publishes that it admits international applicants</span>
                        <span class="inline-flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full bg-[#8A9AAA]"></span>Home-only or not yet established</span>
                    </p>
                </div>
            </div>
            <div class="lg:col-span-7 order-1 lg:order-2">
                <p class="eyebrow eyebrow-rule mb-4">UK medical schools</p>
                <h2 id="directory-heading" class="text-balance">Every UK medical school, and what each one publishes for international applicants</h2>
                <p class="mt-5 text-lg text-ink-700 leading-relaxed">Admissions policy, admissions test, application route, international fee and any statement about WAEC or NECO, each with its official source. We do not rank schools, and we never present a gap in what is published as a yes or a no.</p>
                <dl class="mt-8 grid grid-cols-2 sm:grid-cols-3 gap-6">
                    <div><dt class="sr-only">Medical schools in the directory</dt><dd class="stat-value">{{ $schools->count() }}</dd><dd class="stat-label">medical schools and programmes in the directory</dd></div>
                    <div><dt class="sr-only">Admit international applicants</dt><dd class="stat-value">{{ $accepting }}</dd><dd class="stat-label">publish that they admit international undergraduates</dd></div>
                    <div><dt class="sr-only">Nations</dt><dd class="stat-value">{{ $nations }}</dd><dd class="stat-label">UK nations: England, Scotland, Wales and Northern Ireland</dd></div>
                </dl>
                <div class="mt-9 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('schools.index') }}" class="btn btn-secondary btn-lg">Browse the directory</a>
                    <a href="{{ route('schools.index') }}?international=accepts" class="btn btn-tertiary btn-lg">Schools that admit international applicants</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Honest orientation --}}
    <section class="section section-warm border-y border-ink-200" aria-labelledby="know-heading">
        <div class="container-site">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div class="section-head">
                    <p class="eyebrow mb-3">Before anything else</p>
                    <h2 id="know-heading" class="text-balance">Three things to know before you plan a UK medical degree</h2>
                    <p>We publish what universities publish, university by university, and we say plainly where no information exists.</p>
                </div>
                <a href="{{ route('medicine.nigeria') }}" class="btn btn-secondary shrink-0">Read the full guide for applicants from Nigeria</a>
            </div>
            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @foreach([
                    ['graduation-cap', 'Your secondary-school results are usually the first layer, not the entry ticket', 'In our review of published UK medical school pages, none offered direct entry to the standard medicine degree on WASSCE or NECO alone. Universities that address Nigeria route applicants through A-levels, the IB, a recognised foundation year or part of a degree. The detail differs by school, which is why we show each school’s own statement.', route('requirements.waec'), 'What each medical school says about WAEC and NECO'],
                    ['calendar-days', 'Medicine runs on a fixed calendar', 'Most UK medicine courses are applied for through UCAS by a mid-October deadline the year before entry, and most require the UCAT, which is sat in the summer before that. Missing one window usually means planning for the following year or looking at the small number of schools that use a different route.', route('admissions.ucas2027'), 'Deadlines and timeline for 2027 and 2028 entry'],
                    ['pound-sterling', 'The cost is substantial and varies widely', 'International medicine fees differ by tens of thousands of pounds a year between schools, and clinical years often cost more than early years. Add visa, health surcharge and living costs before deciding. We publish each school’s fee with its fee year and source.', route('fees.index'), 'Fee guide for international students'],
                ] as [$icon, $title, $text, $href, $cta])
                    <article class="card card-hover flex flex-col">
                        <span class="icon-badge"><x-icon :name="$icon" :size="22" /></span>
                        <h3 class="mt-5 text-balance">{{ $title }}</h3>
                        <p class="mt-2 text-ink-700 text-[0.9375rem] flex-1">{{ $text }}</p>
                        <a href="{{ $href }}" class="stretched-link mt-5 inline-flex items-center gap-1 font-semibold text-[0.9375rem] no-underline">{{ $cta }}<x-icon name="arrow-right" :size="16" /></a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- What we are / are not --}}
    <section class="section section-navy" aria-labelledby="service-heading">
        <div class="container-site grid lg:grid-cols-12 gap-10 lg:gap-14">
            <div class="lg:col-span-5 section-head">
                <p class="eyebrow mb-3">What this service is</p>
                <h2 id="service-heading" class="text-balance">Evidence. Guidance. Application.</h2>
                <p>An independent service, not an agent of any university. <a href="{{ route('about') }}">About us</a>, <a href="{{ route('status') }}">our status</a> and <a href="{{ route('verify') }}">how we verify</a> say exactly what we are and how we check what we publish; the <a href="{{ route('medicine.index') }}">Medicine pillar</a> explains how the routes, requirements, costs and calendar fit together.</p>
            </div>
            <div class="lg:col-span-7 grid sm:grid-cols-2 gap-5 text-[0.9375rem]">
                <div class="rounded-lg bg-white/5 ring-1 ring-white/10 p-6">
                    <p class="font-semibold text-white mb-4 flex items-center gap-2"><x-icon name="check" :size="18" class="text-[#7FD1A8]" />We do</p>
                    <ul class="space-y-3 text-white/80">
                        @foreach(['Publish entry requirements, fees and deadlines with their official sources and verification dates', 'Assess your profile against published requirements and tell you which routes appear open', 'Build your personal document checklist and review each document you upload', 'Prepare your complete application and guide you through the official submission route', 'Track every stage in your private portal'] as $line)
                            <li class="flex gap-3"><x-icon name="check" :size="16" class="text-[#7FD1A8] mt-1" />{{ $line }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-lg bg-white/5 ring-1 ring-white/10 p-6">
                    <p class="font-semibold text-white mb-4 flex items-center gap-2"><x-icon name="x" :size="18" class="text-[#F2A7A4]" />We do not</p>
                    <ul class="space-y-3 text-white/80">
                        @foreach(['Guarantee admission, a scholarship or a visa', 'Claim partnerships or agent status with any university unless a signed agreement exists', 'Submit anything to a university without your explicit, recorded approval', 'Publish rankings, testimonials or success rates we cannot evidence', 'Combine our service fee with university tuition or application fees'] as $line)
                            <li class="flex gap-3"><x-icon name="x" :size="16" class="text-[#F2A7A4] mt-1" />{{ $line }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- How the application works --}}
    <section class="section" aria-labelledby="apply-heading">
        <div class="container-site">
            <div class="section-head">
                <p class="eyebrow mb-3">Apply Online</p>
                <h2 id="apply-heading" class="text-balance">A structured application you can leave and return to</h2>
            </div>
            <div class="relative mt-12">
            <div class="hidden lg:block absolute top-6 left-6 right-6 h-px bg-ink-200" aria-hidden="true"></div>
            <ol class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6 relative">
                @foreach([
                    ['user-round', 'Create your account', 'Your application has a number from day one and saves automatically. Leave for weeks and return without losing anything.'],
                    ['graduation-cap', 'Tell us about your education', 'WAEC or NECO, A-levels, IB, or a Nigerian degree. Grades are entered once, structured, and reused across the application.'],
                    ['files', 'Upload what you have', 'Your checklist is built from your answers, so you are only asked for documents that apply to you. Each one is reviewed and accepted or returned with a reason.'],
                    ['badge-check', 'Approve, then submit', 'You review the complete package and authorise it. Only then does submission begin, by the route the university requires.'],
                ] as $i => [$icon, $h, $p])
                    <li class="relative">
                        <span class="relative z-10 inline-flex items-center justify-center w-12 h-12 rounded-full bg-navy-700 text-white ring-8 ring-paper"><x-icon :name="$icon" :size="22" /></span>
                        <p class="mt-5 font-mono text-[0.75rem] text-ink-500">Step {{ $i + 1 }}</p>
                        <h3 class="mt-1">{{ $h }}</h3>
                        <p class="mt-2 text-ink-700 text-[0.9375rem]">{{ $p }}</p>
                    </li>
                @endforeach
            </ol>
            </div>
        </div>
    </section>

    <section class="container-site">
        <x-cta-band title="Ready to check your own situation?" :href="route('apply.eligibility')" label="Check your eligibility" :secondary-href="route('apply.index')" secondary-label="Apply Online">
            Seven questions, no account needed. You will see which routes appear open on published requirements and what to read next.
        </x-cta-band>
    </section>
</x-layouts.public>
