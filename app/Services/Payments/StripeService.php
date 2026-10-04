<?php

namespace App\Services\Payments;

use App\Models\Application;
use App\Models\Payment;
use App\Models\TierPrice;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Notifications\StaffNotification;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeClient;

class StripeService
{
    /** Events the webhook endpoint must send (ops/STAGING-SETTINGS.md); everything else is ignored by applyEvent(). */
    public const WEBHOOK_EVENTS = ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.expired',
        'payment_intent.payment_failed', 'charge.refunded', 'charge.dispute.created'];

    /**
     * Card payments need a key of the right mode: production takes only a live key (no test charge can pose as a real
     * payment), every other environment only a test key.
     */
    public function enabled(): bool
    {
        $secret = (string) config('services.stripe.secret');
        if ($secret === '') {
            return false;
        }
        $live = str_starts_with($secret, 'sk_live_') || str_starts_with($secret, 'rk_live_');

        return $this->liveAllowed() ? $live : ! $live;
    }

    /**
     * Whether any way to pay is open: card (a usable Stripe key) or bank transfer (owner-enabled). Until one is, a
     * student can choose a service and see its fee and terms, but no payment action is offered or accepted.
     */
    public function paymentsOpen(): bool
    {
        return $this->enabled() || (bool) config('site.bank_transfer');
    }

    public function liveAllowed(): bool
    {
        return app()->isProduction();
    }

    /**
     * Resolved from the container so tests can stand in for Stripe without network access.
     *
     * @return StripeClient
     */
    public function client(): object
    {
        if (app()->bound(StripeClient::class)) {
            return app(StripeClient::class);
        }
        $base = app()->environment('local', 'testing') ? config('services.stripe.api_base') : null; // never redirectable on a server

        return new StripeClient(array_filter(['api_key' => (string) config('services.stripe.secret'), 'api_base' => $base]));
    }

    public function createCheckout(Application $a, TierPrice $price, string $termsVersion): Payment
    {
        // The server picks the Stripe Price that matches our own price record (never anything the browser sent);
        // resolved first, so a Stripe error leaves no half-made payment behind.
        $stripePrice = app(StripeCatalog::class)->ensure($price);

        // one open session per price component: expire older INITIATED ones here and at Stripe, so a checkout left open
        // in another tab can no longer be paid (a duplicate that still arrives is held for refund in applyEvent)
        foreach ($a->payments()->where('tier_price_id', $price->id)->where('status', 'INITIATED')->get() as $open) {
            if ($open->stripe_checkout_session_id) {
                try {
                    $this->client()->checkout->sessions->expire($open->stripe_checkout_session_id);
                } catch (\Throwable) {
                    // already expired or completed at Stripe: the webhook decides
                }
            }
            $open->update(['status' => 'EXPIRED']);
        }

        $payment = $a->payments()->create([
            'tier_price_id' => $price->id, 'status' => 'INITIATED', 'amount_minor' => $price->amount_minor, 'currency' => $price->currency,
            'method' => 'STRIPE', 'terms_version_accepted' => $termsVersion, 'stripe_price_id' => $stripePrice,
        ]);
        $label = 'Study Medicine UK Nigeria service fee: '.$price->tier->name.' (application '.$a->application_number.'). Separate from university tuition, application and test fees.';
        $params = [
            'mode' => 'payment',
            'client_reference_id' => $a->application_number,
            'customer_email' => $a->user->email,
            'metadata' => ['application_id' => $a->id, 'application_number' => $a->application_number, 'payment_id' => $payment->id, 'tier_price_id' => $price->id, 'stripe_price_id' => $stripePrice],
            'payment_intent_data' => ['description' => $label, 'metadata' => ['application_number' => $a->application_number, 'payment_id' => $payment->id]],
            'success_url' => route('portal.payments.return', $a).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('portal.payments.index', $a).'?cancelled=1',
            'line_items' => [['quantity' => 1, 'price' => $stripePrice]],
        ];
        if (config('services.stripe.adaptive_pricing')) {
            $params['adaptive_pricing'] = ['enabled' => true];
        }

        $session = $this->client()->checkout->sessions->create($params);
        $payment->forceFill(['stripe_checkout_session_id' => $session->id, 'stripe_payment_intent_id' => is_string($session->payment_intent) ? $session->payment_intent : null])->save();
        $a->record('payment.initiated', ['payment_id' => $payment->id, 'amount' => $payment->formattedAmount()], $a->user_id);
        $payment->checkout_url = $session->url;

        return $payment;
    }

    /** Idempotent webhook application. Returns true when the event changed state. */
    public function applyEvent(Event $event): bool
    {
        $obj = $event->data->object;
        $payment = null;
        if (isset($obj->metadata->payment_id)) {
            $payment = Payment::find((int) $obj->metadata->payment_id);
        }
        if (! $payment && $obj instanceof Session) {
            $payment = Payment::where('stripe_checkout_session_id', $obj->id)->first();
        }
        if (! $payment && isset($obj->payment_intent)) {
            $payment = Payment::where('stripe_payment_intent_id', is_string($obj->payment_intent) ? $obj->payment_intent : $obj->payment_intent->id ?? null)->first();
        }
        if (! $payment && isset($obj->id) && str_starts_with((string) $obj->id, 'pi_')) {
            $payment = Payment::where('stripe_payment_intent_id', $obj->id)->first();
        }
        if (! $payment) {
            return false;
        }

        if ((bool) ($event->livemode ?? false) !== $this->liveAllowed()) {
            return false; // a live-mode event changes nothing outside production, and a test-mode event nothing in it
        }
        if ($obj instanceof Session && $payment->stripe_checkout_session_id && $obj->id !== $payment->stripe_checkout_session_id) {
            return false; // the session must be the one created for this payment
        }
        $a = $payment->application;
        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                if (($obj->payment_status ?? 'paid') !== 'paid') {
                    return false;
                }
                if ($payment->status === 'SUCCEEDED') {
                    return false;
                }
                if ($a->payments()->where('id', '!=', $payment->id)->where('tier_price_id', $payment->tier_price_id)->where('status', 'SUCCEEDED')->exists()) {
                    // The same fee was already paid through another checkout: never count it twice; staff refund it.
                    $payment->update(['status' => 'MANUAL_REVIEW', 'note' => 'Duplicate payment: this fee was already paid by another checkout. Refund it from the Stripe Dashboard.']);
                    $a->record('payment.duplicate', ['payment_id' => $payment->id]);
                    User::where('role', 'admin')->get()->each->notify(new StaffNotification('Duplicate payment to refund — '.$a->application_number, ['Stripe confirmed a second payment for a service fee that was already paid. It has not been counted; refund it from the Stripe Dashboard.'], route('admin.applications.show', $a)));

                    return true;
                }
                if (! $this->amountMatches($obj, $payment) || $payment->tierPrice?->service_tier_id !== $a->service_tier_id) {
                    // Paid, but not the amount and currency this application owes: never treat it as paid automatically.
                    $payment->update(['status' => 'MANUAL_REVIEW', 'note' => 'Stripe reported '.strtoupper((string) ($obj->currency ?? '?')).' '.(int) ($obj->amount_total ?? 0).' (minor units); expected '.$payment->currency.' '.$payment->amount_minor.'.']);
                    $a->record('payment.amount_mismatch', ['payment_id' => $payment->id]);
                    User::where('role', 'admin')->get()->each->notify(new StaffNotification('Payment needs review — '.$a->application_number, ['Stripe confirmed a payment whose amount, currency or service differs from what this application owes. It has not been marked paid.'], route('admin.applications.show', $a)));

                    return true;
                }
                $payment->forceFill(['status' => 'SUCCEEDED', 'succeeded_at' => now(), 'stripe_payment_intent_id' => is_string($obj->payment_intent ?? null) ? $obj->payment_intent : $payment->stripe_payment_intent_id])->save();
                $a->record('payment.succeeded', ['payment_id' => $payment->id, 'amount' => $payment->formattedAmount()]);
                $a->user->notify(new ApplicationNotification($a, 'payment.succeeded', ['amount' => $payment->formattedAmount()]));

                return true;
            case 'checkout.session.expired':
                if ($payment->status === 'INITIATED') {
                    $payment->update(['status' => 'EXPIRED']);
                    $a->record('payment.expired', ['payment_id' => $payment->id]);

                    return true;
                }

                return false;
            case 'payment_intent.payment_failed':
                if ($payment->status !== 'INITIATED') {
                    return false; // never downgrade a succeeded, expired or refunded record because events arrived out of order
                }
                $payment->update(['status' => 'FAILED', 'note' => $obj->last_payment_error->message ?? null]);
                $a->record('payment.failed', ['payment_id' => $payment->id]);

                return true;
            case 'charge.refunded':
                $refunded = (int) ($obj->amount_refunded ?? 0);
                if ($refunded <= $payment->refunded_minor) {
                    return false;
                }
                $payment->update(['refunded_minor' => $refunded, 'status' => $refunded >= $payment->amount_minor ? 'REFUNDED_FULL' : 'REFUNDED_PARTIAL']);
                $a->record('payment.refunded', ['payment_id' => $payment->id, 'refunded' => $payment->formattedRefund()]);

                return true;
            case 'charge.dispute.created':
                $payment->update(['status' => 'DISPUTED']);
                $a->record('payment.disputed', ['payment_id' => $payment->id]);

                return true;
        }

        return false;
    }

    /**
     * Whether Stripe charged exactly the fee recorded for this payment. With adaptive pricing the customer may pay in
     * their own currency; Stripe then reports the original (source) amount under currency_conversion.
     */
    private function amountMatches(object $session, Payment $payment): bool
    {
        $conversion = $session->currency_conversion ?? null;
        $amount = $conversion->amount_total ?? $session->amount_total ?? null;
        $currency = $conversion->source_currency ?? $session->currency ?? null;

        return $amount !== null && (int) $amount === (int) $payment->amount_minor && strtoupper((string) $currency) === strtoupper($payment->currency);
    }
}
