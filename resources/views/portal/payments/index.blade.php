<x-layouts.portal :seo="$seo" :application="$application">
    <div class="max-w-3xl">
        <h1 class="text-h2">Payments</h1>
        <p class="text-ink-700 mt-2">Our service fee is separate from university tuition, UCAS or university application fees, UCAT or GAMSAT fees, English tests, visa fees and the Immigration Health Surcharge. Those are paid by you directly to the relevant organisation.</p>
        <section class="card mt-6">
            <p class="eyebrow mb-2">Your service</p>
            <p class="font-semibold text-lg">{{ $application->tier?->name }}</p>
            <ul class="mt-2 text-[0.9375rem] text-ink-700 list-disc pl-5">@foreach($application->tier?->deliverables ?? [] as $x)<li>{{ $x }}</li>@endforeach</ul>
            <p class="mt-3 text-[0.875rem] text-ink-500"><span class="font-semibold">Not included:</span> {{ implode('; ', $application->tier?->exclusions ?? []) }}.</p>
        </section>
        @if($prices->isEmpty())
            <x-alert type="info" class="mt-6" title="Prices are being confirmed">We have not yet published the fee for this service. You can complete your application and upload documents now; we will email you before any payment is requested, and nothing is charged without your action.</x-alert>
        @else
            @foreach($prices as $price)
                @php $paid = $application->payments->first(fn($p)=>$p->tier_price_id===$price->id && $p->status==='SUCCEEDED'); $pending = $application->payments->first(fn($p)=>$p->tier_price_id===$price->id && in_array($p->status,['INITIATED','MANUAL_REVIEW'])); @endphp
                <section class="card mt-5">
                    <div class="flex items-start justify-between gap-3"><div><p class="eyebrow">{{ $price->component === 'full' ? 'Service fee' : ucfirst($price->component).' fee' }}</p><p class="text-2xl font-serif font-semibold">{{ $price->formatted() }}</p></div>
                        @if($paid)<span class="chip chip-verified">Paid {{ $paid->succeeded_at?->format('j M Y') }}</span>@elseif($pending)<span class="chip chip-info">{{ $pending->statusLabel() }}</span>@endif</div>
                    @unless($paid || $pending)
                        <div class="mt-4 text-[0.9375rem] text-ink-700 space-y-2">
                            <p><span class="font-semibold">When work begins:</span> {{ $application->tier->payment_gate === 'AT_START' ? 'as soon as payment is confirmed.' : ($application->tier->payment_gate === 'BEFORE_REVIEW' ? 'review starts once payment is confirmed; you can complete the form and documents first.' : 'this component is due after you approve the proposed submission.') }}</p>
                            <p><span class="font-semibold">Refunds:</span> full refund within 14 days if no work has started; otherwise pro-rata by deliverables completed. See the <a href="{{ route('legal.refunds') }}" target="_blank">refund policy</a>.</p>
                        </div>
                        <form method="post" action="{{ route('portal.payments.checkout', $application) }}" class="mt-4 space-y-3">@csrf<input type="hidden" name="tier_price_id" value="{{ $price->id }}">
                            <label class="flex items-start gap-3 text-[0.9375rem]"><input type="checkbox" name="accept_terms" value="1" required class="mt-1 w-4 h-4"> I understand what is included and excluded, that admission is decided by universities, and I accept the <a href="{{ route('legal.application-terms') }}" target="_blank">application service terms</a> ({{ $application->tier->terms_version }}).</label>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button class="btn btn-primary" @disabled(!$stripeEnabled)>Pay securely by card (Stripe)</button>
                                <details class="sm:ml-auto"><summary class="btn btn-tertiary cursor-pointer">Pay by bank transfer instead</summary>
                                    <div class="card mt-3 text-[0.9375rem] space-y-2"><p>If your card is declined for international payments, you can pay by bank transfer. We will email you our account details and confirm within one working day of receipt.</p>
                                        <input name="reference" class="input" placeholder="Your transfer reference (optional)" form="manual-{{ $price->id }}">
                                        <button class="btn btn-secondary" form="manual-{{ $price->id }}">Request bank transfer details</button></div></details>
                            </div>
                            @unless($stripeEnabled)<p class="hint">Card payments are being enabled. Bank transfer is available now.</p>@endunless
                        </form>
                        <form id="manual-{{ $price->id }}" method="post" action="{{ route('portal.payments.manual', $application) }}">@csrf<input type="hidden" name="tier_price_id" value="{{ $price->id }}"><input type="hidden" name="accept_terms" value="1"></form>
                    @endunless
                </section>
            @endforeach
        @endif
        @if($application->payments->isNotEmpty())
            <section class="mt-8"><h2 class="eyebrow">Payment history</h2>
                <table class="mt-3 table-stack"><thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead><tbody>
                @foreach($application->payments as $p)<tr><td data-label="Date">{{ $p->created_at->format('j M Y') }}</td><td data-label="Amount">{{ $p->formattedAmount() }}</td><td data-label="Method">{{ $p->method === 'STRIPE' ? 'Card (Stripe)' : 'Bank transfer' }}</td><td data-label="Status">{{ $p->statusLabel() }}</td></tr>@endforeach
                </tbody></table></section>
        @endif
    </div>
</x-layouts.portal>
