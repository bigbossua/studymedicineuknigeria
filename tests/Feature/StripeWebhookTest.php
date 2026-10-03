<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Payment;
use App\Models\ServiceTier;
use App\Models\StripeEvent;
use App\Models\TierPrice;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        $this->actingAs($user)->post('/portal/start', ['service_tier_id' => $tier->id, 'intake_year' => 2028])->assertRedirect();
        $this->application = Application::first();
        $this->payment = $this->application->payments()->create([
            'tier_price_id' => TierPrice::first()?->id, 'status' => 'INITIATED', 'amount_minor' => 25000, 'currency' => 'GBP', 'method' => 'STRIPE',
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
        $object = ['object' => 'checkout.session', 'id' => 'cs_test_123', 'payment_status' => 'paid', 'payment_intent' => 'pi_test_9', 'metadata' => ['payment_id' => (string) $this->payment->id, 'application_number' => $this->application->application_number]];
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

        [$p, $h] = $this->signed($this->event('evt_ref2', 'charge.refunded', ['object' => 'charge', 'id' => 'ch_1', 'payment_intent' => 'pi_fail', 'amount_refunded' => 25000]));
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
}
