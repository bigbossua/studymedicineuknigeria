<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ReferenceFact;
use App\Models\Topic;
use App\Models\University;
use Database\Seeders\TopicFactsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** The verification worksheet round trip: export unverified facts, apply reviewer decisions by stable reference, never verify without a source and a date. */
class FactsWorksheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_then_import_applies_decisions_by_reference_and_refuses_unsourced_verification(): void
    {
        $u = University::create(['slug' => 'testville', 'name' => 'University of Testville', 'nation' => 'England', 'international_policy' => 'accepts']);
        $c = Course::create(['university_id' => $u->id, 'slug' => 'a100', 'title' => 'Medicine MBBS', 'entry_type' => 'standard']);
        $fee = $c->facts()->create(['key' => 'international_fee_gbp', 'value_number' => 50000, 'academic_year' => '2026/27', 'verification_status' => 'VERIFY-ON-PAGE', 'source_url' => 'https://example.ac.uk/fees', 'source_type' => 'official']);
        $waec = $u->facts()->create(['key' => 'waec_neco_statement', 'value_text' => 'WASSCE holders need a foundation year.', 'verification_status' => 'VERIFY-ON-PAGE', 'source_url' => 'https://example.ac.uk/nigeria', 'source_type' => 'official']);
        $noSource = $u->facts()->create(['key' => 'international_places', 'value_text' => '20', 'verification_status' => 'NOT_FOUND', 'source_type' => 'official']);
        $t = Topic::create(['slug' => 'ucat-2026', 'title' => 'UCAT 2026', 'cycle' => '2027']);
        $date = $t->facts()->create(['key' => 'booking_opens', 'value_text' => '23 June 2026', 'academic_year' => '2027', 'verification_status' => 'VERIFY-ON-PAGE', 'source_url' => 'https://www.ucat.ac.uk/ucat/dates-and-fees/', 'source_type' => 'official']);

        $file = 'storage/framework/testing/worksheet.csv';
        $this->artisan('smukn:facts-export', ['file' => $file])->expectsOutputToContain('4 fact(s) written')->assertSuccessful();
        $csv = File::get(base_path($file));
        $this->assertStringContainsString('course:testville/a100:international_fee_gbp:2026/27,1,"University of Testville · Medicine MBBS",international_fee_gbp,2026/27,50000,', $csv, 'numbers keep their digits and courses name their university');
        $this->assertStringContainsString('topic:ucat-2026:booking_opens:2027,1,', $csv, 'topic facts are priority 1');
        $this->assertStringContainsString('university:testville:waec_neco_statement,2,', $csv);

        // Reviewer fills in decisions: the fee changed on the page, the WAEC statement is confirmed, the places figure cannot be verified without a source, the date is confirmed.
        $decisions = "ref,decision,verified_value,new_source_url,reviewer_note,verified_on\n".
            "course:testville/a100:international_fee_gbp:2026/27,verified,52000,,\"Page shows £52,000 for 2026/27\",2026-10-04\n".
            "university:testville:waec_neco_statement,verified,,,,2026-10-04\n".
            "university:testville:international_places,verified,,,,2026-10-04\n".
            "topic:ucat-2026:booking_opens:2027,verified,,,,\n".
            "university:nowhere:waec_neco_statement,verified,,,,2026-10-04\n";
        $dec = 'storage/framework/testing/decisions.csv';
        File::put(base_path($dec), $decisions);
        $this->artisan('smukn:facts-import', ['file' => $dec, '--dry-run' => true])->expectsOutputToContain('[dry run] 2 fact(s) updated, 2 skipped, 1 unknown')->assertSuccessful();
        $this->assertSame('VERIFY-ON-PAGE', $fee->fresh()->verification_status, 'dry run saves nothing');

        $this->artisan('smukn:facts-import', ['file' => $dec])->expectsOutputToContain('2 fact(s) updated, 2 skipped, 1 unknown')->assertSuccessful();
        $fee->refresh();
        $this->assertSame(ReferenceFact::VERIFIED, $fee->verification_status);
        $this->assertSame(52000.0, (float) $fee->value_number);
        $this->assertSame('2026-10-04', $fee->verified_at->toDateString());
        $this->assertSame('2027-04-04', $fee->review_due_at->toDateString(), 'fees are re-checked after six months');
        $this->assertStringContainsString('[Review 2026-10-04: Page shows £52,000 for 2026/27]', $fee->notes);
        $this->assertSame(ReferenceFact::VERIFIED, $waec->fresh()->verification_status);
        $this->assertSame('NOT_FOUND', $noSource->fresh()->verification_status, 'no source URL: cannot be verified');
        $this->assertSame('VERIFY-ON-PAGE', $date->fresh()->verification_status, 'no verified_on date: cannot be verified');

        // Re-running the same decisions changes nothing further.
        $this->artisan('smukn:facts-import', ['file' => $dec])->expectsOutputToContain('Already applied')->assertSuccessful();
        File::delete([base_path($file), base_path($dec)]);
    }

    public function test_reseeding_topic_facts_never_overwrites_a_verified_value(): void
    {
        $this->seed(TopicFactsSeeder::class);
        $fact = Topic::where('slug', 'student-visa')->firstOrFail()->facts()->where('key', 'maintenance_london_monthly_gbp')->firstOrFail();
        $fact->update(['value_number' => 1529, 'verification_status' => ReferenceFact::VERIFIED, 'source_url' => 'https://www.gov.uk/student-visa/money', 'verified_at' => now()]);

        $this->seed(TopicFactsSeeder::class);

        $fact->refresh();
        $this->assertSame(ReferenceFact::VERIFIED, $fact->verification_status);
        $this->assertEquals(1529, $fact->value_number, 'a reviewer-verified value must survive a reseed');
    }

    public function test_the_prioritisation_explanation_appears_only_when_its_definition_is_publishable(): void
    {
        $this->seed(TopicFactsSeeder::class);
        $this->app['env'] = 'production';
        config(['site.publish_unverified' => false]);
        $html = $this->get('/working-in-the-uk')->assertOk()->getContent();
        $this->assertStringNotContainsString('The definition above decides', $html, 'an unverified legal definition must not be interpreted in production');
        $this->assertStringContainsString('treat priority as policy direction, not a promise', $html);

        Topic::where('slug', 'gmc-registration')->firstOrFail()->facts()->where('key', 'prioritisation_act')->update(['verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now()]);
        $this->assertStringContainsString('The definition above decides', $this->get('/working-in-the-uk')->getContent());
    }
}
