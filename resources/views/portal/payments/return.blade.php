<x-layouts.portal :seo="$seo" :application="$application">
    <div class="max-w-xl card-raised text-center py-10">
        @if($payment && $payment->status === 'SUCCEEDED')
            <p class="eyebrow">Payment confirmed</p><h1 class="text-h2 mt-2">Thank you</h1>
            <p class="mt-3 text-ink-700">Stripe has confirmed {{ $payment->formattedAmount() }} for {{ $payment->tierPrice?->tier?->name ?? 'your service' }}. Your receipt is emailed by Stripe, and work on your service has started.</p>
            <a href="{{ route('portal.application.index', $application) }}" class="btn btn-primary mt-6">Continue your application</a>
        @elseif($payment && in_array($payment->status, ['FAILED', 'EXPIRED'], true))
            <p class="eyebrow">Payment not completed</p><h1 class="text-h2 mt-2">{{ $payment->status === 'FAILED' ? 'The payment did not go through' : 'The checkout expired' }}</h1>
            <p class="mt-3 text-ink-700">Nothing was charged and your application is unchanged. {{ $payment->status === 'FAILED' ? 'Your bank or card issuer declined the payment; some Nigerian cards need international payments switched on first.' : '' }} You can try again, or pay by bank transfer.</p>
            <a href="{{ route('portal.payments.index', $application) }}" class="btn btn-primary mt-6">Try again</a>
        @elseif($payment && $payment->status === 'MANUAL_REVIEW')
            <p class="eyebrow">Payment received</p><h1 class="text-h2 mt-2">Our team is checking it</h1>
            <p class="mt-3 text-ink-700">Stripe reported a payment that our team needs to check before it is applied to your application. We will message you within one working day; you do not need to pay again.</p>
            <a href="{{ route('portal.dashboard') }}" class="btn btn-secondary mt-6">Back to your dashboard</a>
        @elseif($payment)
            <p class="eyebrow">Confirming your payment</p><h1 class="text-h2 mt-2">One moment</h1>
            <p class="mt-3 text-ink-700">We are waiting for Stripe to confirm the payment to us. Your application is marked paid only when that confirmation arrives; this page checks again every few seconds, and we also email you.</p>
            <meta http-equiv="refresh" content="5">
            <a href="{{ route('portal.dashboard') }}" class="btn btn-secondary mt-6">Back to your dashboard</a>
        @else
            <p class="eyebrow">Payment</p><h1 class="text-h2 mt-2">We could not find that checkout</h1>
            <p class="mt-3 text-ink-700">Nothing has been recorded against your application from this link.</p>
            <a href="{{ route('portal.payments.index', $application) }}" class="btn btn-primary mt-6">Go to payment</a>
        @endif
    </div>
</x-layouts.portal>
