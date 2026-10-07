<?php

namespace App\Services\Search;

use App\Models\University;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reads the stored Search Console data (smukn:gsc-sync) and lists what the organic-growth loop acts on
 * (docs/ops/OPERATING-MODE.md): Nigerian queries, pages gaining impressions, pages Google shows but searchers rarely
 * click, queries split across two pages, queries new this week, and searches for schools that are not yet published.
 * Every list is a lead to verify, not a decision: DECISION-ENGINE §8 still governs publication.
 */
class SearchInsights
{
    public const NIGERIA = 'nga';

    public function __construct(private int $days = 28) {}

    public function hasData(): bool
    {
        return DB::table('search_performance')->exists();
    }

    /** The newest date with data; periods end here (Google's final data lags about two days). */
    public function latest(): ?Carbon
    {
        $d = DB::table('search_performance')->max('date');

        return $d ? Carbon::parse($d) : null;
    }

    /** @return array{from: string, to: string, prev_from: string, prev_to: string} */
    public function period(): array
    {
        $to = $this->latest() ?? now()->subDays(2);

        return ['from' => $to->copy()->subDays($this->days - 1)->toDateString(), 'to' => $to->toDateString(),
            'prev_from' => $to->copy()->subDays(2 * $this->days - 1)->toDateString(), 'prev_to' => $to->copy()->subDays($this->days)->toDateString()];
    }

    /** Clicks, impressions, CTR and impression-weighted position for the period and the one before, all countries and Nigeria. */
    public function totals(): array
    {
        $p = $this->period();
        $sum = fn (string $from, string $to, ?string $country = null) => $this->aggregate(DB::table('search_performance')->whereBetween('date', [$from, $to])
            ->when($country, fn ($q) => $q->where('country', $country)))->first();

        return [
            'all' => $this->shape($sum($p['from'], $p['to'])), 'all_prev' => $this->shape($sum($p['prev_from'], $p['prev_to'])),
            'nigeria' => $this->shape($sum($p['from'], $p['to'], self::NIGERIA)), 'nigeria_prev' => $this->shape($sum($p['prev_from'], $p['prev_to'], self::NIGERIA)),
        ];
    }

    /** Queries searched from Nigeria, most impressions first. */
    public function nigeriaQueries(int $limit = 25): Collection
    {
        $p = $this->period();

        return $this->aggregate(DB::table('search_performance')->whereBetween('date', [$p['from'], $p['to']])->where('country', self::NIGERIA), 'query')
            ->orderByDesc('impressions')->limit($limit)->get()->map(fn ($r) => $this->shape($r));
    }

    /** Pages whose impressions rose against the previous period (all countries). */
    public function pagesGaining(int $limit = 15): Collection
    {
        $p = $this->period();
        $now = $this->aggregate(DB::table('search_performance')->whereBetween('date', [$p['from'], $p['to']]), 'page')->get()->keyBy('page');
        $before = $this->aggregate(DB::table('search_performance')->whereBetween('date', [$p['prev_from'], $p['prev_to']]), 'page')->get()->keyBy('page');

        return $now->map(fn ($r) => $this->shape($r) + ['previous' => (int) ($before[$r->page]->impressions ?? 0)])
            ->map(fn ($r) => $r + ['gain' => $r['impressions'] - $r['previous']])->filter(fn ($r) => $r['gain'] > 0)
            ->sortByDesc('gain')->take($limit)->values();
    }

    /** Pages on page one of the results (position ≤ 10) with enough impressions to judge, and a CTR under 2%: title or description to review. */
    public function lowCtrPages(int $minImpressions = 100): Collection
    {
        $p = $this->period();

        return $this->aggregate(DB::table('search_performance')->whereBetween('date', [$p['from'], $p['to']]), 'page')->get()
            ->map(fn ($r) => $this->shape($r))->filter(fn ($r) => $r['impressions'] >= $minImpressions && $r['position'] <= 10 && $r['ctr'] < 0.02)
            ->sortByDesc('impressions')->values();
    }

    /** Queries for which two or more of our pages each drew at least $min impressions: one intent, one page (DECISION-ENGINE). */
    public function cannibalisation(int $min = 10): Collection
    {
        $p = $this->period();

        return $this->aggregate(DB::table('search_performance')->whereBetween('date', [$p['from'], $p['to']]), 'query', 'page')->get()
            ->filter(fn ($r) => (int) $r->impressions >= $min)->groupBy('query')->filter(fn ($rows) => $rows->count() >= 2)
            ->map(fn ($rows, $query) => ['query' => $query, 'pages' => $rows->map(fn ($r) => $this->shape($r))->sortByDesc('impressions')->values()])
            ->sortByDesc(fn ($q) => $q['pages']->sum('impressions'))->values();
    }

    /** Queries with impressions in the last 7 days and none in the rest of the stored history. */
    public function newQueries(int $limit = 25): Collection
    {
        $to = $this->latest();
        if (! $to) {
            return collect();
        }
        $from = $to->copy()->subDays(6)->toDateString();
        $seen = DB::table('search_performance')->where('date', '<', $from)->select('query');

        return $this->aggregate(DB::table('search_performance')->where('date', '>=', $from)->whereNotIn('query', $seen), 'query')
            ->orderByDesc('impressions')->limit($limit)->get()->map(fn ($r) => $this->shape($r));
    }

    /**
     * Searches from Nigeria that name a medical school whose page is not published: demand evidence for DECISION-ENGINE §8.
     * Nothing is published from this list automatically.
     */
    public function schoolDemand(): Collection
    {
        $p = $this->period();
        $queries = $this->aggregate(DB::table('search_performance')->whereBetween('date', [$p['from'], $p['to']])->where('country', self::NIGERIA), 'query')->get();
        if ($queries->isEmpty()) {
            return collect();
        }

        return University::medicalSchools()->where('published', false)->orderBy('name')->get()->map(function (University $u) use ($queries) {
            $names = self::schoolNames($u);
            $hits = $queries->filter(fn ($q) => collect($names)->contains(fn ($n) => str_contains(self::normalise($q->query), $n)));

            return ['school' => $u->name, 'slug' => $u->slug, 'queries' => $hits->count(), 'impressions' => (int) $hits->sum('impressions'),
                'clicks' => (int) $hits->sum('clicks'), 'examples' => $hits->sortByDesc('impressions')->take(3)->pluck('query')->all()];
        })->filter(fn ($s) => $s['impressions'] > 0)->sortByDesc('impressions')->values();
    }

    /** Google's index status for the sitemap URLs, worst first. */
    public function indexStatus(): Collection
    {
        return DB::table('search_index_status')->orderByRaw("case when verdict = 'PASS' then 1 else 0 end")->orderBy('url')->get();
    }

    /** Distinctive, lower-case forms of a school's name as searchers type it ("aberdeen", "kings college london"). */
    public static function schoolNames(University $u): array
    {
        $name = self::normalise($u->name);
        $short = trim(preg_replace('/\b(the|university|of|medical|school|college|medicine)\b/', ' ', $name));
        $short = preg_replace('/\s+/', ' ', $short);
        $names = array_filter([$name, str_replace('-', ' ', (string) $u->slug), strlen($short) >= 4 ? $short : null]);

        return array_values(array_unique($names));
    }

    public static function normalise(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', ' ', str_replace(["'", '’'], '', mb_strtolower($s)))));
    }

    private function aggregate($query, string ...$by)
    {
        return $query->selectRaw(($by ? implode(', ', $by).', ' : '').'sum(clicks) clicks, sum(impressions) impressions, sum(position * impressions) pos_weight')
            ->when($by, fn ($q) => $q->groupBy(...$by));
    }

    private function shape(?object $r): array
    {
        $impr = (int) ($r->impressions ?? 0);
        $clicks = (int) ($r->clicks ?? 0);

        return array_filter(['query' => $r->query ?? null, 'page' => $r->page ?? null], fn ($v) => $v !== null) + [
            'clicks' => $clicks, 'impressions' => $impr, 'ctr' => $impr ? round($clicks / $impr, 4) : 0.0,
            'position' => $impr ? round((float) $r->pos_weight / $impr, 1) : null,
        ];
    }
}
