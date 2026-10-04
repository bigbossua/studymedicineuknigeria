<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Payment;
use App\Models\ServiceTier;
use App\Models\StripeEvent;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Stripe\Webhook;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    private Application $application;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(PlatformSeeder::class);
        config(['services.stripe.webhook_secret' => self::SECRET]);
        $user = User::factory()->create();
        $tier = ServiceTier::where('code', 'T2')->first();
        $this->actingAs($user)->post('/portal/start', ['intake_year' => 2028])->assertRedirect();
        $this->application = $this->approveServices(Application::first(), 'T2');
        $this->payment = $this->application->payments()->create([
            'tier_price_id' => $tier->priceFor('full')->id, 'status' => 'INITIATED', 'amount_minor' => 69500, 'currency' => 'GBP', 'method' => 'STRIPE',
            'stripe_checkout_session_id' => 'cs_test_123', 'terms_version_accepted' => 'v1',
        ]);
    }

    private function signed(array $event): array
    {
        $payload = json_encode($event);
        $ts = time();
        $sig = hash_hmac('sha256', $ts.'.'.$payload, self::SECRET);

        return [$payload, ['Stripe-Signature' => "t={$ts},v1={$sig}", 'Content-Type' => 'application/json']];
    }

    private function event(string $id, string $type, array $object): array
    {
        return ['id' => $id, 'object' => 'event', 'type' => $type, 'api_version' => '2024-06-20', 'created' => time(), 'livemode' => false, 'data' => ['object' => $object]];
    }

    private function webhook(string $payload, array $headers)
    {
        return $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars($headers), $payload);
    }

    public function test_webhook_refuses_unsigned_or_unconfigured_requests(): void
    {
        [$payload, $headers] = $this->signed($this->event('evt_1', 'checkout.session.completed', []));
        $this->webhook($payload, ['Stripe-Signature' => 't=1,v1=bad', 'Content-Type' => 'application/json'])->assertStatus(400);
        $this->assertDatabaseCount('stripe_events', 0);

        config(['services.stripe.webhook_secret' => null]);
        $this->webhook($payload, $headers)->assertStatus(503);
    }

    public function test_completed_checkout_marks_the_payment_paid_once_and_notifies_the_student(): void
    {
        $object = ['object' => 'checkout.session', 'id' => 'cs_test_123', 'payment_status' => 'paid', 'amount_total' => 69500, 'currency' => 'gbp', 'payment_intent' => 'pi_test_9', 'metadata' => ['payment_id' => (string) $this->payment->id, 'application_number' => $this->application->application_number]];
        [$payload, $headers] = $this->signed($this->event('evt_paid', 'checkout.session.completed', $object));

        $this->webhook($payload, $headers)->assertOk()->assertSee('OK');
        $p = $this->payment->fresh();
        $this->assertSame('SUCCEEDED', $p->status);
        $this->assertSame('pi_test_9', $p->stripe_payment_intent_id);
        $this->assertNotNull($p->succeeded_at);
        $this->assertNotNull(StripeEvent::where('stripe_event_id', 'evt_paid')->first()->processed_at);
        Notification::assertSentTo($this->application->user, ApplicationNotification::class, fn ($n) => $n->type === 'payment.succeeded');
        $this->assertDatabaseHas('application_events', ['application_id' => $this->application->id, 'type' => 'payment.succeeded']);
        $this->assertDatabaseHas('funnel_events', ['name' => 'payment_completed', 'tier' => 'T2']);

        // Stripe retries: the same event id is acknowledged without re-processing
        $this->webhook($payload, $headers)->assertOk()->assertSee('Already processed');
        $this->assertSame(1, $this->application->events()->where('type', 'payment.succeeded')->count());
        Notification::assertSentTimes(ApplicationNotification::class, 2); // application.started + payment.succeeded
    }

    public function test_unpaid_completion_expiry_failure_and_refund_are_handled(): void
    {
        $base = ['object' => 'checkout.session', 'id' => 'cs_test_123', 'metadata' => ['payment_id' => (string) $this->payment->id]];

        [$p, $h] = $this->signed($this->event('evt_unpaid', 'checkout.session.completed', $base + ['payment_status' => 'unpaid']));
        $this->webhook($p, $h)->assertOk();
        $this->assertSame('INITIATED', $this->payment->fresh()->status);

        [$p, $h] = $this->signed($this->event('evt_exp', 'checkout.session.expired', $base));
        $this->webhook($p, $h)->assertOk();
        $this->assertSame('EXPIRED', $this->payment->fresh()->status);

        $this->payment->refresh()->update(['status' => 'INITIATED', 'stripe_payment_intent_id' => 'pi_fail']);
        [$p, $h] = $this->signed($this->event('evt_fail', 'payment_intent.payment_failed', ['object' => 'payment_intent', 'id' => 'pi_fail', 'last_payment_error' => ['message' => 'Card declined']]));
        $this->webhook($p, $h)->assertOk();
        $this->assertSame('FAILED', $this->payment->fresh()->status);
        $this->assertSame('Card declined', $this->payment->fresh()->note);

        [$p, $h] = $this->signed($this->event('evt_ref', 'charge.refunded', ['object' => 'charge', 'id' => 'ch_1', 'payment_intent' => 'pi_fail', 'amount_refunded' => 10000]));
        $this->webhook($p, $h)->assertOk();
        $this->assertSame('REFUNDED_PARTIAL', $this->payment->fresh()->status);
        $this->assertSame(10000, $this->payment->fresh()->refunded_minor);

        [$p, $h] = $this->signed($this->event('evt_ref2', 'charge.refunded', ['object' => 'charge', 'id' => 'ch_1', 'payment_intent' => 'pi_fail', 'amount_refunded' => 69500]));
        $this->webhook($p, $h)->assertOk();
        $this->assertSame('REFUNDED_FULL', $this->payment->fresh()->status);

        // a late failure event never downgrades a settled record
        [$p, $h] = $this->signed($this->event('evt_fail_late', 'payment_intent.payment_failed', ['object' => 'payment_intent', 'id' => 'pi_fail', 'last_payment_error' => ['message' => 'late']]));
        $this->webhook($p, $h)->assertOk();
        $this->assertSame('REFUNDED_FULL', $this->payment->fresh()->status);

        // an event for an unknown payment is recorded and acknowledged, nothing changes
        [$p, $h] = $this->signed($this->event('evt_unknown', 'checkout.session.completed', ['object' => 'checkout.session', 'id' => 'cs_other', 'payment_status' => 'paid', 'metadata' => []]));
        $this->webhook($p, $h)->assertOk();
        $this->assertDatabaseHas('stripe_events', ['stripe_event_id' => 'evt_unknown']);
    }

    public function test_a_second_paid_checkout_for_the_same_fee_is_held_for_refund_not_counted(): void
    {
        $paid = ['object' => 'checkout.session', 'id' => 'cs_test_123', 'payment_status' => 'paid', 'amount_total' => 69500, 'currency' => 'gbp', 'payment_intent' => 'pi_test_1', 'metadata' => ['payment_id' => (string) $this->payment->id]];
        [$payload, $headers] = $this->signed($this->event('evt_a', 'checkout.session.completed', $paid));
        $this->webhook($payload, $headers)->assertOk();

        // a checkout left open in another tab, paid afterwards
        $second = $this->application->payments()->create([
            'tier_price_id' => $this->payment->tier_price_id, 'status' => 'EXPIRED', 'amount_minor' => 69500, 'currency' => 'GBP', 'method' => 'STRIPE',
            'stripe_checkout_session_id' => 'cs_test_456', 'terms_version_accepted' => 'v1',
        ]);
        $dup = ['object' => 'checkout.session', 'id' => 'cs_test_456', 'payment_status' => 'paid', 'amount_total' => 69500, 'currency' => 'gbp', 'payment_intent' => 'pi_test_2', 'metadata' => ['payment_id' => (string) $second->id]];
        [$payload, $headers] = $this->signed($this->event('evt_b', 'checkout.session.completed', $dup));
        $this->webhook($payload, $headers)->assertOk();

        $this->assertSame('MANUAL_REVIEW', $second->fresh()->status);
        $this->assertStringContainsString('Duplicate payment', $second->fresh()->note);
        $this->assertSame(1, $this->application->payments()->where('status', 'SUCCEEDED')->count());
        $this->assertDatabaseHas('application_events', ['application_id' => $this->application->id, 'type' => 'payment.duplicate']);
    }

    public function test_the_webhook_self_test_signs_exactly_as_stripe_and_needs_a_200_and_a_400(): void
    {
        config(['services.stripe.secret' => 'sk_test_x']);
        $sent = [];
        Http::fake(function ($request) use (&$sent) {
            try {
                Webhook::constructEvent($request->body(), $request->header('Stripe-Signature')[0], self::SECRET);
                $sent[] = 'valid';

                return Http::response('OK', 200);
            } catch (\Throwable) {
                $sent[] = 'invalid';

                return Http::response('Invalid signature', 400);
            }
        });
        $this->artisan('smukn:stripe-webhook', ['--url' => 'https://studymedicineuknigeria.com/webhooks/stripe', '--self-test' => true])->assertSuccessful();
        $this->assertSame(['valid', 'invalid'], $sent);

        // the signed self-test event is accepted by the real endpoint and changes no payment
        $payload = json_encode($this->event('evt_smukn_selftest_1', 'smukn.self_test', ['object' => 'self_test', 'id' => 'selftest']));
        $t = time();
        $this->webhook($payload, ['Stripe-Signature' => "t={$t},v1=".hash_hmac('sha256', "{$t}.{$payload}", self::SECRET), 'Content-Type' => 'application/json'])->assertOk();
        $this->assertSame('INITIATED', $this->payment->fresh()->status);
    }
}
