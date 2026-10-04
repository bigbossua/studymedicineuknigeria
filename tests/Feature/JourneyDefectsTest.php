<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Application;
use App\Models\ServiceTier;
use App\Models\University;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Defects found by the browser journeys of 2026-10-04 (ops/qa/journey.cjs, ops/qa/staff-journey.cjs). */
class JourneyDefectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_throttled_route_counts_its_own_requests(): void
    {
        // An unprefixed throttle:N,M keys guests by IP only, so eligibility checks, logins and registrations from one
        // shared mobile-carrier address all drew on one counter.
        foreach (Route::getRoutes()->getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $m) {
                if (is_string($m) && str_starts_with($m, 'throttle:') && preg_match('/^throttle:\d+,\d+$/', $m)) {
                    $this->fail("{$route->uri()} uses {$m} without its own key prefix");
                }
            }
        }

        for ($i = 0; $i < 10; $i++) {
            $this->post('/apply-online/eligibility', []);
        }
        $this->post('/apply-online/eligibility', [])->assertStatus(429);
        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'Longpass12345', 'password_confirmation' => 'Longpass12345', 'terms' => '1'])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_a_document_nobody_uploaded_cannot_be_accepted_and_a_replaced_proposal_says_why_it_closed(): void
    {
        Notification::fake();
        $this->seed(PlatformSeeder::class);
        $student = User::factory()->create(['email_verified_at' => now()]);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($student)->post('/portal/start', ['service_tier_id' => ServiceTier::where('code', 'T2')->value('id'), 'intake_year' => 2028])->assertRedirect();
        $a = Application::firstOrFail();
        $asAdmin = fn () => $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id]);

        $doc = $a->documents()->firstOrFail();
        $asAdmin()->post("/admin/applications/{$a->application_number}/documents/{$doc->id}/review", ['decision' => 'accept'])->assertSessionHas('error');
        $this->assertNotSame(DocumentStatus::ACCEPTED, $doc->fresh()->status);
        $asAdmin()->post("/admin/applications/{$a->application_number}/documents/{$doc->id}/review", ['decision' => 'waive', 'reason' => 'Not needed for this route'])->assertSessionHas('status');
        $this->assertSame(DocumentStatus::NOT_REQUIRED, $doc->fresh()->status);

        $u = University::create(['slug' => 'testville', 'name' => 'University of Testville']);
        $asAdmin()->post("/admin/applications/{$a->application_number}/submissions", ['university_id' => $u->id, 'intake' => 'September 2028', 'route_code' => 'UCAS_STUDENT'])->assertSessionHas('status');
        $first = $a->submissions()->firstOrFail();
        $asAdmin()->post("/admin/applications/{$a->application_number}/submissions", ['university_id' => $u->id, 'intake' => 'September 2029', 'route_code' => 'UCAS_STUDENT'])->assertSessionHas('status');

        $this->assertSame('CLOSED', $first->fresh()->status);
        $closing = $first->events()->latest('id')->firstOrFail();
        $this->assertSame('CLOSED', $closing->to_status);
        $this->assertSame('Replaced by a new proposal', $closing->note);
        $this->actingAs($student)->get("/portal/{$a->application_number}/submissions")->assertOk()->assertSee('Replaced by a new proposal');
    }
}
