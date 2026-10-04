<x-layouts.admin :seo="$seo">
    <div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-h2">Verification queue</h1><a href="{{ route('admin.reference.sources') }}" class="btn btn-secondary">Verify by source page →</a></div>
    <p class="text-ink-700 mt-1 max-w-3xl">Open the official source, confirm the value on the page, then mark it verified. Verified fees and deadlines fall due for review after 6 months, other facts after 12. Nothing unverified is shown to students in production.</p>
    <div class="mt-4 flex flex-wrap gap-2 text-[0.875rem]">@foreach($counts as $s=>$c)<a href="{{ route('admin.reference.index',['status'=>$s]) }}" class="chip {{ $s===$status ? 'chip-info' : 'chip-pending' }}">{{ $s }} · {{ $c }}</a>@endforeach</div>
    <form class="mt-3 flex gap-2"><input type="hidden" name="status" value="{{ $status }}"><select name="key" class="input max-w-xs" aria-label="Filter by fact key"><option value="">All keys</option>@foreach($keys as $k)<option value="{{ $k }}" @selected(request('key')===$k)>{{ $k }}</option>@endforeach</select><button class="btn btn-secondary">Filter</button></form>
    <div class="mt-5 space-y-3">
        @foreach($facts as $f)
            <form method="post" action="{{ route('admin.reference.update',$f) }}" class="card grid lg:grid-cols-12 gap-3 items-start">@csrf
                <div class="lg:col-span-3"><p class="font-semibold">{{ $f->subject?->name ?? $f->subject?->title ?? class_basename($f->subject_type).' #'.$f->subject_id }}</p><p class="text-[0.8125rem] text-ink-500">{{ $f->subject_type==='App\\Models\\Course' ? $f->subject?->university?->name : '' }}</p><p class="font-mono text-[0.8125rem] mt-1">{{ $f->key }} @if($f->qualification_code)· {{ $f->qualification_code }}@endif</p><x-verified-badge :status="$f->verification_status" :date="$f->verified_at?->format('j M Y')" class="mt-1" />
                    @if($f->history->isNotEmpty())<details class="mt-2 text-[0.8125rem] text-ink-700"><summary class="cursor-pointer text-ink-500">History ({{ $f->history->count() }}{{ $f->history->count() === 5 ? '+' : '' }})</summary>
                        <ul class="mt-1 space-y-1">@foreach($f->history as $c)<li><span class="text-ink-500">{{ $c->created_at?->format('j M Y H:i') }} · {{ $c->via }}{{ $c->user ? ' ('.$c->user->name.')' : '' }}</span>@foreach($c->after as $field => $new)<br><span class="font-mono">{{ $field }}</span>: {{ \Illuminate\Support\Str::limit((string) json_encode($c->before[$field] ?? null), 60) }} → {{ \Illuminate\Support\Str::limit((string) json_encode($new), 60) }}@endforeach</li>@endforeach</ul>
                    </details>@endif</div>
                <div class="lg:col-span-5 space-y-2">
                    @if($f->value_number !== null || str_ends_with($f->key,'_gbp'))<input name="value_number" value="{{ $f->value_number }}" class="input" placeholder="Number" aria-label="Value (number)">@else<textarea name="value_text" class="input min-h-16" aria-label="Value (text)">{{ $f->value_text ?? ($f->value_bool===null ? '' : ($f->value_bool?'Yes':'No')) }}</textarea>@endif
                    <input name="academic_year" value="{{ $f->academic_year }}" class="input" placeholder="Academic year (e.g. 2026/27)" aria-label="Academic year">
                    <input name="source_url" value="{{ $f->source_url }}" class="input" placeholder="Official source URL" aria-label="Official source URL">
                    @if($f->source_url)<a href="{{ $f->source_url }}" target="_blank" rel="noopener" class="text-[0.8125rem]">Open source ↗</a>@endif
                </div>
                <div class="lg:col-span-4 space-y-2"><textarea name="notes" class="input min-h-16" placeholder="Notes" aria-label="Notes">{{ $f->notes }}</textarea>
                    <div class="flex flex-wrap gap-2"><button name="decision" value="verify" class="btn btn-primary btn-sm">Verified on page</button><button name="decision" value="not_published" class="btn btn-secondary btn-sm">Not published</button><button name="decision" value="source_changed" class="btn btn-secondary btn-sm">Source changed</button><button name="decision" value="save" class="btn btn-tertiary">Save edits</button><button name="decision" value="archive" class="btn btn-tertiary text-danger-600">Archive</button></div></div>
            </form>
        @endforeach
    </div>
    {{ $facts->links() }}
</x-layouts.admin>
