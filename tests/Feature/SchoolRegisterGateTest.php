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
 * indexed only when its core facts are verified AND a live register row names its URL. The single other approval is
 * the owner's explicit exception of 2026-10-30 for Cambridge, King's College London and Queen's University Belfast,
 * recorded in their RESEARCH rows without claiming demand evidence; it must not open the index to any other school.
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
        foreach (['cambridge', 'queens-belfast', 'kings-college-london'] as $slug) {
            $this->assertFalse(DecisionRegister::isLive("/medical-schools/$slug"), "$slug stays RESEARCH: no demand evidence is claimed");
            $this->assertTrue(DecisionRegister::isOwnerException("/medical-schools/$slug"), "$slug is the owner's exception of 2026-10-30");
            $this->assertTrue(DecisionRegister::approves("/medical-schools/$slug"));
        }
        foreach (['aston', 'brunel', 'chester', 'imperial', 'keele', 'kent-medway', 'lancaster', 'surrey', 'swansea', 'no-such-school'] as $slug) {
            $this->assertFalse(DecisionRegister::approves("/medical-schools/$slug"), "$slug has no live row and no owner exception");
        }
    }

    public function test_the_owner_exception_covers_exactly_three_research_rows_and_claims_no_demand(): void
    {
        $rows = array_map('str_getcsv', file(base_path('data/seo/decision-register.csv')));
        $head = array_shift($rows);
        $exceptions = collect($rows)->map(fn ($r) => array_combine($head, $r))
            ->filter(fn ($r) => str_starts_with($r['indexation_decision'], DecisionRegister::OWNER_EXCEPTION))->keyBy('id');

        // a new exception needs an explicit owner decision recorded in ops/seo/build-register.py and here
        $this->assertSame(['D14', 'D27', 'D34'], $exceptions->keys()->sort()->values()->all());
        $urls = ['D14' => '/medical-schools/cambridge', 'D27' => '/medical-schools/kings-college-london', 'D34' => '/medical-schools/queens-belfast'];
        foreach ($exceptions as $id => $r) {
            $this->assertSame($urls[$id], $r['current_url']);
            $this->assertSame('RESEARCH', $r['status'], "$id must not be marked as having demand evidence");
            $this->assertSame('2026-10-30', $r['decided_on']);
            $this->assertStringContainsString('OWNER EXCEPTION 2026-10-30', $r['status_reason']);
            $this->assertStringContainsString('Nigerian search demand is NOT verified', $r['status_reason']);
            $this->assertStringStartsWith('DATA UNAVAILABLE', $r['volume'], "$id must not carry an invented search volume");
        }
    }

    public function test_the_three_owner_exceptions_stay_indexed_with_their_canonical_and_in_the_sitemap(): void
    {
        foreach (['cambridge', 'kings-college-london', 'queens-belfast'] as $slug) {
            $u = $this->school($slug);
            $this->assertTrue($u->isIndexable(), "$slug: published, core facts verified and an owner exception");
            $this->get("/medical-schools/$slug")->assertOk()
                ->assertSee('<meta name="robots" content="index, follow', false)
                ->assertDontSee('noindex', false)
                ->assertSee('<link rel="canonical" href="'.route('schools.show', $u).'">', false);
        }
        $sitemap = $this->get('/sitemap.xml')->assertOk();
        foreach (['cambridge', 'kings-college-london', 'queens-belfast'] as $slug) {
            $sitemap->assertSee('<loc>'.route('schools.show', $slug).'</loc>', false);
        }
    }

    public function test_the_owner_exception_still_needs_publication_and_verified_facts(): void
    {
        $u = $this->school('cambridge');
        $u->update(['published' => false]);
        $this->assertSame('not published', $u->fresh()->indexBlocker());
        $u->update(['published' => true]);
        ReferenceFact::where('key', 'english_requirement')->update(['verification_status' => ReferenceFact::SOURCE_CHANGED]);
        $this->assertSame('core facts not verified: english_requirement', $u->fresh()->indexBlocker());
        $this->get('/sitemap.xml')->assertDontSee('/medical-schools/cambridge');
    }

    public function test_verified_facts_without_a_live_register_row_stay_noindex_and_out_of_the_sitemap(): void
    {
        foreach (['aston', 'brunel', 'chester', 'imperial', 'keele', 'kent-medway', 'lancaster', 'surrey', 'swansea'] as $slug) {
            $u = $this->school($slug);
            $this->assertFalse($u->isIndexable(), "$slug has verified facts but no live register row");
            $this->assertStringStartsWith('no live decision-register row', $u->indexBlocker());
            $this->get("/medical-schools/$slug")->assertOk()->assertSee('noindex', false); // the page stays online
        }
        $sitemap = $this->get('/sitemap.xml')->assertOk();
        foreach (['aston', 'brunel', 'chester', 'imperial', 'keele', 'kent-medway', 'lancaster', 'surrey', 'swansea'] as $slug) {
            $sitemap->assertDontSee("/medical-schools/$slug");
        }
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
        $this->school('aston');
        $this->school('aberdeen');
        $this->school('cambridge');
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();

        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])->get(route('admin.reference.universities'))->assertOk()
            ->assertSee('noindex: no live decision-register row: demand evidence and publication approval not recorded', false)
            ->assertSee('indexed (owner-approved exception; demand not yet evidenced)')
            ->assertSee('indexed');
    }
}
