<x-layouts.admin :seo="$seo">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-h2">Funnel</h1><p class="text-ink-700 mt-1 text-[0.9375rem]">Server-side reporting events (architecture 12.9). Counts are unique applications or visitors; no names or application numbers are stored here.</p></div>
        <nav class="flex gap-2 text-[0.875rem]" aria-label="Period">@foreach([7,30,90,365] as $d)<a href="{{ route('admin.funnel',['days'=>$d]) }}" class="chip {{ $days===$d ? 'chip-info' : 'chip-pending' }} no-underline">{{ $d }} days</a>@endforeach</nav></div>
    <div class="mt-6 grid lg:grid-cols-3 gap-6">
        <section class="card lg:col-span-2"><p class="eyebrow mb-3">Steps · last {{ $days }} days</p>
            <table class="text-[0.875rem]"><thead><tr><th>Step</th><th class="text-right">Unique</th><th class="text-right">Events</th><th class="text-right">Of previous step</th></tr></thead><tbody>
            @foreach($rows as $r)<tr><td class="font-mono">{{ $r['name'] }}</td><td class="text-right font-semibold">{{ $r['unique'] }}</td><td class="text-right text-ink-500">{{ $r['events'] }}</td><td class="text-right">{{ $r['of_previous'] !== null ? $r['of_previous'].'%' : '—' }}</td></tr>@endforeach
            </tbody></table>
            @if($other->isNotEmpty())<p class="eyebrow mt-6 mb-2">Other events</p><ul class="text-[0.875rem] space-y-1">@foreach($other as $o)<li class="flex justify-between"><span class="font-mono">{{ $o->name }}</span><span>{{ $o->c }}</span></li>@endforeach</ul>@endif
        </section>
        <div class="space-y-6">
            <section class="card"><p class="eyebrow mb-3">By service tier</p>
                @forelse($byTier as $tier => $events)<p class="text-[0.875rem]"><span class="font-semibold">{{ $tier }}</span> · @foreach($events as $e){{ str_replace('_',' ',$e->name) }} {{ $e->c }}@if(!$loop->last) · @endif @endforeach</p>@empty<p class="text-[0.875rem] text-ink-500">No application events in this period.</p>@endforelse
            </section>
            <section class="card"><p class="eyebrow mb-3">Lead sources (utm_source)</p>
                @forelse($sources as $s)<p class="text-[0.875rem] flex justify-between"><span>{{ $s->src }}</span><span class="font-semibold">{{ $s->c }}</span></p>@empty<p class="text-[0.875rem] text-ink-500">No leads in this period.</p>@endforelse
            </section>
            <section class="card"><p class="eyebrow mb-2">Third-party analytics</p><p class="text-[0.875rem] text-ink-700">@if(config('site.ga4_id'))GA4 <span class="font-mono">{{ config('site.ga4_id') }}</span> loads on public pages after consent only.@else Not configured (SITE_GA4_ID blank). Public pages set no analytics cookies.@endif</p><p class="hint mt-2">{{ number_format($total) }} events stored in total.</p></section>
        </div>
    </div>
</x-layouts.admin>
