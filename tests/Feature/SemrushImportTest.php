<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SemrushImportTest extends TestCase
{
    public function test_recorded_lookups_replace_the_unavailable_placeholders_without_inventing_anything(): void
    {
        $doc = storage_path('framework/testing/demand.md');
        $csv = storage_path('framework/testing/lookups.csv');
        File::ensureDirectoryExists(dirname($doc));
        File::put($doc, "| # | Query theme | Intent | Nigerian | SERP | Volume | Difficulty | Notes |\n".
            "| 1 | study medicine in UK from Nigeria | Informational | Yes | Weak | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | - |\n".
            "| 2 | NECO medicine UK | Informational | Yes | Very weak | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | - |\n");
        File::put($csv, "id,query_theme,intent_hypothesis,semrush_keyword_used,database,volume,keyword_difficulty,cpc,intent_semrush,top_3_urls,trend_note,looked_up_on\n".
            "1,study medicine in UK from Nigeria,Informational,study medicine in uk from nigeria,ng,320,22,,informational,https://a.example https://b.example,peaks Sep,2026-10-03\n".
            "2,NECO medicine UK,Informational,,ng,,,,,,,\n");

        $this->artisan('smukn:semrush-import', ['file' => str_replace(base_path().'/', '', $csv), '--doc' => str_replace(base_path().'/', '', $doc)])
            ->expectsOutputToContain('1 row(s) updated')->expectsOutputToContain('#1 study medicine in UK from Nigeria (vol 320)')->assertSuccessful();

        $out = File::get($doc);
        $this->assertStringContainsString('| 1 | study medicine in UK from Nigeria | Informational | Yes | Weak | Semrush NG 2026-10-03: vol 320, KD 22 ("study medicine in uk from nigeria") | Top: https://a.example https://b.example |', $out);
        $this->assertStringContainsString('| 2 | NECO medicine UK | Informational | Yes | Very weak | DATA UNAVAILABLE', $out, 'a row without a lookup date stays unavailable');
        File::delete([$doc, $csv]);
    }
}
