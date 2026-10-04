<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Apply Online', 'title' => 'Services and pricing', 'lede' => 'Specialist, independent support for Nigerian students applying to study Medicine in the UK, at three levels of depth. Each lists exactly what you receive and what is not included, with one fee paid before work begins.', 'seo' => $seo])
    <x-fee-disclaimer class="mt-8 max-w-4xl" />
    <div class="mt-10 grid gap-6 lg:grid-cols-3 lg:items-stretch">
        @foreach($tiers as $t)
            @php($price = $t->priceFor('full'))
            @php($featured = (bool) $t->badge)
            <section class="card-raised flex flex-col relative {{ $featured ? 'border-2 border-navy-700 lg:-mt-3 lg:pb-8 shadow-lg' : '' }}" aria-labelledby="t-{{ $t->code }}">
                @if($featured)<p class="absolute -top-3 left-6 rounded-full bg-navy-700 px-3 py-1 text-[0.75rem] font-semibold uppercase tracking-wide text-white">{{ $t->badge }}</p>@endif
                <p class="eyebrow {{ $featured ? 'mt-2' : '' }}">{{ $t->tagline ?? $t->code }}</p>
                <h2 id="t-{{ $t->code }}" class="text-xl font-serif font-semibold mt-1">{{ $t->name }}</h2>
                <p class="mt-4"><span class="text-4xl font-serif font-semibold text-ink-900" data-price="{{ $t->code }}">{{ $price?->formatted() ?? 'Price not yet published' }}</span>@if($price?->amount_minor)<span class="ml-2 text-[0.875rem] text-ink-500">one-off service fee</span>@endif</p>
                <p class="mt-3 text-[0.9375rem] text-ink-700">{{ $t->summary }}</p>
                <p class="mt-5 font-semibold text-[0.9375rem]">Included</p>
                <ul class="mt-1 space-y-1.5 text-[0.9375rem] text-ink-700 list-disc pl-5 flex-1">@foreach($t->deliverables as $d)<li>{{ $d }}</li>@endforeach</ul>
                <p class="mt-5 font-semibold text-[0.9375rem]">Not included</p>
                <ul class="mt-1 space-y-1 text-[0.875rem] text-ink-500 list-disc pl-5">@foreach($t->exclusions ?? [] as $d)<li>{{ $d }}</li>@endforeach</ul>
                <p class="mt-5 text-[0.8125rem] text-ink-500">Work begins as soon as your payment is confirmed.</p>
                <a href="{{ route('apply.choose', strtolower($t->code)) }}" class="btn {{ $featured ? 'btn-primary' : 'btn-secondary' }} mt-5" data-choose="{{ $t->code }}" aria-label="Choose {{ $t->name }}, {{ $price?->formatted() }}">Choose this service</a>
            </section>
        @endforeach
    </div>
    <p class="mt-6 text-[0.875rem] text-ink-500 max-w-3xl">Not sure which level you need? The online <a href="{{ route('apply.eligibility') }}">eligibility check</a> (seven questions, no account needed) shows which published routes appear open to you, and you can change your service in your account before you pay.</p>
    <section class="mt-12 max-w-3xl prose-site">
        <h2>Refunds, plainly</h2>
        <p>Full refund if you ask within 14 days and no work has started. After work starts, a pro-rata refund by deliverables completed. Nothing is submitted to any university until you approve the package, and no refund is due for submission support once a submission has been made. If our assessment finds that no published UK medicine route is currently open to you and you do not wish to proceed to foundation or alternative guidance, the assessment fee is refunded in full. Details in the <a href="{{ route('legal.refunds') }}">refund policy</a>.</p>
        <h2>Payment</h2>
        <p>Card payments are taken by Stripe's secure checkout in pounds sterling (GBP); we never see your card details. The payment is a Study Medicine UK Nigeria service fee, not a payment to any university. If your card is declined for international payments, bank transfer is available. Receipts are issued automatically.</p>
    </section>
    <x-cta-band class="mt-12" title="Not sure which service fits?" :href="route('apply.eligibility')" label="Check your eligibility" :secondary-href="route('apply.index')" secondary-label="Apply Online">Start with the eligibility check: seven questions, no account needed. It shows which published routes your qualifications appear to open before you choose a service.</x-cta-band>
</article>
</x-layouts.public>
