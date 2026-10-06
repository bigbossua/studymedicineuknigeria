<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\StripeEvent;
use App\Services\Payments\StripeService;
use Illuminate\Http\Request;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeService $stripe)
    {
        $secret = config('services.stripe.webhook_secret');
        abort_unless($secret, 503);
        try {
            $event = Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature', ''), $secret);
        } catch (\Throwable $e) {
            return response('Invalid signature', 400);
        }
        $record = StripeEvent::firstOrCreate(['stripe_event_id' => $event->id], ['type' => $event->type, 'payload' => self::minimal(json_decode($request->getContent(), true) ?: [])]);
        if ($record->processed_at) {
            return response('Already processed', 200);
        }
        $stripe->applyEvent($event);
        $record->update(['processed_at' => now()]);

        return response('OK', 200);
    }

    /**
     * The stored copy keeps what reconciles a payment, never the customer details Stripe adds (name, email, address,
     * card): the privacy notice keeps payment records, not the payer's contact data.
     */
    private static function minimal(array $payload): array
    {
        $object = $payload['data']['object'] ?? [];
        $keep = array_intersect_key($object, array_flip(['id', 'object', 'amount', 'amount_total', 'amount_received', 'amount_refunded', 'currency', 'status', 'payment_status', 'payment_intent', 'metadata', 'created']));

        return ['id' => $payload['id'] ?? null, 'type' => $payload['type'] ?? null, 'created' => $payload['created'] ?? null, 'livemode' => $payload['livemode'] ?? null, 'data' => ['object' => $keep]];
    }
}
