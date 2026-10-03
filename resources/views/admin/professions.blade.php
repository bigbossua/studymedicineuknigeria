<x-layouts.admin :seo="$seo">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-h2">Healthcare subjects</h1><p class="text-ink-700 mt-1 text-[0.9375rem]">The course universe the platform may cover (<code>data/healthcare/subjects.json</code>, {{ $items->count() }} subjects). A subject gets a public page only when its status is PUBLISHED or later and a decision-register row justifies it; RESEARCH and REJECTED subjects stay internal. Student-facing facts live in the verification queue, never here; the sample-university values below are research labels (FACT = seen on an official page, LEAD = aggregator, NOT FOUND) and are never rendered publicly.</p></div>
        <div class="flex flex-wrap gap-2 text-[0.875rem]">@foreach($counts as $s => $c)<span class="chip {{ in_array($s, ['PUBLISHED','INDEXING','MEASURING']) ? 'chip-verified' : ($s === 'REJECTED' ? 'chip-danger' : ($s === 'VALIDATED' ? 'chip-info' : 'chip-pending')) }}">{{ $s }} {{ $c }}</span>@endforeach</div></div>
    <div class="mt-6 space-y-3">
        @foreach($items as $p)
            <details class="card" @if($p->flagship) open @endif>
                <summary class="cursor-pointer flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="font-semibold">{{ $p->name }}</span>@if($p->flagship)<span class="chip chip-info">flagship</span>@endif
                    <span class="chip {{ in_array($p->status, ['PUBLISHED','INDEXING','MEASURING']) ? 'chip-verified' : ($p->status === 'REJECTED' ? 'chip-danger' : ($p->status === 'VALIDATED' ? 'chip-info' : 'chip-pending')) }}">{{ $p->status }}</span>
                    <span class="text-[0.8125rem] text-ink-500">{{ $p->regulator }} · {{ $p->courses_count }} course record(s) · Nigeria: {{ $p->nigerian_relevance }}</span>
                </summary>
                <dl class="mt-4 grid sm:grid-cols-2 gap-x-6 gap-y-2 text-[0.875rem]">
                    <div class="sm:col-span-2"><dt class="text-ink-500">Decision reason</dt><dd>{{ $p->status_reason }}</dd></div>
                    <div><dt class="text-ink-500">Official terminology</dt><dd>{{ $p->official_terminology }}</dd></div>
                    <div><dt class="text-ink-500">Students also search</dt><dd>{{ implode(' · ', $p->alternative_names ?? []) }}</dd></div>
                    <div><dt class="text-ink-500">Registration route</dt><dd>{{ $p->register_route }}</dd></div>
                    <div><dt class="text-ink-500">Professional body</dt><dd>{{ $p->professional_body }}</dd></div>
                    <div><dt class="text-ink-500">Undergraduate entry · length</dt><dd>{{ $p->undergraduate_entry }} · {{ $p->typical_length_years }} years</dd></div>
                    <div><dt class="text-ink-500">International availability</dt><dd>{{ $p->international_availability }}</dd></div>
                    <div><dt class="text-ink-500">A-level / tariff (sample universities)</dt><dd>{{ $p->a_level_requirements }}</dd></div>
                    <div><dt class="text-ink-500">International fees (sample)</dt><dd>{{ $p->fees }}</dd></div>
                    <div><dt class="text-ink-500">English language (sample)</dt><dd>{{ $p->english_language }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-ink-500">Nigeria / WAEC statement</dt><dd>{{ $p->nigeria_statement }}</dd></div>
                    <div><dt class="text-ink-500">Admissions test · route</dt><dd>{{ $p->admissions_test }} · {{ $p->application_route }}</dd></div>
                    <div><dt class="text-ink-500">Nigerian search demand</dt><dd>{{ $p->nigerian_search_demand }}</dd></div>
                    <div><dt class="text-ink-500">Commercial intent · competition</dt><dd>{{ $p->commercial_intent }} · {{ $p->competition }}</dd></div>
                    <div><dt class="text-ink-500">Register rows · sources</dt><dd>{{ implode(', ', $p->decision_register_ids ?? []) ?: '—' }} · {{ implode('; ', $p->sources ?? []) ?: 'none yet' }}</dd></div>
                    <div><dt class="text-ink-500">Last researched</dt><dd>{{ $p->last_researched?->toDateString() }}</dd></div>
                </dl>
            </details>
        @endforeach
    </div>
</x-layouts.admin>
