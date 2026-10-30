<?php

namespace App\Console\Commands;

use App\Models\ReferenceFact;
use App\Services\Reference\DatasetImporter;
use App\Support\FactAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Undoes one smukn:facts-import run, for a code rollback (ops/revert-release-facts.sh). The import records every change
 * in fact_changes with "worksheet import <file> <sha256 prefix>"; each fact still holding the imported values gets its
 * previous values back, so the earlier release sees the facts it was deployed with. A fact changed after the import
 * (the source watcher, an admin) keeps its newer values and is reported. The file's ledger row is removed, so the next
 * deploy applies the file again. Every revert is itself recorded in fact_changes.
 */
class RevertFactsImport extends Command
{
    protected $signature = 'smukn:facts-revert {file : a decisions CSV applied by smukn:facts-import} {--dry-run : report what would change without saving}';

    protected $description = 'Restore the facts changed by one decisions-file import (used by a code rollback)';

    public function handle(): int
    {
        $real = realpath(base_path($this->argument('file')));
        if (! $real || ! is_file($real)) {
            $this->error('No file at '.base_path($this->argument('file')));

            return self::FAILURE;
        }
        $hash = hash_file('sha256', $real);
        $via = 'worksheet import '.basename($real).' '.substr($hash, 0, 12);
        $changes = DB::table('fact_changes')->where('via', $via)->orderByDesc('id')->get();
        $restored = $kept = 0;
        foreach ($changes as $change) {
            $fact = ReferenceFact::find($change->reference_fact_id);
            $before = json_decode($change->before, true) ?: [];
            $after = json_decode($change->after, true) ?: [];
            if (! $fact || $before === []) {
                continue;
            }
            foreach ($after as $field => $value) {
                if (! self::same($fact->getRawOriginal($field), $value)) {
                    $kept++;
                    $this->warn("kept fact {$fact->id} ({$fact->key}): {$field} changed after the import");

                    continue 2;
                }
            }
            $restored++;
            if (! $this->option('dry-run')) {
                // raw values, exactly as they were stored before the import (no casts applied twice)
                $fact->setRawAttributes(array_merge($fact->getAttributes(), $before));
                FactAudit::using('worksheet revert '.basename($real).' '.substr($hash, 0, 12), fn () => $fact->save());
            }
        }
        if (! $this->option('dry-run')) {
            DB::table('fact_imports')->where('sha256', $hash)->delete();
            if ($restored) {
                app(DatasetImporter::class)->syncCourseColumns();
            }
            Log::warning('fact.worksheet_revert', ['file' => $this->argument('file'), 'sha256' => $hash, 'restored' => $restored, 'kept' => $kept]);
        }
        $this->info(($this->option('dry-run') ? '[dry run] ' : '')."{$restored} fact(s) restored, {$kept} kept (changed since the import).");

        return self::SUCCESS;
    }

    /** Stored values compared as the database returns them: dates by their date-time, numbers by value. */
    private static function same(mixed $current, mixed $imported): bool
    {
        if ($current === null || $imported === null) {
            return ($current === null || $current === '') && ($imported === null || $imported === '');
        }
        if (is_numeric($current) && is_numeric($imported)) {
            return (float) $current === (float) $imported;
        }
        $a = (string) $current;
        $b = is_scalar($imported) ? (string) $imported : json_encode($imported);
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $a) && preg_match('/^\d{4}-\d{2}-\d{2}/', $b)) {
            return strtotime($a) === strtotime($b);
        }

        return $a === $b;
    }
}
