<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\StaffNotification;
use App\Support\LeadReplyDraft;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Regression for 2026-10-29: three eligibility leads stayed "new" for nine days although the Contact page promises a
 * reply within two working days, and nothing in Admin showed they were late. A new lead past that promise is now
 * marked overdue on the dashboard and in Admin -> Leads, and setting it to "contacted" clears the mark.
 */
class LeadFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function lead(string $created, string $status = 'new'): Lead
    {
        $lead = Lead::create(['email' => 'lead-'.uniqid().'@example.test', 'name' => 'Test Lead', 'status' => $status, 'eligibility_result' => ['summary' => 'Foundation route first']]);
        $lead->forceFill(['created_at' => Carbon::parse($created)])->save();

        return $lead->fresh();
    }

    private function asAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession([EnsureTwoFactor::SESSION_KEY => $this->admin->id]);
    }

    public function test_working_days_skip_the_weekend(): void
    {
        $friday = $this->lead('2026-10-23 16:00');                   // Friday
        $this->assertSame(1, $friday->workingDaysWaiting(Carbon::parse('2026-10-26 09:00'))); // Monday
        $this->assertSame(2, $friday->workingDaysWaiting(Carbon::parse('2026-10-27 09:00'))); // Tuesday
        $this->assertFalse($friday->isOverdue(Carbon::parse('2026-10-27 18:00')), 'two working days is still within the promise');
        $this->assertTrue($friday->isOverdue(Carbon::parse('2026-10-28 09:00')), 'the third working day is late');
    }

    public function test_only_new_leads_can_be_overdue(): void
    {
        Carbon::setTestNow('2026-10-29 12:00');
        $late = $this->lead('2026-10-20 10:00');
        $contacted = $this->lead('2026-10-20 10:00', 'contacted');
        $fresh = $this->lead('2026-10-28 10:00');

        $this->assertTrue($late->isOverdue());
        $this->assertFalse($contacted->isOverdue());
        $this->assertFalse($fresh->isOverdue());
        $this->assertSame([$late->id], Lead::overdue()->modelKeys());
    }

    public function test_admin_sees_overdue_leads_and_contacting_one_clears_it(): void
    {
        Carbon::setTestNow('2026-10-29 12:00');
        $late = $this->lead('2026-10-20 10:00');
        $this->lead('2026-10-28 10:00');

        $this->asAdmin()->get(route('admin.dashboard'))->assertOk()->assertSee('Leads overdue (reply promised within 2 working days)');
        $this->asAdmin()->get(route('admin.leads'))->assertOk()
            ->assertSee('1 lead still marked new after more than 2 working days')
            ->assertSee('Overdue: 7 working days');
        $this->asAdmin()->get(route('admin.leads', ['overdue' => 1]))->assertOk()->assertSee($late->email)->assertSee('Overdue: 7 working days');

        $this->asAdmin()->post(route('admin.leads.status', $late), ['status' => 'contacted'])->assertRedirect();
        $this->assertSame('contacted', $late->fresh()->status);
        $this->asAdmin()->get(route('admin.leads'))->assertOk()->assertDontSee('still marked new after')->assertDontSee('Overdue:');
    }

    public function test_an_unknown_lead_status_is_refused(): void
    {
        $lead = $this->lead('2026-10-28 10:00');
        $this->asAdmin()->post(route('admin.leads.status', $lead), ['status' => 'admitted'])->assertSessionHasErrors('status');
        $this->assertSame('new', $lead->fresh()->status);
    }

    public function test_the_eligibility_check_creates_a_new_lead_and_alerts_the_admin(): void
    {
        Notification::fake();
        $this->post(route('apply.eligibility.submit'), [
            'qualification' => 'waec_only', 'sciences' => 'yes', 'english' => 'waec_english', 'ucat' => 'none', 'intake_year' => (int) date('Y') + 1,
            'name' => 'Test Lead', 'email' => 'new-lead@example.test', 'consent' => '1',
        ])->assertRedirect(route('apply.eligibility'));

        $lead = Lead::where('email', 'new-lead@example.test')->firstOrFail();
        $this->assertSame('new', $lead->status);
        $this->assertFalse($lead->isOverdue());
        Notification::assertSentTo($this->admin, StaffNotification::class);
    }

    /** Workstream 5 (2026-10-30): staff get a reply draft built from the lead's own answers; the site sends nothing. */
    public function test_an_overdue_lead_gets_a_reply_draft_from_its_own_answers_and_nothing_is_sent(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-10-30 10:00');
        $this->post(route('apply.eligibility.submit'), [
            'qualification' => 'waec_only', 'sciences' => 'yes', 'english' => 'waec_english', 'ucat' => 'none', 'intake_year' => 2028,
            'name' => 'Ada Example', 'email' => 'ada@example.test', 'consent' => '1',
        ]);
        $lead = Lead::where('email', 'ada@example.test')->firstOrFail();
        Carbon::setTestNow('2026-11-06 10:00'); // five working days later
        Notification::fake();

        $draft = LeadReplyDraft::for($lead->fresh());
        $this->assertStringStartsWith('Dear Ada,', $draft['body']);
        $this->assertStringContainsString('30 October 2026', $draft['body']);
        $this->assertStringContainsString('longer than the two working days', $draft['body']);
        $this->assertStringContainsString('- Your qualifications: WAEC or NECO (WASSCE) only', $draft['body']);
        $this->assertStringContainsString('Foundation year leading to Medicine (possible)', $draft['body'], 'the routes are the ones the checker already showed');
        $this->assertStringContainsString(route('requirements.waec'), $draft['body']);
        $this->assertStringContainsString('We cannot guarantee an offer', $draft['body']);
        $this->assertStringNotContainsString('ada@example.test', $draft['body']);

        $this->asAdmin()->get(route('admin.leads'))->assertOk()->assertSee('Draft reply (review and send yourself)')->assertSee('Dear Ada,')->assertSee('mailto:ada%40example.test?subject=', false);
        Notification::assertNothingSent();
    }
}
