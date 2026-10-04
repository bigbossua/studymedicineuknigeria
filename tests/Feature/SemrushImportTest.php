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

    public function test_semicolon_exports_duplicates_and_spacing_are_handled_without_guessing(): void
    {
        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $sheet = "{$dir}/sheet2.csv";
        File::put($sheet, "id,query_theme,intent_hypothesis,semrush_keyword_used,database,volume,keyword_difficulty,cpc,intent_semrush,top_3_urls,trend_note,looked_up_on,register_ids\n".
            "1,ucat nigeria,Informational,ucat  nigeria,ng,,,,,,,,H01\n".
            "2,neco medicine uk,Informational,neco medicine uk,ng,,,,,,,,G01\n".
            "3,neco medicine uk (again),Informational,neco medicine uk,ng,,,,,,,,G02\n");
        $export = "{$dir}/semicolon.csv";
        // "CSV semicolon" export: one keyword with odd spacing, one keyword twice with different figures (two exports pasted together)
        File::put($export, "Keyword;Intent;Volume;Keyword Difficulty;CPC (USD)\nUCAT Nigeria ;Informational;90;20;0.10\nneco medicine uk;Informational;40;12;0\nneco medicine uk;Informational;70;15;0\n");
        $doc = "{$dir}/demand2.md";
        File::put($doc, "| 1 | ucat nigeria | I | Yes | Weak | DATA UNAVAILABLE (x) | DATA UNAVAILABLE (x) | - |\n| 2 | neco | I | Yes | Weak | DATA UNAVAILABLE (x) | DATA UNAVAILABLE (x) | - |\n");
        $register = "{$dir}/register2.csv";
        File::put($register, "id,query_family,volume,keyword_difficulty,sources,status\nH01,ucat,DATA UNAVAILABLE,DATA UNAVAILABLE,x,PUBLISHED\nG01,neco,DATA UNAVAILABLE,DATA UNAVAILABLE,x,PUBLISHED\n");
        $rel = fn ($p) => str_replace(base_path().'/', '', $p);

        $this->artisan('smukn:semrush-import', ['file' => $rel($export), '--sheet' => $rel($sheet), '--doc' => $rel($doc), '--register' => $rel($register), '--date' => '2026-10-06'])
            ->expectsOutputToContain('appears more than once with different figures')
            ->expectsOutputToContain('Lookup themes 2 and 3 use the same keyword')
            ->expectsOutputToContain('1 of 3 lookup theme(s) found')->assertSuccessful();

        $this->assertStringContainsString('H01,ucat,90,20,', File::get($register));
        $this->assertStringContainsString('G01,neco,"DATA UNAVAILABLE","DATA UNAVAILABLE"', File::get($register), 'conflicting figures are never picked between');
        File::delete([$sheet, $export, $doc, $register, base_path('data/semrush/lookups-2026-10-06.csv')]);

        File::put("{$dir}/fake.xlsx", "PK\x03\x04binary");
        $this->artisan('smukn:semrush-import', ['file' => $rel("{$dir}/fake.xlsx"), '--date' => '2026-10-06'])->expectsOutputToContain('not a CSV file')->assertFailed();
        File::delete("{$dir}/fake.xlsx");
    }

    public function test_the_committed_paste_list_is_fifty_distinct_nigeria_keywords_that_map_to_live_register_rows(): void
    {
        $rows = array_map('str_getcsv', file(base_path('data/semrush/lookup-sheet.csv'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $header = array_shift($rows);
        $rows = array_map(fn ($r) => array_combine($header, $r), $rows);
        $register = array_map('str_getcsv', file(base_path('data/seo/decision-register.csv'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $regIds = array_column(array_slice($register, 1), 0);

        $keywords = array_map(fn ($r) => mb_strtolower(trim($r['semrush_keyword_used'])), $rows);
        $this->assertCount(50, $rows);
        $this->assertSame([], array_keys(array_filter(array_count_values($keywords), fn ($c) => $c > 1)), 'one keyword per theme');
        $this->assertSame($keywords, array_map(fn ($l) => mb_strtolower(trim($l)), file(base_path('data/semrush/paste-list.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)), 'paste-list.txt is the sheet\'s keyword column, in order');
        foreach ($rows as $r) {
            $this->assertSame('ng', $r['database'], "theme {$r['id']} uses the Nigeria database");
            $this->assertNotSame('', $r['register_ids'], "theme {$r['id']} names a register row");
            foreach (array_filter(array_map('trim', explode(';', $r['register_ids']))) as $id) {
                $this->assertContains($id, $regIds, "theme {$r['id']} points at register row {$id}");
            }
            $this->assertSame('', $r['volume'].$r['keyword_difficulty'].$r['cpc'], 'no figures in the sheet before a recorded Semrush export');
        }
    }
}
