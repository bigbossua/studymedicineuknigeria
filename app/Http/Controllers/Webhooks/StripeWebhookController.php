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
        $record = StripeEvent::firstOrCreate(['stripe_event_id' => $event->id], ['type' => $event->type, 'payload' => json_decode($request->getContent(), true)]);
        if ($record->processed_at) {
            return response('Already processed', 200);
        }
        $stripe->applyEvent($event);
        $record->update(['processed_at' => now()]);

        return response('OK', 200);
    }
}
