<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ReferenceFact;
use App\Models\ServiceTier;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ScheduledCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_checkout_sessions_expire_after_a_day(): void
    {
        $this->seed(PlatformSeeder::class);
        $user = User::factory()->create();
        $tier = ServiceTier::where('code', 'T1')->first();
        $this->actingAs($user)->post('/portal/start', ['service_tier_id' => $tier->id, 'intake_year' => 2028]);
        $a = Application::first();
        $old = $a->payments()->create(['status' => 'INITIATED', 'amount_minor' => 1000, 'method' => 'STRIPE']);
        $old->forceFill(['created_at' => now()->subHours(30)])->save();
        $fresh = $a->payments()->create(['status' => 'INITIATED', 'amount_minor' => 1000, 'method' => 'STRIPE']);
        $paid = $a->payments()->create(['status' => 'SUCCEEDED', 'amount_minor' => 1000, 'method' => 'STRIPE']);
        $paid->forceFill(['created_at' => now()->subDays(3)])->save();

        $this->artisan('smukn:expire-payments')->expectsOutputToContain('1 payment(s) expired')->assertSuccessful();
        $this->assertSame(['EXPIRED', 'INITIATED', 'SUCCEEDED'], [$old->fresh()->status, $fresh->fresh()->status, $paid->fresh()->status]);
    }

    public function test_verified_facts_past_their_review_date_are_flagged(): void
    {
        $topic = Topic::create(['slug' => 't', 'title' => 'T', 'cycle' => '2027']);
        $due = $topic->facts()->create(['key' => 'fee_gbp', 'value_number' => 1, 'source_url' => 'https://example.gov.uk/a', 'verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now()->subMonths(7), 'review_due_at' => now()->subDay()]);
        $ok = $topic->facts()->create(['key' => 'other', 'value_text' => 'x', 'source_url' => 'https://example.gov.uk/b', 'verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now(), 'review_due_at' => now()->addMonths(6)]);

        $this->artisan('smukn:flag-review-due')->expectsOutputToContain('1 fact(s) flagged')->assertSuccessful();
        $this->assertSame(ReferenceFact::REVIEW_DUE, $due->fresh()->verification_status);
        $this->assertSame(ReferenceFact::VERIFIED, $ok->fresh()->verification_status);
        $this->assertFalse($due->fresh()->isPublishable() && app()->isProduction());
    }

    public function test_inactivity_reminders_follow_the_cadence_and_never_repeat(): void
    {
        $this->seed(PlatformSeeder::class);
        Notification::fake();
        $user = User::factory()->create();
        $tier = ServiceTier::where('code', 'T1')->first();
        $this->actingAs($user)->post('/portal/start', ['service_tier_id' => $tier->id, 'intake_year' => 2028]);
        $a = Application::first();

        $this->artisan('smukn:reminders')->expectsOutputToContain('0 reminder(s) sent')->assertSuccessful();

        $a->forceFill(['last_activity_at' => now()->subDays(2)])->saveQuietly();
        $this->artisan('smukn:reminders', ['--dry' => true])->expectsOutputToContain('1 reminder(s) would be sent')->assertSuccessful();
        $this->assertDatabaseCount('reminders', 0);

        $this->artisan('smukn:reminders')->expectsOutputToContain('1 reminder(s) sent')->assertSuccessful();
        Notification::assertSentTo($user, ApplicationNotification::class, fn ($n) => $n->type === 'reminder' && str_contains($n->data['next'], 'Next step'));
        $this->assertDatabaseHas('reminders', ['application_id' => $a->id, 'type' => 'incomplete:2']);

        // same day again: nothing (48-hour cap and the same cadence point)
        $this->artisan('smukn:reminders')->expectsOutputToContain('0 reminder(s) sent');

        // a withdrawn application is never reminded
        $a->forceFill(['last_activity_at' => now()->subDays(7), 'withdrawn_at' => now()])->saveQuietly();
        $a->reminders()->update(['sent_at' => now()->subDays(3)]);
        $this->artisan('smukn:reminders')->expectsOutputToContain('0 reminder(s) sent');
    }
}
