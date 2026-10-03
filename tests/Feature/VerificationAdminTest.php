<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\ReferenceFact;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\TopicFactsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->admin()->post('/admin/verification/bulk', ['decision' => 'verify', 'fact_ids' => [$fact->id]])->assertSessionHas('status', '0 fact(s) marked verified; 1 skipped (no source URL).');
        $this->assertSame(ReferenceFact::VERIFY_ON_PAGE, $fact->fresh()->verification_status);

        $this->actingAs(User::factory()->create())->post('/admin/verification/bulk', ['decision' => 'verify', 'fact_ids' => [$fact->id]])->assertForbidden();
    }
}
