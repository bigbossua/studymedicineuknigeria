<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 301s for the previous site's URLs (its sitemap, 2026-10-04: data/seo/legacy-redirects.csv), so indexed pages and
 * links keep working after the switch. Run by smukn:reference-sync on every deploy. Inserts only missing paths: a
 * redirect changed or switched off in Admin → Redirects is never overwritten. URLs with no equivalent page are listed
 * in data/seo/legacy-gone.txt and answer 404 (no redirect to an unrelated page).
 */
class LegacyRedirectsSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('data/seo/legacy-redirects.csv');
        $h = fopen($file, 'r');
        fgetcsv($h, null, ',', '"', '');
        while (($row = fgetcsv($h, null, ',', '"', '')) !== false) {
            [$from, $to, $reason] = $row;
            $from = strtolower('/'.trim($from, '/'));
            if (! DB::table('redirects')->where('from_path', $from)->exists()) {
                DB::table('redirects')->insert(['from_path' => $from, 'to_path' => $to, 'status_code' => 301, 'reason' => $reason, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        fclose($h);
        Cache::forget('redirects.map');
    }
}
