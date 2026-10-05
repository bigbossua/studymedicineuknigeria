<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ReferenceFact;
use App\Models\Topic;
use App\Models\University;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pound amounts read on an official page keep their pence and are stored as numbers, even on a fact recorded empty. */
class FactAmountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pence_are_shown_and_whole_pounds_are_not_padded(): void
    {
        $this->assertSame('£34.50', (new ReferenceFact(['key' => 'application_fee_gbp', 'value_number' => 34.5]))->displayValue());
        $this->assertSame('£1,529', (new ReferenceFact(['key' => 'maintenance_london_monthly_gbp', 'value_number' => 1529]))->displayValue());
    }

    public function test_a_verified_amount_on_an_empty_fact_is_stored_as_a_number(): void
    {
        $topic = Topic::create(['slug' => 'student-visa', 'title' => 'Student visa']);
        $fact = ReferenceFact::create(['subject_type' => Topic::class, 'subject_id' => $topic->id, 'key' => 'application_fee_gbp', 'academic_year' => '2026',
            'source_url' => 'https://www.gov.uk/student-visa', 'verification_status' => ReferenceFact::NOT_FOUND]);
        @mkdir(storage_path('framework/testing'), 0777, true);
        $csv = storage_path('framework/testing/decisions-amounts.csv'); // the importer only reads files inside the project
        file_put_contents($csv, "ref,decision,verified_value,new_source_url,reviewer_note,verified_on\ntopic:student-visa:application_fee_gbp:2026,verified,558,,quote,2026-10-05\n");
        $this->artisan('smukn:facts-import', ['file' => 'storage/framework/testing/decisions-amounts.csv'])->assertSuccessful();
        @unlink($csv);
        $fact->refresh();
        $this->assertSame(ReferenceFact::VERIFIED, $fact->verification_status);
        $this->assertNull($fact->value_text);
        $this->assertSame('£558', $fact->displayValue());
    }

    public function test_the_fee_table_hides_an_unverified_clinical_years_answer_in_production(): void
    {
        $u = University::create(['slug' => 'testville', 'name' => 'University of Testville', 'nation' => 'England', 'international_policy' => 'accepts']);
        $c = Course::create(['university_id' => $u->id, 'slug' => 'a100', 'title' => 'Medicine MBBS', 'entry_type' => 'standard']);
        $c->facts()->create(['key' => 'international_fee_gbp', 'value_number' => 48000, 'academic_year' => '2026/27', 'verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now(), 'source_url' => 'https://example.ac.uk/fees', 'source_type' => 'official']);
        $clinical = $c->facts()->create(['key' => 'clinical_years_fee_differs', 'value_bool' => false, 'academic_year' => '2026/27', 'verification_status' => ReferenceFact::VERIFY_ON_PAGE, 'source_url' => 'https://example.ac.uk/fees', 'source_type' => 'official']);
        $this->app['env'] = 'production';
        config(['site.publish_unverified' => false]);
        $row = fn () => preg_match('#Testville.*?</tr>#s', $this->get('/fees')->assertOk()->getContent(), $m) ? $m[0] : '';
        $this->assertStringContainsString('£48,000', $row());
        $this->assertStringNotContainsString('>No<', $row(), 'an unverified clinical-years answer must not be shown');
        $clinical->update(['verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now()]);
        $this->assertStringContainsString('>No<', $row());
    }
}
