<x-layouts.portal :seo="$seo" :application="$application">
    <div class="max-w-xl card-raised text-center py-10">
        @if($payment && $payment->status === 'SUCCEEDED')
            <p class="eyebrow">Payment received</p><h1 class="text-h2 mt-2">Thank you</h1><p class="mt-3 text-ink-700">We have received {{ $payment->formattedAmount() }}. A receipt from Stripe will arrive by email.</p>
        @else
            <p class="eyebrow">Confirming your payment</p><h1 class="text-h2 mt-2">One moment</h1><p class="mt-3 text-ink-700">Stripe is confirming the payment. This page will update; you can also return to your dashboard and we will email you when it is confirmed.</p>
            <meta http-equiv="refresh" content="5">
        @endif
        <a href="{{ route('portal.dashboard') }}" class="btn btn-primary mt-6">Back to your dashboard</a>
    </div>
</x-layouts.portal>
