<x-layouts.portal :seo="$seo" :application="$application">
    @php($price = $prices->first())
    @php($paid = $price ? $application->payments->first(fn ($p) => $p->tier_price_id === $price->id && $p->status === 'SUCCEEDED') : null)
    @php($pending = $price ? $application->payments->first(fn ($p) => $p->tier_price_id === $price->id && $p->status === 'MANUAL_REVIEW') : null)
    <div class="max-w-3xl">
        <p class="eyebrow">Application {{ $application->application_number }}</p>
        <h1 class="text-h2 mt-1">{{ $paid ? 'Your service is paid' : 'Confirm your service' }}</h1>

        @if($cancelled && ! $paid)
            <x-alert type="info" class="mt-5" title="Payment cancelled">You left Stripe's checkout before paying, so nothing was charged. Your application is unchanged; you can pay below whenever you are ready.</x-alert>
        @endif

        <section class="card-raised mt-6" aria-labelledby="svc">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="eyebrow">{{ $application->tier?->tagline ?? 'Your service' }}</p>
                    <h2 id="svc" class="text-xl font-serif font-semibold mt-1">{{ $application->tier?->name }}</h2>
                    <p class="text-[0.875rem] text-ink-500 mt-1">Study Medicine UK Nigeria service fee, not a payment to any university</p>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-serif font-semibold text-ink-900" data-checkout-price>{{ $price?->formatted() ?? 'Price not yet published' }}</p>
                    @if($price)<p class="text-[0.8125rem] text-ink-500">GBP, one-off</p>@endif
                    @if($paid)<span class="chip chip-verified mt-2">Paid {{ $paid->succeeded_at?->format('j M Y') }}</span>@elseif($pending)<span class="chip chip-info mt-2">{{ $pending->statusLabel() }}</span>@endif
                </div>
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><p class="font-semibold text-[0.9375rem]">What is included</p>
                    <ul class="mt-1 space-y-1.5 text-[0.9375rem] text-ink-700 list-disc pl-5">@foreach($application->tier?->deliverables ?? [] as $x)<li>{{ $x }}</li>@endforeach</ul></div>
                <div><p class="font-semibold text-[0.9375rem]">What is not included</p>
                    <ul class="mt-1 space-y-1 text-[0.875rem] text-ink-500 list-disc pl-5">@foreach($application->tier?->exclusions ?? [] as $x)<li>{{ $x }}</li>@endforeach</ul></div>
            </div>
        </section>

        <x-fee-disclaimer class="mt-6" />

        @if(! $price)
            <x-alert type="info" class="mt-6" title="Fee not yet published">You can complete your application and upload documents now; nothing is charged until a fee is published and you choose to pay.</x-alert>
        @elseif($paid)
            <section class="card mt-6"><p class="text-[0.9375rem] text-ink-700">We received {{ $paid->formattedAmount() }} on {{ $paid->succeeded_at?->format('j F Y') }}. Stripe emails the receipt to {{ auth()->user()->email }}. Work on your service has started.</p>
                <a href="{{ route('portal.application.index', $application) }}" class="btn btn-primary mt-4">Continue your application</a></section>
        @elseif($pending)
            <section class="card mt-6"><p class="text-[0.9375rem] text-ink-700">Your bank transfer is waiting for confirmation by our team; we confirm within one working day of receipt. You can continue your application in the meantime.</p>
                <a href="{{ route('portal.application.index', $application) }}" class="btn btn-secondary mt-4">Continue your application</a></section>
        @else
            <section class="card mt-6" aria-labelledby="pay">
                <h2 id="pay" class="text-lg font-semibold">Pay {{ $price->formatted() }} securely</h2>
                <div class="mt-3 text-[0.9375rem] text-ink-700 space-y-2">
                    <p><span class="font-semibold">When work begins:</span> as soon as Stripe confirms your payment to us. Returning from the checkout page alone does not count as payment.</p>
                    <p><span class="font-semibold">Refunds:</span> full refund within 14 days if no work has started; otherwise pro-rata by deliverables completed. See the <a href="{{ route('legal.refunds') }}" target="_blank" rel="noopener">refund policy</a>.</p>
                </div>
                <form method="post" action="{{ route('portal.payments.checkout', $application) }}" class="mt-4 space-y-4">@csrf<input type="hidden" name="tier_price_id" value="{{ $price->id }}">
                    <label class="flex items-start gap-3 text-[0.9375rem]"><input type="checkbox" name="accept_terms" value="1" required class="mt-1 w-4 h-4"> <span>I understand what is included and not included, that this is a service fee separate from university and third-party costs, that admission is decided by universities, and I accept the <a href="{{ route('legal.application-terms') }}" target="_blank" rel="noopener">application service terms</a> ({{ $application->tier->terms_version }}).</span></label>
                    @error('accept_terms')<p class="error-text">{{ $message }}</p>@enderror
                    <button class="btn btn-primary btn-lg w-full sm:w-auto" @disabled(! $stripeEnabled)>Continue to secure payment</button>
                    <p class="hint">You will pay on Stripe's secure checkout page; we never see your card details.@unless($stripeEnabled) Card payment is not switched on yet; bank transfer is available below.@endunless</p>
                </form>
                <details class="mt-5"><summary class="cursor-pointer text-[0.9375rem] font-medium">Pay by bank transfer instead</summary>
                    <form method="post" action="{{ route('portal.payments.manual', $application) }}" class="card mt-3 text-[0.9375rem] space-y-3">@csrf<input type="hidden" name="tier_price_id" value="{{ $price->id }}"><input type="hidden" name="accept_terms" value="1">
                        <p>If your card is declined for international payments, you can pay by bank transfer. We will email you our account details and confirm within one working day of receipt.</p>
                        <label class="label" for="transfer-ref">Your transfer reference (optional)</label><input id="transfer-ref" name="reference" class="input" maxlength="120">
                        <button class="btn btn-secondary">Request bank transfer details</button></form></details>
            </section>
        @endif

        @if($canChange && ! $paid)
            <details class="card mt-6"><summary class="cursor-pointer font-medium">Choose a different service</summary>
                <form method="post" action="{{ route('portal.payments.service', $application) }}" class="mt-4 space-y-3">@csrf
                    @foreach($tiers as $t)
                        <label class="flex items-start gap-3 text-[0.9375rem]"><input type="radio" name="service_tier_id" value="{{ $t->id }}" class="mt-1 w-4 h-4" @checked($t->id === $application->service_tier_id)>
                            <span><span class="font-semibold">{{ $t->name }}</span> · {{ $t->priceFor('full')?->formatted() ?? 'not yet priced' }}@if($t->badge) <span class="chip chip-info ml-1">{{ $t->badge }}</span>@endif</span></label>
                    @endforeach
                    <button class="btn btn-secondary">Change service</button>
                </form></details>
        @endif

        @if($application->payments->isNotEmpty())
            <section class="mt-8"><h2 class="eyebrow">Payment history</h2>
                <table class="mt-3 table-stack"><thead><tr><th>Date</th><th>Service</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead><tbody>
                @foreach($application->payments->sortByDesc('id') as $p)<tr><td data-label="Date">{{ $p->created_at->format('j M Y H:i') }}</td><td data-label="Service">{{ $p->tierPrice?->tier?->name ?? '—' }}</td><td data-label="Amount">{{ $p->formattedAmount() }}</td><td data-label="Method">{{ $p->method === 'STRIPE' ? 'Card (Stripe)' : 'Bank transfer' }}</td><td data-label="Status">{{ $p->statusLabel() }}</td></tr>@endforeach
                </tbody></table></section>
        @endif
    </div>
</x-layouts.portal>
