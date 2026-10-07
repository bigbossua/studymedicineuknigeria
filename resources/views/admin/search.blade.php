@php($pct = fn ($r) => number_format($r['ctr'] * 100, 1).'%')
<x-layouts.admin :seo="$seo">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-h2">Search</h1><p class="text-ink-700 mt-1 text-[0.9375rem]">Google Search Console data for <span class="font-mono">{{ $property }}</span>, read only. Every list is a lead to verify against the decision register, never a decision on its own.</p></div>
        <nav class="flex gap-2 text-[0.875rem]" aria-label="Period">@foreach([7,28,90] as $d)<a href="{{ route('admin.search',['days'=>$d]) }}" class="chip {{ $days===$d ? 'chip-info' : 'chip-pending' }} no-underline">{{ $d }} days</a>@endforeach</nav></div>

    @unless($connected)
        <section class="card mt-6"><p class="eyebrow mb-2">Not connected</p><p class="text-[0.9375rem] text-ink-700">No service-account key is installed (GSC_SERVICE_ACCOUNT). The owner connects it once: docs/ops/SEARCH-CONSOLE-AND-GA4.md, "Data feed". Until then nothing is fetched and this page stays empty.</p></section>
    @else
        <p class="hint mt-3">Service account <span class="font-mono">{{ $account }}</span> · last sync {{ $sync['at'] ?? 'never' }}@if($sync) ({{ $sync['rows'] }} rows {{ $sync['from'] }} to {{ $sync['to'] }}, {{ $sync['urls_inspected'] }} URLs inspected)@endif</p>
    @endunless

    <section class="card mt-6"><p class="eyebrow mb-3">Index status · Google URL Inspection</p>
        @if($index->isEmpty())<p class="text-[0.875rem] text-ink-500">Not inspected yet.</p>@else
        <p class="text-[0.9375rem] mb-3"><span class="font-semibold">{{ $index->where('verdict','PASS')->count() }} of {{ $index->count() }}</span> sitemap URLs are on Google.</p>
        <table class="text-[0.875rem]"><thead><tr><th>URL</th><th>Google says</th><th>Last crawl</th><th>Google canonical</th></tr></thead><tbody>
        @foreach($index as $s)<tr><td class="font-mono">{{ \Illuminate\Support\Str::after($s->url, '://') }}</td><td><span class="chip {{ $s->verdict === 'PASS' ? 'chip-ok' : 'chip-pending' }}">{{ $s->coverage_state ?? $s->verdict ?? '—' }}</span></td><td class="text-ink-500">{{ $s->last_crawl_at ? \Illuminate\Support\Str::before($s->last_crawl_at, ' ') : '—' }}</td><td class="text-ink-500">{{ $s->google_canonical && $s->google_canonical !== $s->url ? $s->google_canonical : ($s->google_canonical ? 'same' : '—') }}</td></tr>@endforeach
        </tbody></table>@endif
    </section>

    @if(! $hasData)
        <section class="card mt-6"><p class="eyebrow mb-2">Performance</p><p class="text-[0.875rem] text-ink-500">No performance data stored yet.</p></section>
    @else
    <div class="mt-6 grid md:grid-cols-2 gap-6">
        @foreach([['All countries', $totals['all'], $totals['all_prev']], ['Nigeria', $totals['nigeria'], $totals['nigeria_prev']]] as [$label, $now, $prev])
        <section class="card"><p class="eyebrow mb-3">{{ $label }} · {{ $period['from'] }} to {{ $period['to'] }}</p>
            <dl class="grid grid-cols-4 gap-3 text-[0.875rem]">
                <div><dt class="text-ink-500">Clicks</dt><dd class="text-h3">{{ $now['clicks'] }}</dd><dd class="hint">was {{ $prev['clicks'] }}</dd></div>
                <div><dt class="text-ink-500">Impressions</dt><dd class="text-h3">{{ $now['impressions'] }}</dd><dd class="hint">was {{ $prev['impressions'] }}</dd></div>
                <div><dt class="text-ink-500">CTR</dt><dd class="text-h3">{{ $pct($now) }}</dd><dd class="hint">was {{ $pct($prev) }}</dd></div>
                <div><dt class="text-ink-500">Position</dt><dd class="text-h3">{{ $now['position'] ?? '—' }}</dd><dd class="hint">was {{ $prev['position'] ?? '—' }}</dd></div>
            </dl></section>
        @endforeach
    </div>

    <div class="mt-6 grid lg:grid-cols-2 gap-6">
        <section class="card"><p class="eyebrow mb-3">Queries from Nigeria</p>
            @forelse($nigeria as $r)@if($loop->first)<table class="text-[0.875rem]"><thead><tr><th>Query</th><th class="text-right">Impr.</th><th class="text-right">Clicks</th><th class="text-right">CTR</th><th class="text-right">Pos.</th></tr></thead><tbody>@endif
                <tr><td>{{ $r['query'] }}</td><td class="text-right">{{ $r['impressions'] }}</td><td class="text-right">{{ $r['clicks'] }}</td><td class="text-right">{{ $pct($r) }}</td><td class="text-right">{{ $r['position'] }}</td></tr>
            @if($loop->last)</tbody></table>@endif @empty<p class="text-[0.875rem] text-ink-500">None in this period.</p>@endforelse
        </section>
        <div class="space-y-6">
            <section class="card"><p class="eyebrow mb-3">Pages gaining impressions</p>
                @forelse($gaining as $r)<p class="text-[0.875rem] flex justify-between gap-3"><span class="font-mono">{{ \Illuminate\Support\Str::after($r['page'], '.com') ?: '/' }}</span><span>{{ $r['previous'] }} → <span class="font-semibold">{{ $r['impressions'] }}</span></span></p>@empty<p class="text-[0.875rem] text-ink-500">None.</p>@endforelse
            </section>
            <section class="card"><p class="eyebrow mb-1">Low CTR on page one</p><p class="hint mb-3">Position 10 or better, 100+ impressions, CTR under 2%: review the title and description against the query.</p>
                @forelse($lowCtr as $r)<p class="text-[0.875rem] flex justify-between gap-3"><span class="font-mono">{{ \Illuminate\Support\Str::after($r['page'], '.com') ?: '/' }}</span><span>{{ $r['impressions'] }} impr · {{ $pct($r) }} · pos {{ $r['position'] }}</span></p>@empty<p class="text-[0.875rem] text-ink-500">None.</p>@endforelse
            </section>
            <section class="card"><p class="eyebrow mb-1">Queries split across pages</p><p class="hint mb-3">Two or more pages with 10+ impressions for one query: one intent should have one page.</p>
                @forelse($split as $q)<div class="text-[0.875rem] mb-2"><p class="font-semibold">{{ $q['query'] }}</p>@foreach($q['pages'] as $r)<p class="flex justify-between gap-3 text-ink-700"><span class="font-mono">{{ \Illuminate\Support\Str::after($r['page'], '.com') ?: '/' }}</span><span>{{ $r['impressions'] }} impr · pos {{ $r['position'] }}</span></p>@endforeach</div>@empty<p class="text-[0.875rem] text-ink-500">None.</p>@endforelse
            </section>
        </div>
    </div>

    <div class="mt-6 grid lg:grid-cols-2 gap-6">
        <section class="card"><p class="eyebrow mb-3">New queries in the last 7 days</p>
            @forelse($new as $r)<p class="text-[0.875rem] flex justify-between gap-3"><span>{{ $r['query'] }}</span><span>{{ $r['impressions'] }} impr · pos {{ $r['position'] }}</span></p>@empty<p class="text-[0.875rem] text-ink-500">None.</p>@endforelse
        </section>
        <section class="card"><p class="eyebrow mb-1">School searches from Nigeria</p><p class="hint mb-3">Unpublished schools named in Nigerian searches. Evidence for DECISION-ENGINE §8 only; a page is published after the full threshold is met.</p>
            @forelse($schools as $s)<div class="text-[0.875rem] mb-2"><p class="flex justify-between gap-3"><span class="font-semibold">{{ $s['school'] }}</span><span>{{ $s['impressions'] }} impr · {{ $s['clicks'] }} clicks</span></p><p class="hint">{{ implode(' · ', $s['examples']) }}</p></div>@empty<p class="text-[0.875rem] text-ink-500">None.</p>@endforelse
        </section>
    </div>
    @endif
</x-layouts.admin>
