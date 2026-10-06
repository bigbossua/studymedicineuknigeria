<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\ChecklistRule;
use App\Models\Course;
use App\Models\Profession;
use App\Models\ReferenceFact;
use App\Models\ServiceTier;
use App\Models\Topic;
use App\Models\University;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Reference data can be re-synced on every deploy without overwriting a reviewer's work or an owner's prices. */
class FactIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_sync_creates_everything_a_first_deploy_needs_and_never_demo_accounts(): void
    {
        $this->artisan('smukn:reference-sync')->assertSuccessful();
        $this->assertGreaterThan(0, University::count());
        $this->assertGreaterThan(0, ServiceTier::count(), 'students cannot start an application without service tiers');
        $this->assertGreaterThan(0, ChecklistRule::count());
        $this->assertNotNull(Topic::bySlug('ucas-2027'));
        $this->assertGreaterThan(0, Profession::count());
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }

    public function test_re_syncing_never_overwrites_reviewed_facts_or_owner_prices(): void
    {
        $this->artisan('smukn:reference-sync')->assertSuccessful();
        $deadline = Topic::bySlug('ucas-2027')->fact('deadline_medicine');
        $deadline->update(['value_text' => 'Reviewer wording from the UCAS page', 'verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now(), 'reviewed_at' => now()]);
        $archived = ReferenceFact::where('subject_type', University::class)->where('verification_status', ReferenceFact::VERIFY_ON_PAGE)->firstOrFail();
        $archived->update(['verification_status' => ReferenceFact::ARCHIVED, 'reviewed_at' => now()]);
        $price = ServiceTier::firstOrFail()->prices()->firstOrFail();
        $price->update(['amount_minor' => 45000]);

        $this->artisan('smukn:reference-sync')->assertSuccessful();

        $this->assertSame('Reviewer wording from the UCAS page', $deadline->fresh()->value_text);
        $this->assertSame(ReferenceFact::VERIFIED, $deadline->fresh()->verification_status);
        $this->assertSame(ReferenceFact::ARCHIVED, $archived->fresh()->verification_status, 'a reviewer decision is never reversed by a sync');
        $this->assertSame(45000, $price->fresh()->amount_minor);
    }

    public function test_a_graduate_entry_only_school_keeps_one_course_and_its_reviewed_facts(): void
    {
        $this->artisan('smukn:reference-sync')->assertSuccessful();
        $swansea = University::where('slug', 'swansea')->firstOrFail();
        $this->assertSame(['graduate-entry'], $swansea->courses()->medicine()->pluck('slug')->all(), 'the schools row and the fee row describe the same A101 course');
        $this->assertSame('graduate', $swansea->primaryCourse()->entry_type);

        // a database from before the merge: a 'standard' placeholder holding a reviewed fact
        $placeholder = $swansea->courses()->create(['slug' => 'medicine', 'title' => 'Medicine', 'entry_type' => 'standard']);
        $route = $swansea->courses()->where('slug', 'graduate-entry')->firstOrFail()->facts()->where('key', 'application_route')->firstOrFail();
        $route->forceFill(['subject_id' => $placeholder->id, 'verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now(), 'reviewed_at' => now()])->save();

        $this->artisan('smukn:reference-sync')->assertSuccessful();

        $this->assertNull($placeholder->fresh(), 'the placeholder is merged away');
        $moved = $route->fresh();
        $this->assertSame(ReferenceFact::VERIFIED, $moved->verification_status, 'a reviewed fact keeps its status when it moves');
        $this->assertSame('graduate-entry', Course::find($moved->subject_id)->slug);
    }

    public function test_reviewer_decisions_mark_the_fact_reviewed(): void
    {
        $this->artisan('smukn:reference-sync')->assertSuccessful();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $fact = ReferenceFact::where('verification_status', ReferenceFact::VERIFY_ON_PAGE)->whereNotNull('source_url')->firstOrFail();

        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])->post(route('admin.reference.update', $fact), ['decision' => 'source_changed'])->assertSessionHas('status');
        $this->assertNotNull($fact->fresh()->reviewed_at);
    }

    public function test_editing_a_verified_value_makes_it_unverified_until_verified_again(): void
    {
        $this->artisan('smukn:reference-sync')->assertSuccessful();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $fact = Topic::bySlug('ucas-2027')->fact('deadline_medicine');
        $fact->update(['verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now()]);

        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])
            ->post(route('admin.reference.update', $fact), ['decision' => 'save', 'value_text' => '16 October 2026, 18:00 (UK time)'])->assertSessionHas('status');
        $this->assertSame(ReferenceFact::VERIFY_ON_PAGE, $fact->fresh()->verification_status, 'a changed value is not verified');
        $this->assertFalse($fact->fresh()->isPublishable() && app()->isProduction());
    }
}
