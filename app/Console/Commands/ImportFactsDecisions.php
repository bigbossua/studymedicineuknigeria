<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\ReferenceFact;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Applies reviewer decisions from a verification worksheet (see smukn:facts-export and data/verification/README.md).
 * Decisions: verified (requires a source URL and a verified_on date; verified_value replaces the value when given),
 * not_published, source_changed (new wording recorded, status SOURCE_CHANGED for editorial follow-up), archive.
 * Rows with an empty decision are ignored. Idempotent: re-running the same file changes nothing further.
 */
class ImportFactsDecisions extends Command
{
    protected $signature = 'smukn:facts-import {file : worksheet CSV with the decision columns filled in} {--dry-run : report what would change without saving}';

    protected $description = 'Apply verification decisions recorded in a worksheet CSV to the reference facts';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (! is_file($path)) {
            $this->error("No file at {$path}");

            return self::FAILURE;
        }
        $h = fopen($path, 'r');
        $header = fgetcsv($h, 0, ',', '"', '');
        foreach (['ref', 'decision'] as $col) {
            if (! in_array($col, $header, true)) {
                $this->error("Column '{$col}' missing");

                return self::FAILURE;
            }
        }
        $index = $this->index();
        $applied = $skipped = $unknown = 0;
        while (($r = fgetcsv($h, 0, ',', '"', '')) !== false) {
            $row = array_combine($header, array_slice(array_pad($r, count($header), ''), 0, count($header)));
            $decision = strtolower(trim((string) $row['decision']));
            if ($decision === '') {
                continue;
            }
            $fact = $index[$row['ref']] ?? null;
            if (! $fact) {
                $unknown++;
                $this->warn("unknown ref {$row['ref']}");

                continue;
            }
            $date = trim((string) ($row['verified_on'] ?? ''));
            $newSource = trim((string) ($row['new_source_url'] ?? ''));
            $newValue = trim((string) ($row['verified_value'] ?? ''));
            if ($newSource !== '') {
                $fact->source_url = $newSource;
            }
            switch ($decision) {
                case 'verified':
                    if (! $fact->source_url || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                        $skipped++;
                        $this->warn("skipped {$row['ref']}: 'verified' needs a source URL and verified_on as YYYY-MM-DD");

                        continue 2;
                    }
                    if ($newValue !== '') {
                        $this->setValue($fact, $newValue);
                    }
                    $fact->verification_status = ReferenceFact::VERIFIED;
                    $fact->verified_at = $date;
                    $fact->review_due_at = Carbon::parse($date)->addMonths(str_contains($fact->key, 'fee') || str_contains($fact->key, 'deadline') ? 6 : 12);
                    break;
                case 'not_published':
                    $fact->verification_status = ReferenceFact::NOT_PUBLISHED;
                    $fact->verified_at = $date ?: now()->toDateString();
                    break;
                case 'source_changed':
                    if ($newValue !== '') {
                        $this->setValue($fact, $newValue);
                    }
                    $fact->verification_status = ReferenceFact::SOURCE_CHANGED;
                    break;
                case 'archive':
                    $fact->verification_status = ReferenceFact::ARCHIVED;
                    break;
                default:
                    $skipped++;
                    $this->warn("skipped {$row['ref']}: unknown decision '{$decision}'");

                    continue 2;
            }
            if (($note = trim((string) ($row['reviewer_note'] ?? ''))) !== '' && ! str_contains((string) $fact->notes, "[Review {$date}: {$note}]")) {
                $fact->notes = trim(($fact->notes ? $fact->notes.' ' : '')."[Review {$date}: {$note}]");
            }
            if ($fact->isDirty()) {
                $applied++;
                if (! $this->option('dry-run')) {
                    $fact->save();
                }
            }
        }
        fclose($h);
        if (! $this->option('dry-run') && $applied) {
            // No signed-in admin on the command line: the audit trail is the committed decisions file plus the application log.
            Log::info('fact.worksheet_import', ['file' => $this->argument('file'), 'applied' => $applied, 'skipped' => $skipped, 'unknown' => $unknown]);
        }
        $this->info(($this->option('dry-run') ? '[dry run] ' : '')."{$applied} fact(s) updated, {$skipped} skipped, {$unknown} unknown reference(s).");

        return self::SUCCESS;
    }

    private function setValue(ReferenceFact $fact, string $value): void
    {
        if ($fact->value_number !== null && $fact->value_text === null && is_numeric(str_replace([',', '£'], '', $value))) {
            $fact->value_number = (float) str_replace([',', '£'], '', $value);
        } elseif ($fact->value_bool !== null && $fact->value_text === null && in_array(strtolower($value), ['yes', 'no', 'true', 'false'], true)) {
            $fact->value_bool = in_array(strtolower($value), ['yes', 'true'], true);
        } else {
            $fact->value_text = $value;
        }
    }

    /** @return array<string, ReferenceFact> */
    private function index(): array
    {
        $out = [];
        foreach (ReferenceFact::with('subject')->get() as $f) {
            if ($f->subject instanceof Course) {
                $f->subject->loadMissing('university');
            }
            $out[ExportFactsWorksheet::ref($f)] = $f;
        }

        return $out;
    }
}
