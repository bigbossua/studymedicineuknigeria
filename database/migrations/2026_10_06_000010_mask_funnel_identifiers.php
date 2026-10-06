<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Funnel events stored before 2026-10-06 used a plain SHA-256 of the sequential application number (recoverable by
 * hashing every number) and kept the number in the page path. Re-key the hashes with HMAC and mask the paths.
 */
return new class extends Migration
{
    public function up(): void
    {
        $key = (string) config('app.key');
        $map = [];
        foreach (DB::table('applications')->pluck('application_number') as $number) {
            $map[hash('sha256', (string) $number)] = hash_hmac('sha256', (string) $number, $key);
        }
        DB::table('funnel_events')->select('id', 'application_hash', 'source_page', 'properties')->orderBy('id')->chunkById(500, function ($rows) use ($map) {
            foreach ($rows as $r) {
                $update = [];
                if ($r->application_hash !== null) {
                    $update['application_hash'] = $map[$r->application_hash] ?? null;
                }
                if ($r->source_page !== null && preg_match('/SMUKN-\d{4}-\d{6}/i', $r->source_page)) {
                    $update['source_page'] = preg_replace('/SMUKN-\d{4}-\d{6}/i', '{application}', $r->source_page);
                }
                $props = $r->properties ? json_decode($r->properties, true) : null;
                if (is_array($props) && array_key_exists('title', $props)) {
                    unset($props['title']);
                    $update['properties'] = $props ? json_encode($props) : null;
                }
                if ($update) {
                    DB::table('funnel_events')->where('id', $r->id)->update($update);
                }
            }
        });
    }

    public function down(): void
    {
        // one-way: the plain hashes and numbers are not restored
    }
};
