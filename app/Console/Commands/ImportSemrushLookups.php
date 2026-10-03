<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Pulls owner-recorded Semrush figures (data/semrush/lookups-*.csv) into the research record, replacing the
 * "DATA UNAVAILABLE" placeholders in docs/research/02 §4 so the asset register decisions can be re-evaluated.
 * Figures are only ever copied from the CSV; nothing is estimated.
 */
class ImportSemrushLookups extends Command
{
    protected $signature = 'smukn:semrush-import {file : CSV exported from data/semrush/lookup-sheet.csv} {--doc=docs/research/02-nigerian-search-demand.md} {--register=data/seo/decision-register.csv}';

    protected $description = 'Record Semrush lookups made in the owner\'s browser into the search-demand research document';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (! is_file($path)) {
            $this->error("No file at {$path}");

            return self::FAILURE;
        }
        $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $header = array_shift($rows);
        $required = ['id', 'volume', 'keyword_difficulty', 'looked_up_on'];
        foreach ($required as $col) {
            if (! in_array($col, $header, true)) {
                $this->error("Column '{$col}' missing from the CSV header");

                return self::FAILURE;
            }
        }
        $docPath = base_path($this->option('doc'));
        $doc = File::get($docPath);
        $updated = 0;
        $supported = [];
        foreach ($rows as $r) {
            $row = array_combine($header, array_pad($r, count($header), ''));
            $id = (int) $row['id'];
            if (! $id || $row['looked_up_on'] === '') {
                continue;
            }
            $vol = $row['volume'] !== '' ? $row['volume'] : 'n/a';
            $kd = $row['keyword_difficulty'] !== '' ? $row['keyword_difficulty'] : 'n/a';
            $db = strtoupper($row['database'] ?? 'ng');
            $cell = "Semrush {$db} {$row['looked_up_on']}: vol {$vol}, KD {$kd}".(($row['semrush_keyword_used'] ?? '') !== '' ? " (\"{$row['semrush_keyword_used']}\")" : '');
            // the table row for this id: replace the first two DATA UNAVAILABLE cells (volume, difficulty) with the recorded figures
            $pattern = '/^(\| '.$id.' \| (?:[^|]*\|){4}\s*)DATA UNAVAILABLE[^|]*\|\s*DATA UNAVAILABLE[^|]*\|/m';
            $new = preg_replace($pattern, '$1'.$cell.' | '.($row['top_3_urls'] !== '' ? 'Top: '.$row['top_3_urls'] : 'Top URLs not recorded').' |', $doc, 1, $count);
            if ($count) {
                $doc = $new;
                $updated++;
                if (is_numeric($row['volume']) && (int) $row['volume'] >= 50) {
                    $supported[] = "#{$id} {$row['query_theme']} (vol {$row['volume']})";
                }
            }
        }
        File::put($docPath, $doc);
        $this->info("{$updated} row(s) updated in {$this->option('doc')}.");
        $this->updateRegister($header, $rows);
        if ($supported) {
            $this->line('Query themes with recorded demand ≥ 50/month — re-check their rows in docs/decision/page-asset-register.md:');
            foreach ($supported as $s) {
                $this->line('  '.$s);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Copy recorded volume and difficulty into the SEO decision register rows named in the lookup's register_ids
     * column (semicolon-separated), and note the database, date and keyword in the row's sources. Rows without a
     * lookup date or without numeric figures are left reading DATA UNAVAILABLE.
     *
     * @param  array<int, string>  $header
     * @param  array<int, array<int, string|null>>  $rows
     */
    private function updateRegister(array $header, array $rows): void
    {
        $registerPath = base_path($this->option('register'));
        if (! in_array('register_ids', $header, true) || ! is_file($registerPath)) {
            return;
        }
        $h = fopen($registerPath, 'r');
        $regHeader = fgetcsv($h);
        $register = [];
        while (($r = fgetcsv($h)) !== false) {
            $register[] = array_combine($regHeader, $r);
        }
        fclose($h);
        $touched = 0;
        foreach ($rows as $r) {
            $row = array_combine($header, array_pad($r, count($header), ''));
            if ($row['looked_up_on'] === '' || ! is_numeric($row['volume']) || ! is_numeric($row['keyword_difficulty'])) {
                continue;
            }
            $db = strtoupper($row['database'] ?: 'ng');
            $note = "Semrush {$db} {$row['looked_up_on']}".(($row['semrush_keyword_used'] ?? '') !== '' ? " (\"{$row['semrush_keyword_used']}\")" : '');
            foreach (array_filter(array_map('trim', explode(';', (string) $row['register_ids']))) as $id) {
                foreach ($register as &$reg) {
                    if ($reg['id'] === $id) {
                        $reg['volume'] = $row['volume'];
                        $reg['keyword_difficulty'] = $row['keyword_difficulty'];
                        if (! str_contains($reg['sources'], $note)) {
                            $reg['sources'] = rtrim($reg['sources']).' · '.$note;
                        }
                        $touched++;
                    }
                }
                unset($reg);
            }
        }
        if ($touched) {
            $out = fopen($registerPath, 'w');
            fputcsv($out, $regHeader, ',', '"', '');
            foreach ($register as $reg) {
                fputcsv($out, array_values($reg), ',', '"', '');
            }
            fclose($out);
        }
        $this->info("{$touched} decision-register row(s) updated in {$this->option('register')}.");
    }
}
