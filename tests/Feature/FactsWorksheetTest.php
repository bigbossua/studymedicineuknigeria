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
        $this->assertMatchesRegularExpression('#course:testville/a100:international_fee_gbp:2026/27,6,"International fees and costs",S\\d{3},1,"University of Testville · Medicine MBBS",international_fee_gbp,2026/27,50000,#u', $csv, 'numbers keep their digits and courses name their university');
        $this->assertStringContainsString('topic:ucat-2026:booking_opens:2027,2,"UCAT dates and rules",', $csv, 'UCAT facts are priority 2, after UCAS Medicine deadlines');
        $this->assertStringContainsString('university:testville:waec_neco_statement,5,"Nigerian qualifications and entry",', $csv);

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

    public function test_the_worksheet_groups_facts_by_official_page_in_the_owners_priority_order(): void
    {
        $u = University::create(['slug' => 'testville', 'name' => 'University of Testville', 'nation' => 'England', 'international_policy' => 'accepts']);
        $c = Course::create(['university_id' => $u->id, 'slug' => 'a100', 'title' => 'Medicine MBBS', 'entry_type' => 'standard']);
        $page = 'https://example.ac.uk/medicine/international';
        $c->facts()->create(['key' => 'ucas_code', 'value_text' => 'A100', 'verification_status' => 'VERIFY-ON-PAGE', 'source_url' => $page, 'source_type' => 'official']);
        $u->facts()->create(['key' => 'international_accepted', 'value_text' => 'Yes', 'verification_status' => 'VERIFY-ON-PAGE', 'source_url' => $page, 'source_type' => 'official']);
        $other = $u->facts()->create(['key' => 'a_level_requirement', 'value_text' => 'AAA', 'verification_status' => 'SOURCE_CHANGED', 'source_url' => 'https://example.ac.uk/other', 'source_type' => 'official']);
        $ucas = Topic::create(['slug' => 'ucas-2027', 'title' => 'UCAS', 'cycle' => '2027']);
        $ucas->facts()->create(['key' => 'deadline_medicine', 'value_text' => '15 October 2026', 'verification_status' => 'VERIFY-ON-PAGE', 'source_url' => 'https://www.ucas.com/dates', 'source_type' => 'official']);

        $file = 'storage/framework/testing/ws-'.uniqid().'.csv';
        $sources = 'storage/framework/testing/src-'.uniqid().'.csv';
        $this->artisan('smukn:facts-export', ['file' => $file, '--sources' => $sources])->expectsOutputToContain('3 official source(s)')->assertSuccessful();
        $rows = array_map('str_getcsv', file(base_path($file)));
        $head = array_shift($rows);
        $rows = array_map(fn ($r) => array_combine($head, $r), $rows);

        $this->assertSame('topic:ucas-2027:deadline_medicine', $rows[0]['ref'], 'UCAS Medicine deadlines come first');
        $this->assertSame('1', $rows[0]['priority']);
        // the two facts on one page sit together, placed at the page's best priority (eligibility, 4), and the course
        // code from the same page rides along even though on its own it would be priority 10
        $this->assertSame([$rows[1]['source_group'], '2'], [$rows[2]['source_group'], $rows[1]['facts_on_source']]);
        $this->assertSame('https://example.ac.uk/medicine/international', $rows[1]['source_url']);
        $this->assertSame('SOURCE_CHANGED', $rows[3]['status'], 'a changed source is in the sheet and labelled');
        $this->assertSame('Nigerian qualifications and entry', $rows[3]['priority_area']);
        $summary = array_map('str_getcsv', file(base_path($sources)));
        $this->assertCount(4, $summary, 'one header and one row per official page');
        File::delete([base_path($file), base_path($sources)]);
        $this->assertNotNull($other);
    }
}
