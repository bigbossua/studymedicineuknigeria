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
    protected $signature = 'smukn:semrush-import {file : CSV exported from data/semrush/lookup-sheet.csv} {--doc=docs/research/02-nigerian-search-demand.md}';

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
        if ($supported) {
            $this->line('Query themes with recorded demand ≥ 50/month — re-check their rows in docs/decision/page-asset-register.md:');
            foreach ($supported as $s) {
                $this->line('  '.$s);
            }
        }

        return self::SUCCESS;
    }
}
