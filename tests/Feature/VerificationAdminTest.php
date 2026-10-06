<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\ReferenceFact;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\TopicFactsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VerificationAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'staff', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();

        return $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id]);
    }

    public function test_verify_by_source_groups_pending_facts_and_bulk_verifies_them(): void
    {
        $this->seed(TopicFactsSeeder::class);
        $url = 'https://www.gov.uk/student-visa';
        $ids = ReferenceFact::where('source_url', $url)->where('verification_status', ReferenceFact::VERIFY_ON_PAGE)->pluck('id');
        $this->assertGreaterThan(1, $ids->count());

        $this->admin()->get('/admin/verification/by-source')->assertOk()->assertSee($url)->assertSee('Selected facts: verified on this page');

        $this->admin()->post('/admin/verification/bulk', ['decision' => 'verify', 'fact_ids' => $ids->all()])->assertSessionHas('status');
        $verified = ReferenceFact::whereIn('id', $ids)->get();
        $this->assertTrue($verified->every(fn ($f) => $f->verification_status === ReferenceFact::VERIFIED && $f->verified_at !== null && $f->review_due_at !== null));
        $this->assertDatabaseHas('admin_actions', ['action' => 'fact.bulk_verify']);
        $this->assertStringNotContainsString($url.'</span>', $this->admin()->get('/admin/verification/by-source')->getContent());
    }

    public function test_bulk_verify_skips_facts_without_a_source_and_students_are_refused(): void
    {
        $this->seed(TopicFactsSeeder::class);
        $fact = ReferenceFact::where('verification_status', ReferenceFact::VERIFY_ON_PAGE)->first();
        $fact->forceFill(['source_url' => null])->save();

        $this->admin()->post('/admin/verification/bulk', ['decision' => 'verify', 'fact_ids' => [$fact->id]])->assertSessionHas('status', '0 fact(s) marked verified; 1 skipped (no source URL, or the page changed: confirm those one at a time with the current wording).');
        $this->assertSame(ReferenceFact::VERIFY_ON_PAGE, $fact->fresh()->verification_status);

        $this->actingAs(User::factory()->create())->post('/admin/verification/bulk', ['decision' => 'verify', 'fact_ids' => [$fact->id]])->assertForbidden();
    }

    public function test_a_changed_source_is_never_bulk_verified_and_pages_follow_the_priority_order(): void
    {
        $this->seed(TopicFactsSeeder::class);
        // a fact whose official page changed (the visa maintenance figures were one until 2026-10-05)
        $changed = ReferenceFact::where('key', 'maintenance_london_monthly_gbp')->firstOrFail();
        $changed->forceFill(['verification_status' => ReferenceFact::SOURCE_CHANGED])->save();
        $this->admin()->post('/admin/verification/bulk', ['decision' => 'verify', 'fact_ids' => [$changed->id]])->assertSessionHas('status');
        $this->assertSame(ReferenceFact::SOURCE_CHANGED, $changed->fresh()->verification_status, 'the old value must be confirmed alone with the current wording');

        $html = $this->admin()->get('/admin/verification/by-source')->assertOk()->assertSee('Confirm alone')->getContent();
        $this->assertLessThan(strpos($html, 'P2 · UCAT dates and rules'), strpos($html, 'P1 · UCAS Medicine deadlines'), 'UCAS Medicine deadlines come first');
        $this->assertLessThan(strpos($html, 'P7 · Visa and immigration'), strpos($html, 'P3 · GMC status and registration'));
    }

    public function test_admin_dashboard_shows_the_launch_checklist_with_live_state(): void
    {
        $this->seed(PlatformSeeder::class);
        $this->seed(TopicFactsSeeder::class);
        $r = $this->admin()->get('/admin')->assertOk()->assertSee('Launch readiness')->assertSee('Reference facts verified')->assertSee('3 price(s) set', false)->assertSee('STRIPE_SECRET not set');
        $this->assertMatchesRegularExpression('/\d+ of 11 complete/', $r->getContent());
        // the scheduler item tells the truth: no heartbeat yet means the cron job has never run
        $r->assertSee('Scheduler and email queue running')->assertSee('scheduler has never run');
        Cache::forever('scheduler.heartbeat', now()->timestamp);
        $this->admin()->get('/admin')->assertSee('scheduler last ran 0 min ago');
        // recording the first-party analytics choice completes that item; a non-ID SITE_GA4_ID is never treated as GA4
        config(['site.analytics_decision' => 'first_party']);
        $this->admin()->get('/admin')->assertSee('first-party funnel only (owner decision recorded)');
    }

    public function test_redirects_are_admin_only_relative_and_never_over_private_paths(): void
    {
        $this->admin()->post('/admin/redirects', ['from_path' => '/old', 'to_path' => '/fees'])->assertForbidden(); // helper creates staff, not admin

        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $as = fn () => $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id]);
        $as()->post('/admin/redirects', ['from_path' => '/old', 'to_path' => 'https://evil.example/login'])->assertSessionHasErrors('to_path');
        $as()->post('/admin/redirects', ['from_path' => '/old', 'to_path' => '//evil.example'])->assertSessionHasErrors('to_path');
        $as()->post('/admin/redirects', ['from_path' => '/login', 'to_path' => '/fees'])->assertSessionHasErrors('from_path');
        $as()->post('/admin/redirects', ['from_path' => '/old-fees', 'to_path' => '/fees'])->assertSessionHas('status');
        $this->get('/old-fees')->assertRedirect('/fees');
    }
}
