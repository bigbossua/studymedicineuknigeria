<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Application;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Services\Payments\StripeService;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;
use Tests\TestCase;

/**
 * Owner instruction 2026-10-31: until the owner approves the commercial launch (SITE_COMMERCIAL_LAUNCH_APPROVED), the
 * production site starts no new payment, by card or bank transfer, even with a live Stripe key installed. Everything
 * already paid or in flight is preserved: a checkout started before the gate still completes by its signed webhook,
 * refunds are still recorded, and a paid student keeps the service.
 */
class CommercialLaunchGateTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_gate_test';

    private FakeStripeClient $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(PlatformSeeder::class);
        config(['services.stripe.secret' => 'sk_test_fake', 'services.stripe.webhook_secret' => self::SECRET, 'site.bank_transfer' => true,
            'site.commercial_launch_gate' => true, 'site.commercial_launch_approved' => null]);
        $this->stripe = new FakeStripeClient;
        $this->app->instance(StripeClient::class, $this->stripe);
    }

    private function application(string $code = 'T1'): Application
    {
        $student = User::factory()->create();
        $this->actingAs($student)->post('/portal/start', ['intake_year' => now()->year + 2])->assertRedirect();

        return $this->approveServices(Application::where('user_id', $student->id)->latest('id')->firstOrFail(), $code);
    }

    private function checkout(Application $a)
    {
        return $this->actingAs($a->user)->post("/portal/{$a->application_number}/payments/checkout", ['tier_price_id' => $a->tier->priceFor('full')->id, 'accept_terms' => 1]);
    }

    private function webhook(string $id, string $type, array $object)
    {
        $payload = json_encode(['id' => $id, 'object' => 'event', 'type' => $type, 'api_version' => '2024-06-20', 'created' => time(), 'livemode' => false, 'data' => ['object' => $object]]);
        $ts = time();

        return $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars(['Stripe-Signature' => "t={$ts},v1=".hash_hmac('sha256', $ts.'.'.$payload, self::SECRET), 'Content-Type' => 'application/json']), $payload);
    }

    private function completed(Payment $p): array
    {
        return ['object' => 'checkout.session', 'id' => $p->stripe_checkout_session_id, 'payment_status' => 'paid', 'amount_total' => $p->amount_minor, 'currency' => 'gbp', 'payment_intent' => 'pi_'.$p->id, 'metadata' => ['payment_id' => (string) $p->id]];
    }

    public function test_production_is_gated_until_the_owner_records_the_approval(): void
    {
        foreach (['production' => true, 'local' => false, 'testing' => false] as $env => $gated) {
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $env;
            try {
                $this->assertSame($gated, (require config_path('site.php'))['commercial_launch_gate'], $env);
            } finally {
                $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
            }
        }
        $stripe = app(StripeService::class);
        $this->assertFalse($stripe->launchApproved());
        $this->assertFalse($stripe->paymentsOpen(), 'a usable key and bank transfer are not enough while the launch is not approved');
        config(['site.commercial_launch_approved' => '2026-11-20']);
        $this->assertTrue($stripe->launchApproved());
        $this->assertTrue($stripe->paymentsOpen());
    }

    public function test_no_new_payment_can_start_while_the_launch_is_not_approved(): void
    {
        $a = $this->application('T2');

        $page = $this->actingAs($a->user)->get("/portal/{$a->application_number}/payments")->assertOk();
        $page->assertSee('Payment is not open yet')->assertDontSee('Continue to secure payment')->assertDontSee('Request bank transfer details');

        $this->checkout($a)->assertSessionHas('error');
        $this->actingAs($a->user)->post("/portal/{$a->application_number}/payments/manual", ['tier_price_id' => $a->tier->priceFor('full')->id, 'accept_terms' => 1])->assertSessionHas('error');
        $this->assertCount(0, $this->stripe->created, 'no Stripe Checkout session was created');
        $this->assertSame([], $this->stripe->writes);
        $this->assertSame(0, Payment::count(), 'no payment record was opened');

        $this->actingAs($a->user)->get('/portal')->assertOk()->assertSee('Online payment is not open yet');
        Notification::fake(); // only what the notifier sends counts here
        Artisan::call('smukn:payments-open-notify');
        Notification::assertNothingSent();

        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])->get(route('admin.dashboard'))
            ->assertSee('Commercial launch approved (new payments accepted)')->assertSee('SITE_COMMERCIAL_LAUNCH_APPROVED');
    }

    public function test_payments_made_or_started_before_the_gate_are_preserved(): void
    {
        // a student paid, and another started a checkout, before the gate closed (approval present at the time)
        config(['site.commercial_launch_approved' => '2026-10-01']);
        $paid = $this->application('T1');
        $this->checkout($paid);
        $p1 = $paid->payments()->firstOrFail();
        $this->webhook('evt_paid_before', 'checkout.session.completed', $this->completed($p1))->assertOk();
        $inFlight = $this->application('T2');
        $this->checkout($inFlight);
        $p2 = $inFlight->payments()->firstOrFail();

        config(['site.commercial_launch_approved' => null]); // the gate closes

        $this->assertTrue($paid->fresh()->hasSucceededPayment(), 'paid access is kept');
        $this->actingAs($paid->user)->get("/portal/{$paid->application_number}/payments")->assertOk()->assertSee('Paid');

        $this->webhook('evt_in_flight', 'checkout.session.completed', $this->completed($p2))->assertOk();
        $this->assertSame('SUCCEEDED', $p2->fresh()->status, 'a checkout started before the gate still completes by its signed webhook');

        $this->webhook('evt_refund', 'charge.refunded', ['object' => 'charge', 'id' => 'ch_1', 'payment_intent' => 'pi_'.$p1->id, 'amount_refunded' => 12500])->assertOk();
        $this->assertSame('REFUNDED_FULL', $p1->fresh()->status, 'refunds are still recorded');
        $this->assertCount(2, $this->stripe->created, 'and nothing new was charged after the gate closed');
    }

    public function test_recording_the_approval_reopens_payment_and_waiting_students_are_told_once(): void
    {
        $a = $this->application('T1');
        $this->checkout($a)->assertSessionHas('error');

        config(['site.commercial_launch_approved' => '2026-11-20']);
        Notification::fake();
        Artisan::call('smukn:payments-open-notify');
        Notification::assertSentTo($a->user, ApplicationNotification::class);
        $this->checkout($a)->assertRedirect();
        $this->assertCount(1, $this->stripe->created);
        $this->assertSame('INITIATED', $a->payments()->firstOrFail()->status);
    }
}
