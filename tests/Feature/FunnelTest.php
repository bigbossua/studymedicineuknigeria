<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Application;
use App\Models\FunnelEvent;
use App\Models\ServiceTier;
use App\Models\University;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FunnelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_lead_and_account_events_are_recorded_without_personal_data(): void
    {
        $this->post('/apply-online/eligibility?utm_source=whatsapp', ['qualification' => 'waec_only', 'sciences' => 'yes', 'english' => 'none', 'ucat' => 'none', 'intake_year' => 2027, 'name' => 'Ada Okonkwo', 'email' => 'ada@example.test', 'consent' => 1])->assertRedirect();
        $e = FunnelEvent::where('name', 'lead_created')->first();
        $this->assertNotNull($e);
        $this->assertSame('waec_only', $e->properties['qualification']);
        $this->assertSame(2027, $e->intake_year);
        $this->assertSame('whatsapp', $e->utm['utm_source']);
        $this->assertNotNull($e->visitor_hash);
        $this->assertStringNotContainsString('ada', strtolower(json_encode($e->toArray())));

        $this->post('/register', ['name' => 'Ada Okonkwo', 'email' => 'ada@example.test', 'password' => 'Longpass12345', 'password_confirmation' => 'Longpass12345', 'terms' => 1])->assertRedirect();
        $this->assertDatabaseHas('funnel_events', ['name' => 'account_created', 'user_id' => User::first()->id]);
    }

    public function test_application_events_mirror_into_the_funnel_with_a_hashed_application_number(): void
    {
        $this->seed(PlatformSeeder::class);
        $student = User::factory()->create();
        $tier = ServiceTier::where('code', 'T2')->first();
        $this->actingAs($student)->post('/portal/start', ['service_tier_id' => $tier->id, 'intake_year' => 2028])->assertRedirect();
        $a = Application::first();

        $e = FunnelEvent::where('name', 'application_started')->first();
        $this->assertNotNull($e);
        $this->assertSame(hash('sha256', $a->application_number), $e->application_hash);
        $this->assertSame('T2', $e->tier);
        $this->assertSame(2028, $e->intake_year);
        $this->assertSame('student', $e->properties['actor']);
        $this->assertStringNotContainsString($a->application_number, json_encode($e->toArray()));

        $a->record('document.uploaded', ['document_id' => 1, 'title' => 'Passport', 'version' => 1], $student->id);
        $a->record('payment.succeeded', ['payment_id' => 1, 'amount' => '£0.00']);
        $a->record('message.sent', [], $student->id); // not a reporting event
        $this->assertDatabaseHas('funnel_events', ['name' => 'document_uploaded']);
        $this->assertDatabaseHas('funnel_events', ['name' => 'payment_completed']);
        $this->assertDatabaseMissing('funnel_events', ['name' => 'message_sent']);
        $this->assertSame('staff', FunnelEvent::where('name', 'payment_completed')->first()->properties['actor']);
    }

    public function test_public_page_views_feed_the_funnel_without_personal_data(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $u = University::first();
        $this->get('/medical-schools/'.$u->slug)->assertOk();
        $e = FunnelEvent::where('name', 'course_viewed')->first();
        $this->assertNotNull($e);
        $this->assertSame($u->slug, $e->properties['school']);
        $this->assertNull($e->user_id);

        config(['site.ga4_id' => 'G-TEST123']);
        $this->get('/apply-online')->assertOk()->assertSee('name="funnel-events"', false)->assertSee('apply_viewed', false);
        $this->assertDatabaseHas('funnel_events', ['name' => 'apply_viewed']);
    }

    public function test_admin_funnel_page_shows_steps_and_conversion(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        foreach (['lead_created', 'lead_created', 'account_created'] as $n) {
            FunnelEvent::create(['name' => $n, 'occurred_at' => now(), 'visitor_hash' => hash('sha256', $n.rand())]);
        }
        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])->get('/admin/funnel?days=7')->assertOk()->assertSee('lead_created')->assertSee('50%')->assertSee('Not configured');
    }

    public function test_third_party_analytics_are_absent_unless_configured_and_consented(): void
    {
        $r = $this->get('/')->assertOk()->assertDontSee('data-consent-banner', false)->assertDontSee('ga4-id', false);
        $this->assertStringNotContainsString('googletagmanager', $r->headers->get('Content-Security-Policy'));

        config(['site.ga4_id' => 'G-TEST123']);
        $r = $this->get('/')->assertOk()->assertSee('data-consent-banner', false)->assertSee('<meta name="ga4-id" content="G-TEST123">', false);
        $this->assertStringContainsString('https://www.googletagmanager.com', $r->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('gtag/js', $r->getContent(), 'the tag is never in the server HTML; app.js loads it after consent');

        $this->withUnencryptedCookie('smukn_consent', 'denied')->get('/')->assertOk()->assertDontSee('data-consent-banner', false);
        $this->withUnencryptedCookie('smukn_consent', 'granted')->get('/')->assertOk()->assertDontSee('data-consent-banner', false)->assertSee('ga4-id', false);

        // portal and admin never get the Google hosts even when configured
        $student = User::factory()->create();
        $this->assertStringNotContainsString('googletagmanager', $this->actingAs($student)->get('/portal')->headers->get('Content-Security-Policy'));
    }
}
