<?php

namespace App\Console\Commands;

use App\Services\Search\SearchConsole;
use App\Services\Search\SearchInsights;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * The weekly organic-search summary (search-report.yml prints it into a public Actions log, so it holds no secrets and
 * no personal data): totals, Google's index status for the sitemap URLs, and the opportunity lists of SearchInsights.
 * Pages are public URLs. A query is printed only when it drew at least --min-impressions in the period (Google already
 * withholds rare queries); smaller ones are counted, not shown.
 */
class SearchReport extends Command
{
    protected $signature = 'smukn:search-report {--days=28} {--min-impressions=20 : smallest query volume printed by name}';

    protected $description = 'Summarise the stored Search Console data: totals, index status and opportunities';

    public function handle(): int
    {
        $sync = Cache::get('gsc.last_sync');
        $this->line('Search Console: '.(SearchConsole::configured() ? 'connected ('.SearchConsole::property().')' : 'not connected').'; last sync '.($sync['at'] ?? 'never'));
        $in = new SearchInsights((int) $this->option('days'));
        $min = (int) $this->option('min-impressions');
        $name = fn (array $r) => $r['impressions'] >= $min ? '"'.$r['query'].'"' : '(query under '.$min.' impressions)';
        $fmt = fn (array $r) => "{$r['impressions']} impr, {$r['clicks']} clicks, CTR ".round($r['ctr'] * 100, 1).'%, pos '.($r['position'] ?? '-');

        $index = $in->indexStatus();
        $this->line('');
        $this->line('INDEX STATUS (Google URL Inspection): '.($index->isEmpty() ? 'not inspected yet' : $index->where('verdict', 'PASS')->count().' of '.$index->count().' sitemap URLs on Google'));
        foreach ($index->where('verdict', '!=', 'PASS') as $s) {
            $this->line("  {$s->url}: ".($s->coverage_state ?? $s->verdict ?? 'unknown').($s->last_crawl_at ? ", last crawl {$s->last_crawl_at}" : ''));
        }
        if (! $in->hasData()) {
            $this->line('');
            $this->line('PERFORMANCE: no data stored yet.');

            return self::SUCCESS;
        }
        $p = $in->period();
        $t = $in->totals();
        $this->line('');
        $this->line("PERFORMANCE {$p['from']} to {$p['to']} (previous {$p['prev_from']} to {$p['prev_to']})");
        $this->line('  all countries: '.$fmt($t['all']).' (previous: '.$fmt($t['all_prev']).')');
        $this->line('  Nigeria:       '.$fmt($t['nigeria']).' (previous: '.$fmt($t['nigeria_prev']).')');

        $sections = [
            'NIGERIAN QUERIES' => $in->nigeriaQueries()->map(fn ($r) => $name($r).': '.$fmt($r)),
            'PAGES GAINING IMPRESSIONS' => $in->pagesGaining()->map(fn ($r) => "{$r['page']}: {$r['previous']} -> {$r['impressions']} impr, CTR ".round($r['ctr'] * 100, 1).'%, pos '.$r['position']),
            'LOW CTR ON PAGE ONE (review title/description)' => $in->lowCtrPages()->map(fn ($r) => "{$r['page']}: ".$fmt($r)),
            'QUERIES SPLIT ACROSS PAGES (one intent, one page)' => $in->cannibalisation()->map(fn ($q) => $name(['query' => $q['query'], 'impressions' => $q['pages']->sum('impressions')]).': '.$q['pages']->map(fn ($r) => "{$r['page']} ({$r['impressions']})")->implode(', ')),
            'NEW QUERIES THIS WEEK' => $in->newQueries()->map(fn ($r) => $name($r).': '.$fmt($r)),
            'SCHOOL DEMAND FROM NIGERIA (unpublished schools; evidence for DECISION-ENGINE §8, not a decision)' => $in->schoolDemand()->map(fn ($s) => "{$s['school']}: {$s['impressions']} impr, {$s['clicks']} clicks over {$s['queries']} queries"),
        ];
        foreach ($sections as $title => $lines) {
            $this->line('');
            $this->line($title.': '.($lines->isEmpty() ? 'none' : $lines->count()));
            $lines->each(fn ($l) => $this->line('  '.$l));
        }

        return self::SUCCESS;
    }
}
