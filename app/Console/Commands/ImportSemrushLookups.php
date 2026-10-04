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
    protected $signature = 'smukn:semrush-import {file : CSV exported from data/semrush/lookup-sheet.csv} {--doc=docs/research/02-nigerian-search-demand.md} {--register=data/seo/decision-register.csv} {--sheet=data/semrush/lookup-sheet.csv : lookup sheet used to match a native Semrush export} {--database=ng : database of a native Semrush export} {--date= : lookup date of a native Semrush export (YYYY-MM-DD)}';

    protected $description = 'Record Semrush lookups made in the owner\'s browser into the search-demand research document';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (! is_file($path)) {
            $this->error("No file at {$path}");

            return self::FAILURE;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines || str_starts_with((string) $lines[0], "PK\x03\x04")) {
            $this->error('This is not a CSV file (an .xlsx export?). In Semrush choose Export → CSV.');

            return self::FAILURE;
        }
        // Semrush offers "CSV" and "CSV semicolon"; spreadsheet re-saves may use tabs. Use whichever the header uses most.
        $first = (string) $lines[0];
        $delimiter = collect([',' => substr_count($first, ','), ';' => substr_count($first, ';'), "\t" => substr_count($first, "\t")])->sortDesc()->keys()->first();
        $rows = array_map(fn ($l) => str_getcsv($l, $delimiter), $lines);
        $header = array_map(fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)), array_shift($rows));
        if (! in_array('id', $header, true) && preg_grep('/^keyword$/i', $header)) {
            // A Semrush export as downloaded (Keyword Overview bulk analysis or Keyword Magic Tool): match its rows to the
            // lookup sheet by keyword and record them in the lookup format, so one paste-and-export replaces row-by-row entry.
            $converted = $this->fromNativeExport($header, $rows);
            if ($converted === null) {
                return self::FAILURE;
            }
            [$header, $rows] = $converted;
        }
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

    /**
     * @param  list<string>  $header
     * @param  list<list<string>>  $rows
     * @return array{0: list<string>, 1: list<list<string>>}|null
     */
    private function fromNativeExport(array $header, array $rows): ?array
    {
        $date = (string) $this->option('date');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error('A native Semrush export carries no date: pass --date=YYYY-MM-DD (the day you exported it).');

            return null;
        }
        $col = function (string $prefix) use ($header): ?int {
            foreach ($header as $i => $h) {
                if (stripos($h, $prefix) === 0) {
                    return $i;
                }
            }

            return null;
        };
        [$kw, $vol, $kd, $cpc, $intent] = [$col('Keyword'), $col('Volume'), $col('Keyword Difficulty') ?? $col('KD'), $col('CPC'), $col('Intent')];
        if ($vol === null) {
            $this->error('The export has no Volume column: export Keyword Overview (bulk analysis) or Keyword Magic Tool results.');

            return null;
        }
        $norm = fn (string $k): string => preg_replace('/\s+/u', ' ', mb_strtolower(trim($k)));
        $found = [];
        $conflicts = [];
        foreach ($rows as $r) {
            $key = $norm((string) ($r[$kw] ?? ''));
            if ($key === '') {
                continue;
            }
            if (isset($found[$key])) {
                // The same keyword twice (two exports pasted together): keep the first, and never pick silently between different figures.
                if (($found[$key][$vol] ?? null) !== ($r[$vol] ?? null) || ($kd !== null && ($found[$key][$kd] ?? null) !== ($r[$kd] ?? null))) {
                    $conflicts[$key] = true;
                }

                continue;
            }
            $found[$key] = $r;
        }
        foreach (array_keys($conflicts) as $key) {
            unset($found[$key]);
            $this->warn("\"{$key}\" appears more than once with different figures: not recorded. Export it once and import again.");
        }
        $sheet = array_map('str_getcsv', file(base_path((string) $this->option('sheet')), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $sheetHeader = array_shift($sheet);
        $out = [];
        $matched = 0;
        $seen = [];
        foreach ($sheet as $s) {
            $row = array_combine($sheetHeader, array_pad($s, count($sheetHeader), ''));
            $keyword = $norm($row['semrush_keyword_used'] !== '' ? $row['semrush_keyword_used'] : $row['query_theme']);
            if (isset($seen[$keyword])) {
                $this->warn("Lookup themes {$seen[$keyword]} and {$row['id']} use the same keyword \"{$keyword}\": one query, one page (fix the lookup sheet).");
            }
            $seen[$keyword] = $row['id'];
            $hit = $found[$keyword] ?? null;
            if ($hit) {
                $matched++;
                $row['semrush_keyword_used'] = $keyword;
                $row['database'] = strtolower((string) $this->option('database'));
                $row['volume'] = $vol !== null ? preg_replace('/[^\d]/', '', (string) ($hit[$vol] ?? '')) : '';
                $row['keyword_difficulty'] = $kd !== null ? preg_replace('/[^\d]/', '', (string) ($hit[$kd] ?? '')) : '';
                $row['cpc'] = $cpc !== null ? trim((string) ($hit[$cpc] ?? '')) : '';
                $row['intent_semrush'] = $intent !== null ? trim((string) ($hit[$intent] ?? '')) : '';
                $row['top_3_urls'] = $row['top_3_urls'] ?? '';
                $row['looked_up_on'] = $date;
            }
            $out[] = $row;
        }
        $this->info("{$matched} of ".count($sheet).' lookup theme(s) found in the Semrush export ('.count($rows).' keyword row(s)).');
        // Keep the converted record in the repository, next to the export it came from.
        $record = 'data/semrush/lookups-'.$date.'.csv';
        $h = fopen(base_path($record), 'w');
        fputcsv($h, $sheetHeader, ',', '"', '');
        foreach ($out as $row) {
            fputcsv($h, array_map(fn ($c) => $row[$c] ?? '', $sheetHeader), ',', '"', '');
        }
        fclose($h);
        $this->line("Recorded as {$record}.");

        return [$sheetHeader, array_map(fn ($row) => array_map(fn ($c) => $row[$c] ?? '', $sheetHeader), $out)];
    }
}
