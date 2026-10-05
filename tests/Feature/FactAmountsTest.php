<?php

namespace Tests\Feature;

use App\Models\ReferenceFact;
use App\Models\Topic;
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
}
