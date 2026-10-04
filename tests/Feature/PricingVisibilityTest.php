<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Application;
use App\Models\ServiceTier;
use App\Models\TierPrice;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;
use Tests\TestCase;

/**
 * Owner decision 2026-10-04: service fees are not public. Anonymous visitors and students whose profile has not been
 * reviewed see what each service includes, never a fee; an approved student sees every fee, chooses and pays; staff see
 * fees. Enforced on the server (nothing is hidden with CSS or scripts), so page source, JSON and redirects carry no fee.
 */
class PricingVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** Every way the three fees could be written, including JSON-LD and data attributes. */
    private const LEAKS = ['£125', '£695', '£1,295', '£1295', '125.00', '695.00', '1295.00', '1,295.00', '12500', '69500', '129500', '"price"', 'priceCurrency', 'data-service-price', 'data-checkout-price'];

    private FakeStripeClient $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(PlatformSeeder::class);
        config(['services.stripe.secret' => 'sk_test_fake', 'services.stripe.webhook_secret' => 'whsec_x']);
        $this->stripe = new FakeStripeClient;
        $this->app->instance(StripeClient::class, $this->stripe);
    }

    private function assertNoFee(TestResponse $response, string $where): void
    {
        $body = $response->baseResponse->getContent().' '.json_encode($response->headers->all());
        foreach (self::LEAKS as $leak) {
            $this->assertStringNotContainsString($leak, $body, "$where exposes $leak");
        }
    }

    private function student(): array
    {
        $student = User::factory()->create();
        $this->actingAs($student)->post('/portal/start', ['intake_year' => now()->year + 2])->assertRedirect();

        return [$student, Application::where('user_id', $student->id)->firstOrFail()];
    }

    private function staff(string $role = 'staff'): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $role, 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();

        return $u;
    }

    public function test_an_anonymous_visitor_never_receives_a_fee_on_any_public_page_feed_or_redirect(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk();
        $this->assertNoFee($sitemap, 'sitemap');
        preg_match_all('#<loc>([^<]+)</loc>#', $sitemap->getContent(), $m);
        $this->assertGreaterThan(20, count($m[1]));
        foreach ($m[1] as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $this->assertNoFee($this->get($path), $path);
        }
        foreach (['/robots.txt', '/apply-online', '/apply-online/services', '/apply-online/eligibility', '/register', '/login', '/apply-online/start/t2', '/portal', '/no-such-page'] as $path) {
            $this->assertNoFee($this->get($path), $path);
        }
        $this->assertNoFee($this->getJson('/apply-online/services'), 'services as JSON');
        $this->assertNoFee($this->post('/apply-online/eligibility', ['route' => 'alevels']), 'eligibility result');
        $this->get('/apply-online/services')->assertSee('Service options and pricing are provided after your profile has been reviewed.');
    }

    public function test_a_student_whose_profile_is_not_reviewed_sees_services_but_no_fee_anywhere(): void
    {
        [$student, $a] = $this->student();
        $n = $a->application_number;
        $this->assertFalse($student->canSeeServicePrices($a));
        $this->assertNoFee($this->actingAs($student)->get('/portal'), 'dashboard');
        $this->actingAs($student)->get('/portal')->assertSee('Service options and pricing are provided after your profile has been reviewed.');
        foreach (["/portal/$n/application", "/portal/$n/application/personal", "/portal/$n/documents", "/portal/$n/messages", '/portal/profile', '/portal/profile/export'] as $path) {
            $this->assertNoFee($this->actingAs($student)->get($path), $path);
        }
        $this->assertNoFee($this->actingAs($student)->postJson("/portal/$n/application/personal", ['legal_first_names' => 'Ada']), 'autosave JSON');

        // the price pages refuse on the server and the redirect says why, without a fee
        foreach (["/portal/$n/services", "/portal/$n/payments"] as $path) {
            $r = $this->actingAs($student)->get($path)->assertRedirect(route('portal.dashboard'));
            $this->assertNoFee($r, $path);
            $this->assertNoFee($this->actingAs($student)->get($path)->assertSessionHas('status', 'Service options and pricing are provided after your profile has been reviewed.'), $path);
        }
        $price = ServiceTier::where('code', 'T1')->first()->priceFor('full');
        $this->actingAs($student)->post("/portal/$n/payments/service", ['service_tier_id' => $price->service_tier_id])->assertForbidden();
        $this->actingAs($student)->post("/portal/$n/payments/checkout", ['tier_price_id' => $price->id, 'accept_terms' => 1])->assertForbidden();
        $this->actingAs($student)->post("/portal/$n/payments/manual", ['tier_price_id' => $price->id, 'accept_terms' => 1])->assertForbidden();
        $this->assertNull($a->fresh()->service_tier_id);
        $this->assertSame(0, $a->payments()->count());
        $this->assertSame([], $this->stripe->created);
    }

    public function test_staff_approval_opens_the_fees_and_the_student_chooses_any_service_at_its_exact_price(): void
    {
        [$student, $a] = $this->student();
        $staff = $this->staff();
        $this->actingAs($staff)->withSession([EnsureTwoFactor::SESSION_KEY => $staff->id])->post("/admin/applications/{$a->application_number}/services-approval", ['decision' => 'approve', 'note' => 'Welcome'])->assertSessionHas('status');
        $a->refresh();
        $this->assertTrue($a->servicesApproved());
        $this->assertSame($staff->id, $a->services_approved_by);
        $this->assertDatabaseHas('admin_actions', ['action' => 'application.services_approve', 'target_id' => $a->id]);
        $this->assertDatabaseHas('application_events', ['application_id' => $a->id, 'type' => 'services.approved']);
        Notification::assertSentTo($student, ApplicationNotification::class, fn ($n) => $n->type === 'services.approved');
        $this->actingAs($student)->get('/portal')->assertSee('choose your service', false);

        $n = $a->application_number;
        $page = $this->actingAs($student)->get("/portal/$n/services")->assertOk()->assertSee('Most popular');
        foreach (['T1' => ['£125', 12500], 'T2' => ['£695', 69500], 'T3' => ['£1,295', 129500]] as $code => [$shown, $minor]) {
            $this->assertStringContainsString('data-service-price="'.$code.'">'.$shown.'<', $page->getContent());
            $tier = ServiceTier::where('code', $code)->first();
            $this->actingAs($student)->post("/portal/$n/payments/service", ['service_tier_id' => $tier->id])->assertRedirect(route('portal.payments.index', $a));
            // before payment: exact name, fee, inclusions, exclusions, third-party costs, refunds, no guarantee, payment terms
            $confirm = $this->actingAs($student)->get("/portal/$n/payments")->assertOk()
                ->assertSee($tier->name)->assertSee('data-checkout-price>'.$shown.'<', false)
                ->assertSee($tier->deliverables[0])->assertSee($tier->exclusions[0])
                ->assertSee('Our service fees are separate from university tuition, application fees and other third-party costs. We do not guarantee admission, a visa, a scholarship or an offer from any university.')
                ->assertSee('Refunds:')->assertSee('Payment terms:')->assertSee('one payment of '.$shown, false)->assertSee('accept_terms', false);
            $this->actingAs($student)->post("/portal/$n/payments/checkout", ['tier_price_id' => $tier->priceFor('full')->id, 'accept_terms' => 1, 'amount' => 1])->assertRedirect();
            $this->assertSame([$minor, 'gbp'], $this->stripe->chargedAmount(end($this->stripe->created)), "$code: Stripe charges the authoritative price");
            $this->assertSame($minor, $a->payments()->latest('id')->first()->amount_minor);
        }
    }

    public function test_one_students_approval_never_shows_fees_to_another_student(): void
    {
        [, $approved] = $this->student();
        $this->approveServices($approved);
        [$other, $mine] = $this->student();
        $this->assertFalse($other->canSeeServicePrices($mine));
        $this->assertFalse($other->canSeeServicePrices($approved));
        $this->actingAs($other)->get("/portal/{$approved->application_number}/services")->assertForbidden();
        $this->actingAs($other)->get("/portal/{$mine->application_number}/services")->assertRedirect(route('portal.dashboard'));
    }

    public function test_staff_see_the_fees_and_price_changes_stay_audited(): void
    {
        [, $a] = $this->student();
        foreach (['staff', 'admin'] as $role) {
            $u = $this->staff($role);
            $this->assertTrue($u->canSeeServicePrices());
            $this->actingAs($u)->withSession([EnsureTwoFactor::SESSION_KEY => $u->id])->get('/admin/services')->assertOk()->assertSee('£1,295')->assertSee('£695')->assertSee('£125');
            $this->actingAs($u)->withSession([EnsureTwoFactor::SESSION_KEY => $u->id])->get("/admin/applications/{$a->application_number}")->assertOk()->assertSee('Approve for service selection');
        }
        $staff = User::where('role', 'staff')->first();
        $price = ServiceTier::where('code', 'T2')->first()->priceFor('full');
        $this->actingAs($staff)->withSession([EnsureTwoFactor::SESSION_KEY => $staff->id])->post("/admin/services/prices/{$price->id}", ['amount' => '1'])->assertForbidden();
        $admin = User::where('role', 'admin')->first();
        $price->forceFill(['stripe_price_id' => 'price_old', 'stripe_price_amount' => 69500])->save();
        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])->post("/admin/services/prices/{$price->id}", ['amount' => '700'])->assertSessionHas('status');
        $this->assertDatabaseHas('admin_actions', ['action' => 'price.update', 'target_id' => $price->id]);
        $this->assertNull($price->fresh()->stripe_price_id, 'a changed amount never reuses the old Stripe price');
    }

    public function test_the_stripe_catalogue_is_created_once_and_never_duplicated(): void
    {
        $this->assertSame(0, Artisan::call('smukn:stripe-sync'));
        $this->assertSame(['smukn_t1', 'smukn_t2', 'smukn_t3'], array_keys($this->stripe->productStore));
        $this->assertSame([3, 3], [$this->stripe->productCreates, $this->stripe->priceCreates]);
        foreach (TierPrice::with('tier')->get() as $p) {
            $sp = $this->stripe->priceStore[$p->stripe_price_id];
            $this->assertSame([$p->amount_minor, 'gbp', 'one_time', 'smukn_'.strtolower($p->tier->code).'_gbp', false], [$sp->unit_amount, $sp->currency, $sp->type, $sp->lookup_key, (bool) $p->stripe_livemode]);
            $this->assertStringContainsString('no admission is guaranteed', $this->stripe->productStore['smukn_'.strtolower($p->tier->code)]->description);
        }
        // repeating, or a fresh database that knows no ids, finds the same entries instead of creating more
        $this->assertSame(0, Artisan::call('smukn:stripe-sync'));
        TierPrice::query()->update(['stripe_price_id' => null, 'stripe_price_amount' => null]);
        $this->assertSame(0, Artisan::call('smukn:stripe-sync'));
        $this->assertSame([3, 3], [$this->stripe->productCreates, $this->stripe->priceCreates]);

        // an admin's new amount gets a new Stripe price; the old one is switched off and keeps no lookup key
        $t2 = ServiceTier::where('code', 'T2')->first()->priceFor('full');
        $old = $t2->stripe_price_id;
        $t2->update(['amount_minor' => 70000]);
        $this->assertSame(0, Artisan::call('smukn:stripe-sync'));
        $this->assertNotSame($old, $t2->fresh()->stripe_price_id);
        $this->assertFalse($this->stripe->priceStore[$old]->active);
        $this->assertSame(70000, $this->stripe->priceStore[$t2->fresh()->stripe_price_id]->unit_amount);

        // --check never writes
        TierPrice::query()->update(['amount_minor' => 99900]);
        $creates = $this->stripe->priceCreates;
        $this->assertSame(1, Artisan::call('smukn:stripe-sync', ['--check' => true]));
        $this->assertSame($creates, $this->stripe->priceCreates);
        $this->assertStringContainsString('differs', Artisan::output());
        $this->assertStringNotContainsString('sk_test_fake', Artisan::output());
    }

    public function test_each_service_keeps_exactly_one_active_price_and_payment_links_are_refused(): void
    {
        $this->assertSame(0, Artisan::call('smukn:stripe-sync'));
        $t2 = ServiceTier::where('code', 'T2')->first()->priceFor('full');
        $stray = $this->stripe->addManualPrice('smukn_t2', 50000); // e.g. made by hand in the Dashboard
        $this->assertSame(1, Artisan::call('smukn:stripe-sync', ['--check' => true]), 'a check reports the extra active price');
        $this->assertStringContainsString('other active prices: 1', Artisan::output());
        $this->assertTrue($this->stripe->priceStore[$stray]->active, 'a check changes nothing');
        $this->assertSame(0, Artisan::call('smukn:stripe-sync'));
        $this->assertFalse($this->stripe->priceStore[$stray]->active);
        $this->assertTrue($this->stripe->priceStore[$t2->fresh()->stripe_price_id]->active);
        foreach (['smukn_t1', 'smukn_t2', 'smukn_t3'] as $product) {
            $this->assertCount(1, array_filter($this->stripe->priceStore, fn ($p) => $p->product === $product && $p->active), $product);
        }

        // a Payment Link selling a service bypasses the profile review: reported and the run fails, nothing is changed
        $this->stripe->linkStore['plink_1'] = ['active' => true, 'prices' => [$t2->fresh()->stripe_price_id]];
        $this->assertSame(1, Artisan::call('smukn:stripe-sync'));
        $this->assertStringContainsString('plink_1', Artisan::output());
        $this->assertTrue($this->stripe->linkStore['plink_1']['active']);
    }

    public function test_the_webhook_endpoint_is_verified_or_created_without_printing_its_secret(): void
    {
        config(['app.url' => 'https://staging.studymedicineuknigeria.com']);
        $this->assertSame(1, Artisan::call('smukn:stripe-webhook'));
        $this->assertStringContainsString('No test-mode webhook endpoint', Artisan::output());
        $this->assertSame(1, Artisan::call('smukn:stripe-webhook', ['--create' => true]), 'never created without a place for the secret');
        $file = tempnam(sys_get_temp_dir(), 'whsec');
        $this->assertSame(0, Artisan::call('smukn:stripe-webhook', ['--create' => true, '--secret-file' => $file]));
        $this->assertStringNotContainsString('whsec_created_once', Artisan::output());
        $this->assertSame('whsec_created_once_we_test_1', file_get_contents($file));
        $this->assertSame('0600', substr(sprintf('%o', fileperms($file)), -4));
        unlink($file);
        $this->assertSame(['https://staging.studymedicineuknigeria.com/webhooks/stripe'], array_values(array_map(fn ($e) => $e->url, $this->stripe->endpointStore)));
        $this->assertSame(0, Artisan::call('smukn:stripe-webhook'));
        $this->assertStringContainsString('all required events present', Artisan::output());

        $this->stripe->endpointStore['we_test_1']->enabled_events = ['checkout.session.completed'];
        $this->assertSame(1, Artisan::call('smukn:stripe-webhook'));
        $this->assertStringContainsString('MISSING: checkout.session.async_payment_succeeded', Artisan::output());
        $this->assertSame(1, Artisan::call('smukn:stripe-webhook', ['--url' => 'http://127.0.0.1:8000/webhooks/stripe']), 'Stripe needs a public https URL');
    }

    public function test_until_stripe_is_configured_no_payment_is_offered_or_accepted_and_students_are_told_when_it_opens(): void
    {
        config(['services.stripe.secret' => null, 'site.bank_transfer' => false]);
        [$student, $a] = $this->student();
        $a = $this->approveServices($a, 'T2');
        $n = $a->application_number;
        $page = $this->actingAs($student)->get("/portal/$n/payments")->assertOk()
            ->assertSee('£695')->assertSee('Payment terms:')->assertSee('Refunds:')->assertSee('Payment is not open yet')
            ->assertDontSee('Continue to secure payment')->assertDontSee('Request bank transfer details')->assertDontSee('Pay £695 securely');
        $this->actingAs($student)->get('/portal')->assertSee('Online payment is not open yet');
        $price = $a->tier->priceFor('full');
        $this->actingAs($student)->post("/portal/$n/payments/checkout", ['tier_price_id' => $price->id, 'accept_terms' => 1])->assertSessionHas('error');
        $this->actingAs($student)->post("/portal/$n/payments/manual", ['tier_price_id' => $price->id, 'accept_terms' => 1])->assertSessionHas('error');
        $this->assertSame(0, $a->payments()->count());
        $this->assertSame([], $this->stripe->created);
        $this->get('/apply-online/services')->assertDontSee('bank transfer is available');
        $staff = $this->staff('admin');
        $this->actingAs($staff)->withSession([EnsureTwoFactor::SESSION_KEY => $staff->id])->get('/admin')->assertSee('card payment closed (post-launch step)');

        // nobody is told while payment is closed; once Stripe is configured, each waiting student is told exactly once
        $this->assertSame(0, Artisan::call('smukn:payments-open-notify'));
        Notification::assertNotSentTo($student, ApplicationNotification::class, fn ($x) => $x->type === 'payments.open');
        config(['services.stripe.secret' => 'sk_test_fake']);
        Artisan::call('smukn:payments-open-notify');
        Artisan::call('smukn:payments-open-notify');
        Notification::assertSentToTimes($student, ApplicationNotification::class, 2); // services.approved is not sent by the shortcut; started + payments.open
        $this->assertSame(1, $a->events()->where('type', 'payments.open_notified')->count());
        $this->actingAs($student)->get("/portal/$n/payments")->assertSee('Continue to secure payment')->assertDontSee('Payment is not open yet');
    }

    public function test_bank_transfer_is_offered_only_when_the_owner_switches_it_on(): void
    {
        config(['services.stripe.secret' => null, 'site.bank_transfer' => true]);
        [$student, $a] = $this->student();
        $a = $this->approveServices($a, 'T1');
        $n = $a->application_number;
        $this->actingAs($student)->get("/portal/$n/payments")->assertOk()->assertSee('Request bank transfer details')->assertDontSee('Continue to secure payment')->assertDontSee('Payment is not open yet');
        $this->actingAs($student)->post("/portal/$n/payments/manual", ['tier_price_id' => $a->tier->priceFor('full')->id, 'accept_terms' => 1])->assertSessionHas('status');
        $this->assertSame(['MANUAL_REVIEW', 12500], [$a->payments()->first()->status, $a->payments()->first()->amount_minor]);
        $this->get('/apply-online/services')->assertSee('bank transfer is available');
    }

    public function test_no_payment_link_is_ever_created_and_the_secret_key_never_reaches_a_page(): void
    {
        $source = (string) file_get_contents(app_path('Services/Payments/StripeCatalog.php')).file_get_contents(app_path('Services/Payments/StripeService.php'));
        $this->assertStringNotContainsString('paymentLinks->create', $source, 'Payment Links are only ever audited, never made');
        $this->assertSame(0, preg_match('/paymentLinks->(create|update)/', (string) file_get_contents(app_path('Console/Commands/SyncStripeCatalog.php'))));
        [$student, $a] = $this->student();
        $this->approveServices($a, 'T2');
        $this->actingAs($student)->get("/portal/{$a->application_number}/payments")->assertOk()->assertDontSee('sk_test_fake')->assertDontSee('whsec_x');
    }
}
