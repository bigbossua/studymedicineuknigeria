<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\ReferenceFact;
use App\Models\University;
use App\Models\User;
use App\Support\DecisionRegister;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression for 2026-10-29: schools published in Admin entered the sitemap as soon as their five core facts were
 * verified, although their decision-register rows were RESEARCH (no Nigerian demand evidence, no publication
 * decision). Factual verification, demand evidence and publication approval are now separate: a published school is
 * indexed only when its core facts are verified AND a live register row names its URL.
 */
class SchoolRegisterGateTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $slug, bool $published = true): University
    {
        $u = University::create(['slug' => $slug, 'name' => 'University '.$slug, 'nation' => 'England', 'international_policy' => 'accepts', 'published' => $published]);
        $course = $u->courses()->create(['slug' => 'medicine', 'title' => 'Medicine', 'entry_type' => 'standard']);
        foreach (University::CORE_FACTS as $key) {
            (in_array($key, ['international_fee_gbp', 'application_route'], true) ? $course : $u)->facts()->create(['key' => $key, 'value_text' => 'x', 'verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now(), 'source_type' => 'official']);
        }

        return $u;
    }

    public function test_the_register_decides_which_school_paths_are_approved(): void
    {
        DecisionRegister::flush();
        $this->assertTrue(DecisionRegister::isLive('/medical-schools/aberdeen'), 'D07 is PUBLISHED');
        $this->assertTrue(DecisionRegister::isLive('/medical-schools/buckingham'), 'D13 is PUBLISHED');
        $this->assertFalse(DecisionRegister::isLive('/medical-schools/cambridge'), 'D14 is RESEARCH with no URL');
        $this->assertFalse(DecisionRegister::isLive('/medical-schools/queens-belfast'));
        $this->assertFalse(DecisionRegister::isLive('/medical-schools/kings-college-london'));
        $this->assertFalse(DecisionRegister::isLive('/medical-schools/no-such-school'));
    }

    public function test_verified_facts_without_a_live_register_row_stay_noindex_and_out_of_the_sitemap(): void
    {
        foreach (['cambridge', 'queens-belfast', 'kings-college-london', 'aston', 'imperial'] as $slug) {
            $u = $this->school($slug);
            $this->assertFalse($u->isIndexable(), "$slug has verified facts but no live register row");
            $this->assertStringStartsWith('no live decision-register row', $u->indexBlocker());
            $this->get("/medical-schools/$slug")->assertOk()->assertSee('noindex', false); // the page stays online
        }
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/medical-schools/cambridge')->assertDontSee('/medical-schools/aston');
    }

    public function test_a_register_approved_school_needs_publication_and_verified_facts_too(): void
    {
        $u = $this->school('aberdeen');
        $this->assertTrue($u->isIndexable());
        $this->get('/sitemap.xml')->assertSee('/medical-schools/aberdeen');

        $u->update(['published' => false]);
        $this->assertSame('not published', $u->fresh()->indexBlocker());

        $u->update(['published' => true]);
        ReferenceFact::where('key', 'application_route')->update(['verification_status' => ReferenceFact::SOURCE_CHANGED]);
        $this->assertSame('core facts not verified: application_route', $u->fresh()->indexBlocker());
        $this->get('/sitemap.xml')->assertDontSee('/medical-schools/aberdeen');
    }

    public function test_admin_sees_why_a_published_school_is_not_indexed(): void
    {
        $this->school('cambridge');
        $this->school('aberdeen');
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();

        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])->get(route('admin.reference.universities'))->assertOk()
            ->assertSee('noindex: no live decision-register row: demand evidence and publication approval not recorded', false)
            ->assertSee('indexed');
    }
}
