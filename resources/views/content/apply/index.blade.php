<x-layouts.public :seo="$seo" :hide-floating-cta="true">
<section class="bg-paper-warm border-b border-ink-200">
    <div class="container-site py-14 sm:py-20 grid lg:grid-cols-12 gap-10 items-center">
        <div class="lg:col-span-7">
            <p class="eyebrow mb-4">Apply Online</p>
            <h1 class="text-balance">A structured application you can leave and return to</h1>
            <p class="lede mt-6 max-w-[34rem]">Create your account, enter your qualifications once, upload only the documents that apply to you, and approve your package before anything is submitted. Your work is saved from the first field.</p>
            <x-photo slug="apply" class="mt-6" ratio="16/9" sizes="(min-width: 1024px) 40vw, 100vw" />
            <div class="mt-8 flex flex-col sm:flex-row gap-3"><a href="{{ route('register') }}" class="btn btn-primary btn-lg">Create your account</a><a href="{{ route('apply.eligibility') }}" class="btn btn-secondary btn-lg">Check your eligibility first</a></div>
            <p class="mt-5 text-[0.875rem] text-ink-500">Already registered? <a href="{{ route('login') }}">Sign in</a>.</p>
        </div>
        <div class="lg:col-span-5 card-raised">
            <p class="eyebrow mb-3">What happens, in order</p>
            <ol class="space-y-3 text-[0.9375rem]">
                @foreach(['Choose a service level and create your application number', 'Complete nine short sections, saved automatically', 'Upload the documents on your personal checklist; each is reviewed', 'We review your file and propose where it should go', 'You approve the exact package; only then does submission begin', 'Track the university\'s response in your portal'] as $i => $s)<li class="flex gap-3"><span class="font-mono text-ink-500">0{{ $i+1 }}</span><span>{{ $s }}</span></li>@endforeach
            </ol>
        </div>
    </div>
</section>
<section class="container-site py-14">
    <h2 class="text-balance">Three levels of support</h2>
    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        @foreach($tiers as $t)<div class="card flex flex-col"><p class="eyebrow">{{ $t->code }}</p><h3 class="mt-1">{{ $t->name }}</h3><p class="mt-2 text-[0.9375rem] text-ink-700 flex-1">{{ $t->summary }}</p><p class="mt-3 font-semibold">{{ $t->hasPrices() ? $t->prices->whereNotNull('amount_minor')->map->formatted()->join(' + ') : 'Price to be confirmed' }}</p></div>@endforeach
    </div>
    <a href="{{ route('apply.services') }}" class="btn btn-tertiary mt-4">Full list of what is included and excluded</a>
</section>
<section class="bg-navy-50 border-y border-ink-200"><div class="container-site py-12 grid lg:grid-cols-12 gap-8">
    <div class="lg:col-span-5"><h2>What we promise, and what we never claim</h2></div>
    <div class="lg:col-span-7 text-[0.9375rem] text-ink-700 space-y-2">
        <p>We are an independent application-support service. We are not an agent of, or affiliated with, any university, UCAS, the British Council or the GMC, and we receive no commission from universities. We do not guarantee offers, interviews, visas or scholarships. Our fee is separate from university tuition, application fees, tests and visa costs, and we tell you before you pay exactly what is and is not included.</p>
        <p>Nothing is ever submitted to a university without your explicit, recorded approval of the exact package. <a href="{{ route('status') }}">Our status</a> · <a href="{{ route('legal.application-terms') }}">Application service terms</a> · <a href="{{ route('legal.refunds') }}">Refund policy</a></p>
    </div></div></section>
</x-layouts.public>
