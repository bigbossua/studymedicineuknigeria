<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Apply Online', 'title' => 'Our services', 'lede' => 'Independent support for Nigerian students applying to study Medicine in the UK, at three levels of depth. Each lists exactly what you receive and what is not included.', 'seo' => $seo])
    {{-- Service fees are deliberately not part of any public page: they are shown in the student portal once our team has reviewed the profile. --}}
    <div class="border-l-4 border-navy-700 bg-navy-50 rounded-md px-5 py-4 mt-8 max-w-4xl" role="note" aria-label="Service options and pricing">
        <p class="font-semibold text-ink-900">Service options and pricing are provided after your profile has been reviewed.</p>
        <p class="mt-2 text-[0.9375rem] text-ink-700">Create your account and tell us about your qualifications. Once our team has reviewed your profile, your portal shows each service open to you with its exact fee, and you choose the one that suits you. Nothing is charged until you have seen the fee, what it includes and the terms, and decided to pay.</p>
    </div>
    <x-fee-disclaimer class="mt-6 max-w-4xl" />
    <div class="mt-10 grid gap-6 lg:grid-cols-3 lg:items-stretch">
        @foreach($tiers as $t)
            @php($featured = (bool) $t->badge)
            <section class="card-raised flex flex-col relative {{ $featured ? 'border-2 border-navy-700 lg:-mt-3 lg:pb-8 shadow-lg' : '' }}" aria-labelledby="t-{{ $t->code }}">
                @if($featured)<p class="absolute -top-3 left-6 rounded-full bg-navy-700 px-3 py-1 text-[0.75rem] font-semibold uppercase tracking-wide text-white">{{ $t->badge }}</p>@endif
                <p class="eyebrow {{ $featured ? 'mt-2' : '' }}">{{ $t->tagline ?? $t->code }}</p>
                <h2 id="t-{{ $t->code }}" class="text-xl font-serif font-semibold mt-1">{{ $t->name }}</h2>
                <p class="mt-3 text-[0.9375rem] text-ink-700">{{ $t->summary }}</p>
                <p class="mt-5 font-semibold text-[0.9375rem]">Included</p>
                <ul class="mt-1 space-y-1.5 text-[0.9375rem] text-ink-700 list-disc pl-5 flex-1">@foreach($t->deliverables as $d)<li>{{ $d }}</li>@endforeach</ul>
                <p class="mt-5 font-semibold text-[0.9375rem]">Not included</p>
                <ul class="mt-1 space-y-1 text-[0.875rem] text-ink-500 list-disc pl-5">@foreach($t->exclusions ?? [] as $d)<li>{{ $d }}</li>@endforeach</ul>
                <p class="mt-5 text-[0.8125rem] text-ink-500">One service fee, shown in your portal after profile review and paid before work begins.</p>
            </section>
        @endforeach
    </div>
    <div class="mt-8 flex flex-col sm:flex-row gap-3">
        <a href="{{ route('apply.index') }}" class="btn btn-primary btn-lg" data-cta="services-apply">Apply Online</a>
        <a href="{{ route('apply.eligibility') }}" class="btn btn-secondary btn-lg">Check your eligibility first</a>
    </div>
    <section class="mt-12 max-w-3xl prose-site">
        <h2>How it works</h2>
        <ol><li>Apply Online: create your account and your application (no payment is taken).</li><li>Tell us about your qualifications and plans; your answers save as you go.</li><li>Our team reviews your profile.</li><li>Your portal then shows the service options open to you, each with its exact fee, inclusions and exclusions.</li><li>You choose one service, read the terms and confirm; only then do you pay, through Stripe's secure checkout.</li></ol>
        <h2>Refunds, plainly</h2>
        <p>Full refund if you ask within 14 days and no work has started. After work starts, a pro-rata refund by deliverables completed. Nothing is submitted to any university until you approve the package, and no refund is due for submission support once a submission has been made. If our assessment finds that no published UK medicine route is currently open to you and you do not wish to proceed to foundation or alternative guidance, the assessment fee is refunded in full. Details in the <a href="{{ route('legal.refunds') }}">refund policy</a>.</p>
        <h2>Payment</h2>
        <p>The fee is paid in pounds sterling (GBP) through Stripe's secure checkout; we never see your card details.@if(config('site.bank_transfer')) If your card is declined for international payments, bank transfer is available.@endif The payment is a Study Medicine UK Nigeria service fee, not a payment to any university. Receipts are issued automatically.</p>
    </section>
    <x-cta-band class="mt-12" title="Ready to start?" :href="route('apply.index')" label="Apply Online" :secondary-href="route('apply.eligibility')" secondary-label="Check your eligibility">Create your account and application; our team reviews your profile and your portal then shows the service options and their fees.</x-cta-band>
</article>
</x-layouts.public>
