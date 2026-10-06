<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Application;
use App\Models\FunnelEvent;
use App\Models\University;
use App\Models\User;
use App\Support\Funnel;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        // No personal data in the event: check the surname and the mailbox domain (a hex hash can never contain 'k', 'o' or '.'), not a short substring a random hash could contain.
        $json = strtolower(json_encode($e->toArray()));
        $this->assertStringNotContainsString('okonkwo', $json);
        $this->assertStringNotContainsString('example.test', $json);
        $this->assertArrayNotHasKey('name', $e->properties);
        $this->assertArrayNotHasKey('email', $e->properties);

        $this->post('/register', ['name' => 'Ada Okonkwo', 'email' => 'ada@example.test', 'password' => 'Longpass12345', 'password_confirmation' => 'Longpass12345', 'terms' => 1])->assertRedirect();
        $this->assertDatabaseHas('funnel_events', ['name' => 'account_created', 'user_id' => User::first()->id]);
    }

    public function test_application_events_mirror_into_the_funnel_with_a_hashed_application_number(): void
    {
        $this->seed(PlatformSeeder::class);
        $student = User::factory()->create();
        $this->actingAs($student)->post('/portal/start', ['intake_year' => 2028])->assertRedirect();
        $a = Application::first();

        $e = FunnelEvent::where('name', 'application_started')->first();
        $this->assertNotNull($e);
        $this->assertSame(\App\Support\Funnel::applicationHash($a), $e->application_hash, 'keyed (HMAC), so the sequential number cannot be recovered');
        $this->assertNotSame(hash('sha256', $a->application_number), $e->application_hash);
        $this->assertNull($e->tier, 'no service is chosen until the profile has been reviewed');
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

    public function test_journey_events_reach_ga4_from_the_server_only_with_consent_and_without_personal_data(): void
    {
        $this->seed(PlatformSeeder::class);
        Http::fake();
        config(['site.ga4_id' => 'G-TEST123', 'site.ga4_api_secret' => 'secret-x']);
        $student = User::factory()->create(['name' => 'Ada Okonkwo']);

        // no consent: the event is recorded first-party only
        $this->actingAs($student)->withUnencryptedCookies(['_ga' => 'GA1.1.123456.789'])->post('/portal/start', ['intake_year' => 2028])->assertRedirect();
        Http::assertNothingSent();

        // consent and a GA4 client id: one Measurement Protocol hit, with the anonymous client id and allowed parameters only
        Application::query()->delete();
        $this->actingAs($student)->withUnencryptedCookies(['smukn_consent' => 'granted', '_ga' => 'GA1.1.123456.789'])->post('/portal/start', ['intake_year' => 2028])->assertRedirect();
        Http::assertSent(function ($request) {
            $body = $request->data();
            $this->assertStringStartsWith('https://www.google-analytics.com/mp/collect?', $request->url());
            $this->assertSame('123456.789', $body['client_id']);
            $this->assertSame('application_started', $body['events'][0]['name']);
            $json = strtolower(json_encode($body));
            foreach (['okonkwo', 'smukn-', '@', 'actor'] as $never) {
                $this->assertStringNotContainsString($never, $json);
            }

            return true;
        });
        // the portal itself never loads a third-party script
        $this->actingAs($student)->get('/portal')->assertDontSee('googletagmanager');
    }

    public function test_ga4_parameters_are_an_allow_list_and_account_security_pages_never_load_analytics(): void
    {
        $params = (new \ReflectionMethod(Funnel::class, 'clientParams'))->invoke(null, [
            'step' => 'personal', 'step_title' => 'Personal details', 'title' => 'Passport scan.pdf', 'email' => 'a@b.test',
            'tier' => 'T2', 'intake_year' => 2028, 'amount' => 69500, 'actor' => 'student',
        ]);
        $this->assertSame(['step' => 'personal', 'tier' => 'T2', 'intake_year' => 2028], $params);
        // a step value that is not a form-section key (free text) is dropped
        $this->assertSame([], (new \ReflectionMethod(Funnel::class, 'clientParams'))->invoke(null, ['step' => 'Ada Okonkwo']));

        // the reset link carries a token and the email address in its URL: GA4 is never loaded there
        config(['site.ga4_id' => 'G-TEST123']);
        $this->get('/password/reset/abc123token?email=ada%40example.test')->assertOk()->assertDontSee('ga4-id', false);
        $this->get('/password/forgot')->assertOk()->assertDontSee('ga4-id', false);
        $this->get('/register')->assertOk()->assertSee('ga4-id', false);
    }
}
