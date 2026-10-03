<x-layouts.public :seo="$seo">
    {{-- Hero --}}
    <section class="bg-paper-warm border-b border-ink-200">
        <div class="container-site py-14 sm:py-20 lg:py-24 grid lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7">
                <p class="eyebrow mb-4"><span class="inline-block w-6 h-[2px] bg-nigeria-green align-middle mr-2"></span>Nigeria → United Kingdom · Medicine</p>
                <h1 class="text-balance">Study Medicine in the UK from Nigeria</h1>
                <p class="lede mt-6 max-w-[34rem]">Understand your options, check requirements, prepare your documents and apply online.</p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('apply.index') }}" class="btn btn-primary btn-lg">Apply Online</a>
                    <a href="{{ route('apply.eligibility') }}" class="btn btn-secondary btn-lg">Check your eligibility</a>
                </div>
                <p class="mt-6 text-[0.875rem] text-ink-500 max-w-[34rem]">Independent application support. Every requirement, fee and deadline we publish names its official source and shows the date we last verified it.</p>
            </div>
            <div class="lg:col-span-5">
                <x-photo slug="home-hero" class="mb-6 hidden lg:block" ratio="4/3" :priority="true" sizes="(min-width: 1024px) 40vw, 100vw" />
                <div class="card-raised">
                    <p class="eyebrow mb-4">Where most Nigerian applicants start</p>
                    <ol class="space-y-3 text-[0.9375rem]">
                        @foreach([
                            ['Requirements', 'Which routes are open with WAEC, NECO, A-levels or a Nigerian degree', route('requirements.index')],
                            ['Fees &amp; costs', 'International tuition by medical school, with fee years and sources', route('fees.index')],
                            ['Medical schools', 'Which of the UK’s medical schools accept international applicants', route('schools.index')],
                            ['UCAT &amp; UCAS', 'Test windows, deadlines and what a missed window means', route('admissions.index')],
                            ['Apply Online', 'A structured application with a personal document checklist', route('apply.index')],
                        ] as $i => [$label, $desc, $url])
                            <li class="flex gap-4">
                                <span class="font-mono text-ink-500 text-sm pt-0.5">0{{ $i + 1 }}</span>
                                <div><a href="{{ $url }}" class="font-semibold no-underline hover:underline">{!! $label !!}</a><span class="block text-ink-500">{{ $desc }}</span></div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </section>

    {{-- Honest orientation --}}
    <section class="container-site py-16 sm:py-20">
        <div class="grid lg:grid-cols-12 gap-10">
            <div class="lg:col-span-5">
                <p class="eyebrow mb-3">Before anything else</p>
                <h2 class="text-balance">Three things to know before you plan a UK medical degree</h2>
                <p class="mt-4 text-ink-700">We publish what universities publish, university by university, and we say plainly where no information exists. Specific requirements, fees and dates appear on their own pages with sources and verification dates.</p>
                <a href="{{ route('medicine.nigeria') }}" class="btn btn-secondary mt-5">Read the full guide for applicants from Nigeria</a>
            </div>
            <div class="lg:col-span-7 grid gap-4 sm:grid-cols-1">
                <div class="card">
                    <h3>Your secondary-school results are usually the first layer, not the entry ticket</h3>
                    <p class="mt-2 text-ink-700">In our review of published UK medical school pages, none offered direct entry to the standard medicine degree on WASSCE or NECO alone. Universities that address Nigeria route applicants through A-levels, the IB, a recognised foundation year or part of a degree. The detail differs by school, which is why we show each school’s own statement.</p>
                    <a href="{{ route('requirements.waec') }}" class="btn btn-tertiary mt-3">What each medical school says about WAEC and NECO</a>
                </div>
                <div class="card">
                    <h3>Medicine runs on a fixed calendar</h3>
                    <p class="mt-2 text-ink-700">Most UK medicine courses are applied for through UCAS by a mid-October deadline the year before entry, and most require the UCAT, which is sat in the summer before that. Missing one window usually means planning for the following year or looking at the small number of schools that use a different route.</p>
                    <a href="{{ route('admissions.ucas2027') }}" class="btn btn-tertiary mt-3">Deadlines and timeline for 2027 and 2028 entry</a>
                </div>
                <div class="card">
                    <h3>The cost is substantial and varies widely</h3>
                    <p class="mt-2 text-ink-700">International medicine fees differ by tens of thousands of pounds a year between schools, and clinical years often cost more than early years. Add visa, health surcharge and living costs before deciding. We publish each school’s fee with its fee year and source.</p>
                    <a href="{{ route('fees.index') }}" class="btn btn-tertiary mt-3">Fee guide for international students</a>
                </div>
            </div>
        </div>
    </section>

    {{-- What we are / are not --}}
    <section class="bg-navy-50 border-y border-ink-200">
        <div class="container-site py-14 sm:py-16 grid lg:grid-cols-12 gap-10">
            <div class="lg:col-span-5">
                <p class="eyebrow mb-3">What this service is</p>
                <h2 class="text-balance">Evidence. Guidance. Application.</h2>
            </div>
            <div class="lg:col-span-7 grid sm:grid-cols-2 gap-6 text-[0.9375rem]">
                <div>
                    <p class="font-semibold text-ink-900 mb-2">We do</p>
                    <ul class="space-y-2 text-ink-700 list-disc pl-5">
                        <li>Publish entry requirements, fees and deadlines with their official sources and verification dates</li>
                        <li>Assess your profile against published requirements and tell you which routes appear open</li>
                        <li>Build your personal document checklist and review each document you upload</li>
                        <li>Prepare your complete application and guide you through the official submission route</li>
                        <li>Track every stage in your private portal</li>
                    </ul>
                </div>
                <div>
                    <p class="font-semibold text-ink-900 mb-2">We do not</p>
                    <ul class="space-y-2 text-ink-700 list-disc pl-5">
                        <li>Guarantee admission, a scholarship or a visa</li>
                        <li>Claim partnerships or agent status with any university unless a signed agreement exists</li>
                        <li>Submit anything to a university without your explicit, recorded approval</li>
                        <li>Publish rankings, testimonials or success rates we cannot evidence</li>
                        <li>Combine our service fee with university tuition or application fees</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- How the application works --}}
    <section class="container-site py-16 sm:py-20">
        <p class="eyebrow mb-3">Apply Online</p>
        <h2 class="text-balance max-w-2xl">A structured application you can leave and return to</h2>
        <ol class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['Create your account', 'Your application has a number from day one and saves automatically. Leave for weeks and return without losing anything.'],
                ['Tell us about your education', 'WAEC or NECO, A-levels, IB, or a Nigerian degree. Grades are entered once, structured, and reused across the application.'],
                ['Upload what you have', 'Your checklist is built from your answers, so you are only asked for documents that apply to you. Each one is reviewed and accepted or returned with a reason.'],
                ['Approve, then submit', 'You review the complete package and authorise it. Only then does submission begin, by the route the university requires.'],
            ] as $i => [$h, $p])
                <li class="card">
                    <span class="font-mono text-sm text-ink-500">Step {{ $i + 1 }}</span>
                    <h3 class="mt-2">{{ $h }}</h3>
                    <p class="mt-2 text-ink-700 text-[0.9375rem]">{{ $p }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="container-site pb-4">
        <x-cta-band title="Ready to check your own situation?" :href="route('apply.eligibility')" label="Check your eligibility" :secondary-href="route('apply.index')" secondary-label="Apply Online">
            Seven questions, no account needed. You will see which routes appear open on published requirements and what to read next.
        </x-cta-band>
    </section>
</x-layouts.public>
