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

    public function test_a_native_semrush_export_is_matched_to_the_lookup_sheet_by_keyword(): void
    {
        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $sheet = "{$dir}/sheet.csv";
        File::put($sheet, "id,query_theme,intent_hypothesis,semrush_keyword_used,database,volume,keyword_difficulty,cpc,intent_semrush,top_3_urls,trend_note,looked_up_on,register_ids\n".
            "1,study medicine in UK from Nigeria,Informational,study medicine in uk from nigeria,ng,,,,,,,,C01\n".
            "2,NECO medicine UK,Informational,neco medicine uk,ng,,,,,,,,G01\n");
        $export = "{$dir}/semrush-export.csv";
        // Semrush's own column layout (with a byte-order mark), one theme present and one absent
        File::put($export, "\xEF\xBB\xBFKeyword,Intent,Volume,Trend,Keyword Difficulty,CPC (USD),Competitive Density\n".
            "Study medicine in UK from Nigeria,Informational,\"1,300\",\"0.5,0.6\",34,0.21,0.1\n");
        $doc = "{$dir}/demand.md";
        File::put($doc, "| # | Query theme | Intent | Nigerian | SERP | Volume | Difficulty | Notes |\n| 1 | study medicine in UK from Nigeria | Informational | Yes | Weak | DATA UNAVAILABLE (x) | DATA UNAVAILABLE (x) | - |\n");
        $register = "{$dir}/register.csv";
        File::put($register, "id,query_family,volume,keyword_difficulty,sources,status\nC01,core,DATA UNAVAILABLE (pending),DATA UNAVAILABLE (pending),02 §A,PUBLISHED\n");
        $rel = fn ($p) => str_replace(base_path().'/', '', $p);

        $this->artisan('smukn:semrush-import', ['file' => $rel($export), '--sheet' => $rel($sheet), '--doc' => $rel($doc), '--register' => $rel($register), '--date' => '2026-10-05'])
            ->expectsOutputToContain('1 of 2 lookup theme(s) found')->expectsOutputToContain('1 row(s) updated')->assertSuccessful();

        $this->assertStringContainsString('Semrush NG 2026-10-05: vol 1300, KD 34', File::get($doc));
        $this->assertStringContainsString('C01,core,1300,34,', File::get($register));
        $recorded = base_path('data/semrush/lookups-2026-10-05.csv');
        $this->assertFileExists($recorded);
        $this->assertStringContainsString('1,"study medicine in UK from Nigeria",Informational,"study medicine in uk from nigeria",ng,1300,34,0.21,Informational,,,2026-10-05,C01', File::get($recorded));
        File::delete([$sheet, $export, $doc, $register, $recorded]);

        $this->artisan('smukn:semrush-import', ['file' => $rel($export)])->assertFailed(); // file gone
    }

    public function test_a_native_export_without_a_date_is_refused(): void
    {
        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        File::put("{$dir}/native.csv", "Keyword,Volume,Keyword Difficulty\nucat nigeria,90,20\n");
        $this->artisan('smukn:semrush-import', ['file' => str_replace(base_path().'/', '', "{$dir}/native.csv")])->expectsOutputToContain('pass --date')->assertFailed();
        File::delete("{$dir}/native.csv");
    }
}
