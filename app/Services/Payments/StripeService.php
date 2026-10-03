<?php

namespace App\Services\Payments;

use App\Models\Application;
use App\Models\Payment;
use App\Models\TierPrice;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class StripeService
{
    public function enabled(): bool
    {
        return (bool) config('services.stripe.secret');
    }

    public function client(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    public function createCheckout(Application $a, TierPrice $price, string $termsVersion): Payment
    {
        // one open session per price component; expire older INITIATED ones
        $a->payments()->where('tier_price_id', $price->id)->where('status', 'INITIATED')->update(['status' => 'EXPIRED']);

        $payment = $a->payments()->create([
            'tier_price_id' => $price->id, 'status' => 'INITIATED', 'amount_minor' => $price->amount_minor, 'currency' => $price->currency,
            'method' => 'STRIPE', 'terms_version_accepted' => $termsVersion,
        ]);

        $params = [
            'mode' => 'payment',
            'client_reference_id' => $a->application_number,
            'customer_email' => $a->user->email,
            'metadata' => ['application_id' => $a->id, 'application_number' => $a->application_number, 'payment_id' => $payment->id, 'tier_price_id' => $price->id],
            'payment_intent_data' => ['metadata' => ['application_number' => $a->application_number, 'payment_id' => $payment->id]],
            'success_url' => route('portal.payments.return', $a).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('portal.payments.index', $a),
            'line_items' => [$price->stripe_price_id
                ? ['price' => $price->stripe_price_id, 'quantity' => 1]
                : ['quantity' => 1, 'price_data' => ['currency' => strtolower($price->currency), 'unit_amount' => $price->amount_minor, 'product_data' => ['name' => $price->tier->name.($price->component !== 'full' ? ' — '.ucfirst($price->component) : ''), 'description' => 'Application '.$a->application_number]]]],
        ];
        if (config('services.stripe.adaptive_pricing')) $params['adaptive_pricing'] = ['enabled' => true];

        $session = $this->client()->checkout->sessions->create($params);
        $payment->forceFill(['stripe_checkout_session_id' => $session->id, 'stripe_payment_intent_id' => is_string($session->payment_intent) ? $session->payment_intent : null])->save();
        $a->record('payment.initiated', ['payment_id' => $payment->id, 'amount' => $payment->formattedAmount()], $a->user_id);
        $payment->checkout_url = $session->url;

        return $payment;
    }

    /** Idempotent webhook application. Returns true when the event changed state. */
    public function applyEvent(\Stripe\Event $event): bool
    {
        $obj = $event->data->object;
        $payment = null;
        if (isset($obj->metadata->payment_id)) $payment = Payment::find((int) $obj->metadata->payment_id);
        if (! $payment && $obj instanceof Session) $payment = Payment::where('stripe_checkout_session_id', $obj->id)->first();
        if (! $payment && isset($obj->payment_intent)) $payment = Payment::where('stripe_payment_intent_id', is_string($obj->payment_intent) ? $obj->payment_intent : $obj->payment_intent->id ?? null)->first();
        if (! $payment && isset($obj->id) && str_starts_with((string) $obj->id, 'pi_')) $payment = Payment::where('stripe_payment_intent_id', $obj->id)->first();
        if (! $payment) return false;

        $a = $payment->application;
        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                if (($obj->payment_status ?? 'paid') !== 'paid') return false;
                if ($payment->status === 'SUCCEEDED') return false;
                $payment->forceFill(['status' => 'SUCCEEDED', 'succeeded_at' => now(), 'stripe_payment_intent_id' => is_string($obj->payment_intent ?? null) ? $obj->payment_intent : $payment->stripe_payment_intent_id])->save();
                $a->record('payment.succeeded', ['payment_id' => $payment->id, 'amount' => $payment->formattedAmount()]);
                $a->user->notify(new \App\Notifications\ApplicationNotification($a, 'payment.succeeded', ['amount' => $payment->formattedAmount()]));
                return true;
            case 'checkout.session.expired':
                if ($payment->status === 'INITIATED') { $payment->update(['status' => 'EXPIRED']); return true; }
                return false;
            case 'payment_intent.payment_failed':
                $payment->update(['status' => 'FAILED', 'note' => $obj->last_payment_error->message ?? null]);
                $a->record('payment.failed', ['payment_id' => $payment->id]);
                return true;
            case 'charge.refunded':
                $refunded = (int) ($obj->amount_refunded ?? 0);
                $payment->update(['refunded_minor' => $refunded, 'status' => $refunded >= $payment->amount_minor ? 'REFUNDED_FULL' : 'REFUNDED_PARTIAL']);
                return true;
            case 'charge.dispute.created':
                $payment->update(['status' => 'DISPUTED']);
                $a->record('payment.disputed', ['payment_id' => $payment->id]);
                return true;
        }

        return false;
    }
}
