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
        File::put($csv, "id,query_theme,intent_hypothesis,semrush_keyword_used,database,volume,keyword_difficulty,cpc,intent_semrush,top_3_urls,trend_note,looked_up_on,register_ids\n".
            "1,study medicine in UK from Nigeria,Informational,study medicine in uk from nigeria,ng,320,22,,informational,https://a.example https://b.example,peaks Sep,2026-10-03,C01;C02\n".
            "2,NECO medicine UK,Informational,,ng,,,,,,,,G01\n");
        $register = storage_path('framework/testing/register.csv');
        File::put($register, "id,query_family,volume,keyword_difficulty,sources,status\n".
            "C01,core,DATA UNAVAILABLE (pending),DATA UNAVAILABLE (pending),02 §A,PUBLISHED\n".
            "C02,list,DATA UNAVAILABLE (pending),DATA UNAVAILABLE (pending),02 §A q4,PUBLISHED\n".
            "G01,neco,DATA UNAVAILABLE (pending),DATA UNAVAILABLE (pending),02 §A q7,UPDATE\n");

        $this->artisan('smukn:semrush-import', ['file' => str_replace(base_path().'/', '', $csv), '--doc' => str_replace(base_path().'/', '', $doc), '--register' => str_replace(base_path().'/', '', $register)])
            ->expectsOutputToContain('1 row(s) updated')->expectsOutputToContain('#1 study medicine in UK from Nigeria (vol 320)')->expectsOutputToContain('2 decision-register row(s) updated')->assertSuccessful();

        $reg = File::get($register);
        $this->assertStringContainsString('C01,core,320,22,"02 §A · Semrush NG 2026-10-03 (""study medicine in uk from nigeria"")",PUBLISHED', $reg);
        $this->assertStringContainsString('C02,list,320,22,', $reg);
        $this->assertMatchesRegularExpression('/^G01,neco,"?DATA UNAVAILABLE \(pending\)"?,"?DATA UNAVAILABLE \(pending\)"?,"?02 §A q7"?,UPDATE$/mu', $reg, 'no lookup date means the register stays unavailable');
        File::delete($register);

        $out = File::get($doc);
        $this->assertStringContainsString('| 1 | study medicine in UK from Nigeria | Informational | Yes | Weak | Semrush NG 2026-10-03: vol 320, KD 22 ("study medicine in uk from nigeria") | Top: https://a.example https://b.example |', $out);
        $this->assertStringContainsString('| 2 | NECO medicine UK | Informational | Yes | Very weak | DATA UNAVAILABLE', $out, 'a row without a lookup date stays unavailable');
        File::delete([$doc, $csv]);
    }
}
