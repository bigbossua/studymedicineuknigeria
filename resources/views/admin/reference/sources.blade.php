<x-layouts.admin :seo="$seo">
    <div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-h2">Verify by source</h1><a href="{{ route('admin.reference.index') }}" class="btn btn-tertiary">← Fact-by-fact queue</a></div>
    <p class="text-ink-700 mt-1 max-w-3xl">Open one official page, read it once, then confirm every fact we took from it. Pages are in the owner's priority order (P1 UCAS Medicine deadlines, P2 UCAT, P3 GMC, P4 eligibility, P5 Nigerian qualifications, P6 fees, P7 visa, P8 graduate work, P9 application routes, P10 course availability, P12 other subjects). Facts marked source changed carry the old value and are confirmed one at a time with the page's current wording. {{ $pendingTotal }} facts are pending across {{ $sources->total() }} source pages@if($withoutSource); {{ $withoutSource }} pending facts have no source URL and can only be handled in the fact-by-fact queue@endif.</p>
    <div class="mt-6 space-y-4">
        @foreach($sources as $src)
            @php $rows = $src->facts; @endphp
            <details class="card" @if($loop->first) open @endif>
                <summary class="cursor-pointer flex flex-wrap items-center gap-3"><span class="chip chip-info">P{{ $src->tier }} · {{ $src->area }}</span><span class="chip chip-pending">{{ $src->c }} fact(s)</span>@foreach($src->statuses as $st => $n)<span class="text-[0.8125rem] text-ink-500">{{ $st }} {{ $n }}</span>@endforeach<span class="font-mono text-[0.875rem] break-all">{{ $src->source_url }}</span><a href="{{ $src->source_url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm ml-auto">Open source ↗</a></summary>
                <form method="post" action="{{ route('admin.reference.bulk') }}" class="mt-4">@csrf
                    <table class="text-[0.875rem]"><thead><tr><th><span class="sr-only">Select</span></th><th>Subject</th><th>Key</th><th>Value</th><th>Year</th><th>Status</th></tr></thead><tbody>
                    @foreach($rows as $f)
                        <tr><td>@if($f->verification_status === 'SOURCE_CHANGED')<a href="{{ route('admin.reference.index', ['status' => 'SOURCE_CHANGED', 'key' => $f->key]) }}" class="text-[0.8125rem]">Confirm alone</a>@else<input type="checkbox" name="fact_ids[]" value="{{ $f->id }}" checked class="w-4 h-4" aria-label="Select {{ $f->key }} for {{ $f->subject?->name ?? $f->subject?->title ?? 'subject' }}">@endif</td>
                            <td>{{ $f->subject?->name ?? $f->subject?->title ?? class_basename($f->subject_type).' #'.$f->subject_id }}@if($f->subject_type==='App\\Models\\Course')<span class="block text-[0.8125rem] text-ink-500">{{ $f->subject?->university?->name }}</span>@endif</td>
                            <td class="font-mono text-[0.8125rem]">{{ $f->key }}@if($f->qualification_code) · {{ $f->qualification_code }}@endif</td>
                            <td class="max-w-md">{{ $f->displayValue() ?? '—' }}@if($f->notes)<span class="block text-[0.8125rem] text-ink-500">{{ $f->notes }}</span>@endif</td>
                            <td>{{ $f->academic_year }}</td><td><x-verified-badge :status="$f->verification_status" :date="$f->verified_at?->format('j M Y')" /></td></tr>
                    @endforeach
                    </tbody></table>
                    <div class="mt-3 flex flex-wrap gap-2"><button name="decision" value="verify" class="btn btn-primary btn-sm">Selected facts: verified on this page</button><button name="decision" value="not_published" class="btn btn-secondary btn-sm">Selected facts: not published on this page</button><span class="hint self-center">Untick anything the page does not actually say; edit values in the fact-by-fact queue.</span></div>
                </form>
            </details>
        @endforeach
    </div>
    {{ $sources->links() }}
</x-layouts.admin>
