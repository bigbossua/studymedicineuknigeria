<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\AdminAction;
use App\Models\Application;
use App\Models\Payment;
use App\Models\ServiceTier;
use App\Models\TierPrice;
use App\Models\User;
use App\Services\Payments\StripeService;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * The paid service journey with the owner-approved fees (T1 £75, T2 £395, T3 £795): what the page shows is what
 * Stripe is asked to charge, the browser never sets a price, and only a verified Stripe event marks a fee paid.
 * Stripe itself is replaced by a recorder (no network); webhook events are signed with the test secret.
 */
class ServicePaymentsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_service_test';

    private object $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(PlatformSeeder::class);
        config(['services.stripe.secret' => 'sk_test_fake', 'services.stripe.webhook_secret' => self::SECRET]);
        $this->stripe = new class
        {
            public object $checkout;

            public array $created = [];

            public array $expired = [];

            public function __construct()
            {
                $root = $this;
                $this->checkout = (object) ['sessions' => new class($root)
                {
                    public function __construct(private object $root) {}

                    public function create(array $params): object
                    {
                        $this->root->created[] = $params;
                        $id = 'cs_test_'.count($this->root->created);

                        return (object) ['id' => $id, 'url' => 'https://checkout.stripe.com/c/pay/'.$id, 'payment_intent' => null];
                    }

                    public function expire(string $id): object
                    {
                        $this->root->expired[] = $id;

                        return (object) ['id' => $id];
                    }
                }];
            }
        };
        $this->app->instance(StripeClient::class, $this->stripe);
    }

    private function applicationFor(string $code, ?User $student = null): Application
    {
        $student ??= User::factory()->create();
        $this->actingAs($student)->post('/portal/start', ['service_tier_id' => ServiceTier::where('code', $code)->value('id'), 'intake_year' => now()->year + 2])->assertRedirect();

        return Application::where('user_id', $student->id)->latest('id')->firstOrFail();
    }

    private function checkout(Application $a, array $extra = [])
    {
        return $this->actingAs($a->user)->post("/portal/{$a->application_number}/payments/checkout", ['tier_price_id' => $a->tier->priceFor('full')->id, 'accept_terms' => 1] + $extra);
    }

    private function webhook(string $id, string $type, array $object)
    {
        $payload = json_encode(['id' => $id, 'object' => 'event', 'type' => $type, 'api_version' => '2024-06-20', 'created' => time(), 'livemode' => false, 'data' => ['object' => $object]]);
        $ts = time();

        return $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars(['Stripe-Signature' => "t={$ts},v1=".hash_hmac('sha256', $ts.'.'.$payload, self::SECRET), 'Content-Type' => 'application/json']), $payload);
    }

    private function completed(Payment $p, array $override = []): array
    {
        return $override + ['object' => 'checkout.session', 'id' => $p->stripe_checkout_session_id, 'payment_status' => 'paid', 'amount_total' => $p->amount_minor, 'currency' => 'gbp', 'payment_intent' => 'pi_'.$p->id, 'metadata' => ['payment_id' => (string) $p->id]];
    }

    public function test_the_pricing_page_shows_the_approved_fees_with_t2_most_popular_and_the_disclaimer(): void
    {
        $page = $this->get('/apply-online/services')->assertOk();
        $page->assertSee('£75')->assertSee('£395')->assertSee('£795')->assertDontSee('Price to be confirmed');
        $this->assertMatchesRegularExpression('#Most popular</p>\s*<p class="eyebrow mt-2">Prepare your medical application</p>\s*<h2 id="t-T2"#', $page->getContent());
        $page->assertSee('Our service fees are separate from university tuition, application fees and other third-party costs. We do not guarantee admission, a visa, a scholarship or an offer from any university.');
        $page->assertSee('UCAT or GAMSAT fees', false);
        foreach (['guaranteed admission', 'success rate', 'limited places', 'only a few'] as $claim) {
            $page->assertDontSee($claim);
        }
        $this->assertSame(['T1' => 7500, 'T2' => 39500, 'T3' => 79500], ServiceTier::with('prices')->orderBy('sort')->get()->mapWithKeys(fn ($t) => [$t->code => $t->priceFor('full')->amount_minor])->all());
    }

    public function test_each_service_sends_stripe_exactly_the_price_the_page_shows_in_gbp(): void
    {
        foreach (['T1' => [7500, '£75'], 'T2' => [39500, '£395'], 'T3' => [79500, '£795']] as $code => [$minor, $shown]) {
            $a = $this->applicationFor($code);
            $this->actingAs($a->user)->get("/portal/{$a->application_number}/payments")->assertOk()->assertSee($shown)
                ->assertSee('Study Medicine UK Nigeria service fee, not a payment to any university')->assertSee('Continue to secure payment');
            $this->checkout($a)->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_'.count($this->stripe->created));
            $params = end($this->stripe->created);
            $this->assertSame($minor, $params['line_items'][0]['price_data']['unit_amount'], $code);
            $this->assertSame('gbp', $params['line_items'][0]['price_data']['currency']);
            $this->assertStringStartsWith('Study Medicine UK Nigeria service fee', $params['line_items'][0]['price_data']['product_data']['name']);
            $this->assertSame($a->application_number, $params['client_reference_id']);
            $payment = $a->payments()->latest('id')->first();
            $this->assertSame([$minor, 'GBP', 'INITIATED'], [$payment->amount_minor, $payment->currency, $payment->status]);
        }
    }

    public function test_the_browser_can_never_set_or_switch_the_price(): void
    {
        $a = $this->applicationFor('T2');
        $this->checkout($a, ['amount' => 1, 'unit_amount' => 100, 'amount_minor' => 100, 'currency' => 'ngn'])->assertRedirect();
        $this->assertSame(39500, $this->stripe->created[0]['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('gbp', $this->stripe->created[0]['line_items'][0]['price_data']['currency']);

        $cheaper = ServiceTier::where('code', 'T1')->first()->priceFor('full');
        $this->actingAs($a->user)->post("/portal/{$a->application_number}/payments/checkout", ['tier_price_id' => $cheaper->id, 'accept_terms' => 1])->assertForbidden();
        $this->actingAs($a->user)->post("/portal/{$a->application_number}/payments/manual", ['tier_price_id' => $cheaper->id, 'accept_terms' => 1])->assertForbidden();
        $this->assertCount(1, $this->stripe->created);
    }

    public function test_another_student_cannot_see_pay_change_or_read_a_return_page(): void
    {
        $a = $this->applicationFor('T2');
        $intruder = User::factory()->create();
        $n = $a->application_number;
        $this->actingAs($intruder)->get("/portal/{$n}/payments")->assertForbidden();
        $this->actingAs($intruder)->post("/portal/{$n}/payments/checkout", ['tier_price_id' => $a->tier->priceFor('full')->id, 'accept_terms' => 1])->assertForbidden();
        $this->actingAs($intruder)->post("/portal/{$n}/payments/service", ['service_tier_id' => ServiceTier::where('code', 'T1')->value('id')])->assertForbidden();
        $this->actingAs($intruder)->get("/portal/{$n}/payments/return?session_id=cs_test_1")->assertForbidden();
        $this->assertCount(0, $this->stripe->created);
    }

    public function test_only_the_verified_webhook_marks_the_fee_paid_and_replays_change_nothing(): void
    {
        $a = $this->applicationFor('T3');
        $this->checkout($a);
        $payment = $a->payments()->first();

        // returning from Stripe is not payment
        $this->actingAs($a->user)->get("/portal/{$a->application_number}/payments/return?session_id={$payment->stripe_checkout_session_id}")->assertOk()->assertSee('Your application is marked paid only when that confirmation arrives');
        $this->assertSame('INITIATED', $payment->fresh()->status);

        // a forged or unsigned event is refused
        $payload = json_encode(['id' => 'evt_forged', 'type' => 'checkout.session.completed', 'data' => ['object' => $this->completed($payment)]]);
        $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars(['Stripe-Signature' => 't=1,v1=forged', 'Content-Type' => 'application/json']), $payload)->assertStatus(400);
        $this->assertSame('INITIATED', $payment->fresh()->status);

        $this->webhook('evt_paid', 'checkout.session.completed', $this->completed($payment))->assertOk()->assertSee('OK');
        $payment->refresh();
        $this->assertSame(['SUCCEEDED', 79500, 'pi_'.$payment->id], [$payment->status, $payment->amount_minor, $payment->stripe_payment_intent_id]);
        $this->assertTrue($a->fresh()->hasSucceededPayment());

        // the same event again, and a second event for the same session, change nothing
        $this->webhook('evt_paid', 'checkout.session.completed', $this->completed($payment))->assertOk()->assertSee('Already processed');
        $this->webhook('evt_paid_again', 'checkout.session.async_payment_succeeded', $this->completed($payment))->assertOk();
        $this->assertSame(1, $a->events()->where('type', 'payment.succeeded')->count());

        // the student sees it, and cannot pay twice
        $this->actingAs($a->user)->get("/portal/{$a->application_number}/payments")->assertSee('Your service is paid')->assertDontSee('Continue to secure payment');
        $this->checkout($a)->assertRedirect("/portal/{$a->application_number}/payments");
        $this->assertCount(1, $this->stripe->created);
    }

    public function test_a_paid_amount_or_currency_that_differs_is_held_for_staff_and_never_marked_paid(): void
    {
        $a = $this->applicationFor('T2');
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->checkout($a);
        $payment = $a->payments()->first();
        $this->webhook('evt_short', 'checkout.session.completed', $this->completed($payment, ['amount_total' => 100]))->assertOk();
        $this->assertSame('MANUAL_REVIEW', $payment->fresh()->status);
        $this->assertFalse($a->fresh()->hasSucceededPayment());
        $this->assertDatabaseHas('application_events', ['application_id' => $a->id, 'type' => 'payment.amount_mismatch']);

        $this->checkout($b = $this->applicationFor('T1'));
        $p2 = $b->payments()->first();
        $this->webhook('evt_ngn', 'checkout.session.completed', $this->completed($p2, ['currency' => 'ngn']))->assertOk();
        $this->assertSame('MANUAL_REVIEW', $p2->fresh()->status);

        // a session id that is not the one created for the payment is ignored
        $this->checkout($c = $this->applicationFor('T1'));
        $p3 = $c->payments()->first();
        $this->webhook('evt_other_session', 'checkout.session.completed', $this->completed($p3, ['id' => 'cs_someone_else']))->assertOk();
        $this->assertSame('INITIATED', $p3->fresh()->status);
    }

    public function test_failed_cancelled_and_expired_checkouts_leave_the_fee_unpaid_and_can_be_retried(): void
    {
        $a = $this->applicationFor('T2');
        $n = $a->application_number;
        $this->checkout($a);
        $first = $a->payments()->first();

        // cancelled on Stripe: back on the confirmation page with an explanation, nothing paid
        $this->actingAs($a->user)->get("/portal/{$n}/payments?cancelled=1")->assertSee('Payment cancelled')->assertSee('nothing was charged')->assertSee('Continue to secure payment');

        $first->forceFill(['stripe_payment_intent_id' => 'pi_fail_1'])->save();
        $this->webhook('evt_fail', 'payment_intent.payment_failed', ['object' => 'payment_intent', 'id' => 'pi_fail_1', 'last_payment_error' => ['message' => 'Your card was declined.']])->assertOk();
        $this->assertSame('FAILED', $first->fresh()->status);
        $this->actingAs($a->user)->get("/portal/{$n}/payments/return?session_id={$first->stripe_checkout_session_id}")->assertSee('The payment did not go through')->assertSee('Try again');
        $this->assertDatabaseHas('application_events', ['application_id' => $a->id, 'type' => 'payment.failed']);

        // retry opens a new checkout; the older open one is expired when the new one starts
        $this->checkout($a)->assertRedirect();
        $second = $a->payments()->latest('id')->first();
        $this->webhook('evt_exp', 'checkout.session.expired', ['object' => 'checkout.session', 'id' => $second->stripe_checkout_session_id, 'metadata' => ['payment_id' => (string) $second->id]])->assertOk();
        $this->assertSame('EXPIRED', $second->fresh()->status);
        $this->assertDatabaseHas('application_events', ['application_id' => $a->id, 'type' => 'payment.expired']);
        $this->assertFalse($a->fresh()->hasSucceededPayment());
    }

    public function test_refunds_are_recorded_once_and_shown_to_staff(): void
    {
        $a = $this->applicationFor('T1');
        $this->checkout($a);
        $p = $a->payments()->first();
        $this->webhook('evt_paid_r', 'checkout.session.completed', $this->completed($p))->assertOk();
        $this->webhook('evt_ref_1', 'charge.refunded', ['object' => 'charge', 'id' => 'ch_1', 'payment_intent' => 'pi_'.$p->id, 'amount_refunded' => 2500])->assertOk();
        $this->assertSame(['REFUNDED_PARTIAL', 2500], [$p->fresh()->status, $p->fresh()->refunded_minor]);
        $this->webhook('evt_ref_1b', 'charge.refunded', ['object' => 'charge', 'id' => 'ch_1', 'payment_intent' => 'pi_'.$p->id, 'amount_refunded' => 2500])->assertOk();
        $this->assertSame(1, $a->events()->where('type', 'payment.refunded')->count(), 'a repeated refund amount is not recorded twice');
        $this->webhook('evt_ref_2', 'charge.refunded', ['object' => 'charge', 'id' => 'ch_1', 'payment_intent' => 'pi_'.$p->id, 'amount_refunded' => 7500])->assertOk();
        $this->assertSame('REFUNDED_FULL', $p->fresh()->status);
    }

    public function test_a_live_stripe_key_is_refused_outside_production_and_live_events_are_ignored(): void
    {
        $a = $this->applicationFor('T2');
        config(['services.stripe.secret' => 'sk_live_should_not_be_here']);
        $this->checkout($a)->assertSessionHas('error');
        $this->assertCount(0, $this->stripe->created);

        config(['services.stripe.secret' => 'sk_test_fake']);
        $this->checkout($a);
        $p = $a->payments()->first();
        $payload = json_encode(['id' => 'evt_live', 'object' => 'event', 'type' => 'checkout.session.completed', 'api_version' => '2024-06-20', 'created' => time(), 'livemode' => true, 'data' => ['object' => $this->completed($p)]]);
        $ts = time();
        $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars(['Stripe-Signature' => "t={$ts},v1=".hash_hmac('sha256', $ts.'.'.$payload, self::SECRET), 'Content-Type' => 'application/json']), $payload)->assertOk();
        $this->assertSame('INITIATED', $p->fresh()->status);
    }

    public function test_choosing_a_service_on_the_pricing_page_carries_through_sign_up_and_can_change_before_payment(): void
    {
        $this->get('/apply-online/start/t3')->assertRedirect(route('register'));
        $student = User::factory()->create();
        $this->actingAs($student)->withSession(['intended_service' => 'T3'])->get('/portal')->assertOk()
            ->assertSee('value="'.ServiceTier::where('code', 'T3')->value('id').'" class="mt-1 w-5 h-5" aria-label="Full Medical Application Support" checked', false);
        $this->get('/apply-online/start/t9')->assertNotFound();

        $a = $this->applicationFor('T3', $student);
        $this->checkout($a);
        $t1 = ServiceTier::where('code', 'T1')->first();
        $this->actingAs($student)->post("/portal/{$a->application_number}/payments/service", ['service_tier_id' => $t1->id])->assertRedirect();
        $this->assertSame($t1->id, $a->fresh()->service_tier_id);
        $this->assertSame(['cs_test_1'], $this->stripe->expired, 'the open checkout for the old service is closed at Stripe');
        $this->assertSame('EXPIRED', $a->payments()->first()->status);
        $this->assertDatabaseHas('application_events', ['application_id' => $a->id, 'type' => 'service.changed']);

        // a checkout completed for the old service is never applied to the new one
        $old = $a->payments()->first();
        $this->webhook('evt_late_old', 'checkout.session.completed', $this->completed($old))->assertOk();
        $this->assertSame('MANUAL_REVIEW', $old->fresh()->status);

        // once paid, the service is fixed
        $this->checkout($a = $a->fresh());
        $paid = $a->payments()->latest('id')->first();
        $this->webhook('evt_paid_t1', 'checkout.session.completed', $this->completed($paid))->assertOk();
        $this->actingAs($student)->post("/portal/{$a->application_number}/payments/service", ['service_tier_id' => ServiceTier::where('code', 'T2')->value('id')])->assertSessionHas('error');
        $this->assertSame($t1->id, $a->fresh()->service_tier_id);
    }

    public function test_staff_see_service_amount_status_date_and_reference_and_price_changes_are_audited(): void
    {
        $a = $this->applicationFor('T2');
        $this->checkout($a);
        $p = $a->payments()->first();
        $this->webhook('evt_staff', 'checkout.session.completed', $this->completed($p))->assertOk();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $as = fn () => $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id]);
        $as()->get("/admin/applications/{$a->application_number}")->assertOk()->assertSee('T2 £395.00')->assertSee('Paid')->assertSee('pi_'.$p->id)->assertSee('Payment received');
        $as()->get('/admin/payments')->assertOk()->assertSee($a->application_number)->assertSee('£395.00');

        $price = ServiceTier::where('code', 'T2')->first()->priceFor('full');
        $as()->post("/admin/services/prices/{$price->id}", ['amount' => '400'])->assertSessionHas('status');
        $this->assertDatabaseHas('admin_actions', ['action' => 'price.update', 'target_id' => $price->id]);
        $this->assertEquals(['tier' => 'T2', 'before_minor' => 39500, 'after_minor' => 40000], AdminAction::where('action', 'price.update')->first()->payload);
        $this->assertSame(39500, $p->fresh()->amount_minor, 'a payment keeps the amount it was taken at');

        // a deploy's reference sync never reverts an admin's price, and fills only an empty one
        $this->seed(PlatformSeeder::class);
        $this->assertSame(40000, $price->fresh()->amount_minor);
        $price->update(['amount_minor' => null]);
        $this->seed(PlatformSeeder::class);
        $this->assertSame(39500, $price->fresh()->amount_minor);
        $this->assertSame(0, TierPrice::where('active', true)->whereNull('amount_minor')->count());
    }

    public function test_the_local_stripe_stand_in_can_never_be_used_on_a_server(): void
    {
        $this->app->forgetInstance(StripeClient::class);
        $this->app->offsetUnset(StripeClient::class);
        config(['services.stripe.api_base' => 'http://127.0.0.1:12111']);
        $this->assertSame('http://127.0.0.1:12111', app(StripeService::class)->client()->getApiBase(), 'local QA may use it');
        foreach (['staging', 'production'] as $env) {
            $this->app['env'] = $env;
            $this->assertSame('https://api.stripe.com', app(StripeService::class)->client()->getApiBase(), $env);
        }
        $this->app['env'] = 'testing';
    }

    public function test_the_browser_may_follow_the_checkout_redirect_to_stripe_only(): void
    {
        $a = $this->applicationFor('T2');
        $csp = $this->actingAs($a->user)->get("/portal/{$a->application_number}/payments")->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("form-action 'self' https://checkout.stripe.com", $csp, 'without it the browser blocks the redirect to Stripe Checkout');
        config(['services.stripe.api_base' => 'http://127.0.0.1:12111']);
        $this->assertStringNotContainsString('127.0.0.1', $this->get('/')->headers->get('Content-Security-Policy'), 'the local stand-in is never allowed outside APP_ENV=local');
    }
}
