<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Apply Online', 'title' => 'Services and pricing', 'lede' => 'Each level lists exactly what you receive and what is not included. Our fee is for our time and support; it is separate from university tuition and application fees, admissions tests, English tests, visa and health surcharge, which you pay directly to those organisations.', 'seo' => $seo])
    <div class="mt-10 grid gap-6 lg:grid-cols-3">
        @foreach($tiers as $t)
            <section class="card-raised flex flex-col" aria-labelledby="t-{{ $t->code }}">
                <p class="eyebrow">{{ $t->code }}</p><h2 id="t-{{ $t->code }}" class="text-xl font-serif font-semibold mt-1">{{ $t->name }}</h2>
                <p class="mt-2 text-[0.9375rem] text-ink-700">{{ $t->summary }}</p>
                <p class="mt-4 text-2xl font-serif font-semibold">{{ $t->hasPrices() ? $t->prices->whereNotNull('amount_minor')->map->formatted()->join(' + ') : 'Price to be confirmed' }}</p>
                @if(!$t->hasPrices())<p class="text-[0.8125rem] text-ink-500">You can start your application now; nothing is charged until a price is published and you choose to pay.</p>@endif
                <p class="mt-4 font-semibold text-[0.9375rem]">Included</p><ul class="mt-1 space-y-1.5 text-[0.9375rem] text-ink-700 list-disc pl-5 flex-1">@foreach($t->deliverables as $d)<li>{{ $d }}</li>@endforeach</ul>
                <p class="mt-4 font-semibold text-[0.9375rem]">Not included</p><ul class="mt-1 space-y-1 text-[0.875rem] text-ink-500 list-disc pl-5">@foreach($t->exclusions ?? [] as $d)<li>{{ $d }}</li>@endforeach</ul>
                <p class="mt-4 text-[0.8125rem] text-ink-500">When work begins: {{ $t->payment_gate === 'AT_START' ? 'as soon as payment is confirmed.' : ($t->payment_gate === 'BEFORE_REVIEW' ? 'review begins once payment is confirmed.' : 'preparation after the first payment; the submission component is due only after you approve the proposed submission.') }}</p>
                <a href="{{ route('register') }}" class="btn btn-primary mt-5">Start with {{ $t->code }}</a>
            </section>
        @endforeach
    </div>
    <section class="mt-12 max-w-3xl prose-site">
        <h2>Refunds, plainly</h2>
        <p>Full refund if you ask within 14 days and no work has started. After work starts, a pro-rata refund by deliverables completed. The submission component is refundable until you approve the package. If our assessment finds that no published UK medicine route is currently open to you and you do not wish to proceed to foundation or alternative guidance, the assessment fee is refunded in full. Details in the <a href="{{ route('legal.refunds') }}">refund policy</a>.</p>
        <h2>Payment</h2>
        <p>Card payments are taken securely by Stripe in pounds sterling; we never see your card details. If your card is declined for international payments, bank transfer is available. Receipts are issued automatically.</p>
    </section>
</article>
</x-layouts.public>
