<?php

namespace App\Console\Commands;

use App\Services\Search\SearchConsole;
use App\Support\Sitemap;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Nightly copy of the site's own Search Console data into this database (Admin → Search, smukn:search-report):
 *  - performance rows (date × query × page × country) for the last --days days of final data, re-read each night
 *    because Google completes recent days late; rows older than 16 months (Search Console's own limit) are pruned;
 *  - Google's index status for every sitemap URL (URL Inspection API), so "indexed" is only ever claimed from Google's answer.
 * Does nothing until the owner connects a service account (GSC_SERVICE_ACCOUNT). Counts only are logged.
 */
class SyncSearchConsole extends Command
{
    protected $signature = 'smukn:gsc-sync {--days=10 : days of performance data to (re)read; up to 480 for a first backfill} {--skip-inspection : performance data only}';

    protected $description = 'Copy Search Console performance data and sitemap index status into the database (read only)';

    public function handle(SearchConsole $gsc): int
    {
        if (! SearchConsole::configured()) {
            $this->info('Search Console is not connected (GSC_SERVICE_ACCOUNT empty): nothing fetched.');

            return self::SUCCESS;
        }
        $days = max(1, min(480, (int) $this->option('days')));
        $end = now()->subDays(2)->toDateString(); // final data lags about two days
        $start = Carbon::parse($end)->subDays($days - 1)->toDateString();
        try {
            $rows = 0;
            for ($startRow = 0; ; $startRow += 25000) {
                $page = $gsc->searchAnalytics($start, $end, $startRow);
                foreach (array_chunk($page, 500) as $chunk) {
                    DB::table('search_performance')->upsert(array_map(fn ($r) => self::row($r), $chunk), ['row_hash'], ['clicks', 'impressions', 'position']);
                }
                $rows += count($page);
                if (count($page) < 25000) {
                    break;
                }
            }
            DB::table('search_performance')->where('date', '<', now()->subMonths(16)->toDateString())->delete();
            $inspected = $this->option('skip-inspection') ? 0 : $this->inspect($gsc);
        } catch (Throwable $e) {
            Log::warning('gsc.sync_failed', ['error' => substr($e->getMessage(), 0, 300)]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $summary = ['from' => $start, 'to' => $end, 'rows' => $rows, 'urls_inspected' => $inspected];
        Cache::forever('gsc.last_sync', ['at' => now()->toIso8601String()] + $summary);
        Log::info('gsc.synced', $summary);
        $this->info("Search Console: {$rows} performance rows {$start} to {$end}; {$inspected} sitemap URL(s) inspected.");

        return self::SUCCESS;
    }

    /** @param array{keys: list<string>, clicks: float, impressions: float, position: float} $r */
    private static function row(array $r): array
    {
        [$date, $query, $page, $country] = $r['keys'];

        return [
            'date' => $date, 'query' => mb_substr($query, 0, 512), 'page' => mb_substr($page, 0, 512), 'country' => substr(strtolower($country), 0, 3),
            'row_hash' => sha1($date."\n".$query."\n".$page."\n".$country),
            'clicks' => (int) $r['clicks'], 'impressions' => (int) $r['impressions'], 'position' => round((float) $r['position'], 2),
        ];
    }

    private function inspect(SearchConsole $gsc): int
    {
        $n = 0;
        foreach (Sitemap::urls() as $url) {
            $s = $gsc->inspect($url);
            DB::table('search_index_status')->updateOrInsert(['url_hash' => sha1($url)], [
                'url' => $url, 'verdict' => $s['verdict'] ?? null, 'coverage_state' => isset($s['coverageState']) ? mb_substr($s['coverageState'], 0, 160) : null,
                'indexing_state' => $s['indexingState'] ?? null, 'page_fetch_state' => $s['pageFetchState'] ?? null,
                'google_canonical' => isset($s['googleCanonical']) ? mb_substr($s['googleCanonical'], 0, 512) : null,
                'last_crawl_at' => isset($s['lastCrawlTime']) ? Carbon::parse($s['lastCrawlTime']) : null, 'inspected_at' => now(),
            ]);
            $n++;
        }
        // a URL that left the sitemap is no longer tracked
        DB::table('search_index_status')->whereNotIn('url_hash', array_map('sha1', Sitemap::urls()))->delete();

        return $n;
    }
}
