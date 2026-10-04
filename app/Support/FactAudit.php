<?php

namespace App\Support;

use App\Models\ReferenceFact;
use Illuminate\Support\Facades\DB;

/**
 * Records every change to the fields that decide what the public sees (value, year, source, status, verification)
 * in fact_changes, with the channel it came through. Called from the ReferenceFact model, so the admin queue, the
 * worksheet import, the source watcher and the reference sync all leave the same trail.
 */
final class FactAudit
{
    public const FIELDS = ['value_text', 'value_number', 'value_bool', 'value_json', 'academic_year', 'source_url', 'verification_status', 'verified_at', 'verified_by', 'review_due_at', 'notes'];

    private static ?string $via = null;

    /** Run $work with every fact change attributed to $via (e.g. "source watcher"). */
    public static function using(string $via, callable $work): mixed
    {
        $previous = self::$via;
        self::$via = $via;
        try {
            return $work();
        } finally {
            self::$via = $previous;
        }
    }

    public static function record(ReferenceFact $fact): void
    {
        $after = array_intersect_key($fact->getChanges(), array_flip(self::FIELDS));
        if ($after === []) {
            return;
        }
        $before = [];
        foreach (array_keys($after) as $field) {
            $before[$field] = $fact->getRawOriginal($field);
        }
        DB::table('fact_changes')->insert([
            'reference_fact_id' => $fact->getKey(),
            'user_id' => auth()->id(),
            'via' => self::$via ?? (auth()->check() ? 'admin' : (app()->runningInConsole() ? 'console' : 'web')),
            'before' => json_encode($before),
            'after' => json_encode($after),
            'created_at' => now(),
        ]);
    }
}
