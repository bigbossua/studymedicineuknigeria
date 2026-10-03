<x-layouts.admin :seo="$seo">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-h2">SEO decisions</h1><p class="text-ink-700 mt-1 text-[0.9375rem]">The decision register (<code>data/seo/decision-register.csv</code>, {{ $total }} query families): one row per search intent with its evidence, the page that answers it and the decision with its reason. Edited in the repository; <code>docs/seo/DECISION-ENGINE.md</code> explains the statuses. Semrush cells read DATA UNAVAILABLE until an export is imported.</p></div>
        <nav class="flex flex-wrap gap-2 text-[0.875rem]" aria-label="Status"><a href="{{ route('admin.seo') }}" class="chip {{ $status === '' ? 'chip-info' : 'chip-pending' }} no-underline">All {{ $total }}</a>@foreach($counts as $s => $c)<a href="{{ route('admin.seo', ['status' => $s]) }}" class="chip {{ $status === $s ? 'chip-info' : 'chip-pending' }} no-underline">{{ $s }} {{ $c }}</a>@endforeach</nav></div>
    <div class="mt-6 space-y-3">
        @forelse($rows as $r)
            <details class="card">
                <summary class="cursor-pointer flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="font-mono text-[0.8125rem] text-ink-500">{{ $r['id'] }}</span>
                    <span class="font-semibold">{{ $r['query_family'] }}</span>
                    <span class="chip {{ in_array($r['status'], ['PUBLISHED','INDEXING','MEASURING']) ? 'chip-verified' : ($r['status'] === 'REJECTED' ? 'chip-danger' : ($r['status'] === 'UPDATE' || $r['status'] === 'DRAFT' ? 'chip-info' : 'chip-pending')) }}">{{ $r['status'] }}</span>
                    <span class="text-[0.8125rem] text-ink-500">{{ $r['intent'] }} · relevance {{ $r['relevance_score'] }}/5 · Nigeria {{ $r['nigerian_relevance'] }}</span>
                    @if($r['current_url'] !== '—' && ! str_contains($r['current_url'], '{'))<a href="{{ url(strtok($r['current_url'], '#')) }}" class="text-[0.8125rem] ml-auto" target="_blank" rel="noopener">{{ $r['current_url'] }} ↗</a>@endif
                </summary>
                <dl class="mt-4 grid sm:grid-cols-2 gap-x-6 gap-y-2 text-[0.875rem]">
                    <div class="sm:col-span-2"><dt class="text-ink-500">Decision reason</dt><dd>{{ $r['status_reason'] }}</dd></div>
                    <div><dt class="text-ink-500">Example queries</dt><dd>{{ $r['example_queries'] }}</dd></div>
                    <div><dt class="text-ink-500">Observed SERP</dt><dd>{{ $r['serp_features_observed'] }}</dd></div>
                    <div><dt class="text-ink-500">Competing URLs</dt><dd class="break-words">{{ $r['competing_urls'] }}</dd></div>
                    <div><dt class="text-ink-500">Recommended page</dt><dd>{{ $r['recommended_page'] }}</dd></div>
                    <div><dt class="text-ink-500">Supporting pages (must link to it)</dt><dd>{{ $r['supporting_pages'] }}</dd></div>
                    <div><dt class="text-ink-500">Semrush ({{ $r['country_db'] }})</dt><dd>volume: {{ $r['volume'] }} · KD: {{ $r['keyword_difficulty'] }}</dd></div>
                    <div><dt class="text-ink-500">Subject · indexation</dt><dd>{{ $r['subject'] ?? 'medicine' }} · {{ $r['indexation_decision'] ?? '—' }}</dd></div>
                    <div><dt class="text-ink-500">Internal-link role · conversion role</dt><dd>{{ $r['internal_link_role'] ?? '—' }} · {{ $r['conversion_role'] ?? '—' }}</dd></div>
                    <div><dt class="text-ink-500">Sources</dt><dd>{{ $r['sources'] }}</dd></div>
                    <div><dt class="text-ink-500">Decided · review due</dt><dd>{{ $r['decided_on'] }} · {{ $r['review_due'] }}</dd></div>
                </dl>
            </details>
        @empty
            <p class="text-ink-500">No rows with this status.</p>
        @endforelse
    </div>
</x-layouts.admin>
