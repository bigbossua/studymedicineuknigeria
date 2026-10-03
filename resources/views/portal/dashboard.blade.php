<x-layouts.portal :seo="$seo" :application="$application">
    @php $a = $application; $stage = $a->stage; @endphp
    <div class="grid lg:grid-cols-12 gap-8">
        <div class="lg:col-span-8 space-y-6">
            <div>
                <p class="eyebrow">Welcome back, {{ Str::of(auth()->user()->name)->explode(' ')->first() }}</p>
                <h1 class="text-h2 mt-1">{{ $a->tier?->name ?? 'Medical application' }} <span class="text-ink-500 font-sans text-base font-normal">· {{ $a->intake_year }} entry · <span class="font-mono">{{ $a->application_number }}</span></span></h1>
            </div>

            <section class="card-raised border-l-4 {{ $next['kind']==='primary' ? 'border-l-accent-600' : 'border-l-navy-700' }}" aria-labelledby="next-action">
                <p id="next-action" class="eyebrow">Next action</p>
                <p class="mt-2 text-xl font-serif font-semibold text-balance">{{ $next['label'] }}</p>
                @if($next['url'])<a href="{{ $next['url'] }}" class="btn {{ $next['kind']==='primary' ? 'btn-primary' : 'btn-secondary' }} mt-4">{{ $next['kind']==='primary' ? 'Continue' : 'View' }}</a>@endif
            </section>

            <section class="card">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-4">
                        <div class="relative w-16 h-16 shrink-0" role="img" aria-label="{{ $a->completion_pct }} per cent complete">
                            <svg viewBox="0 0 36 36" class="w-16 h-16 -rotate-90"><circle cx="18" cy="18" r="15.9" fill="none" stroke="#EEF2F5" stroke-width="3.5"/><circle cx="18" cy="18" r="15.9" fill="none" stroke="#0B3D5C" stroke-width="3.5" stroke-dasharray="{{ $a->completion_pct }} 100" stroke-linecap="round"/></svg>
                            <span class="absolute inset-0 flex items-center justify-center font-semibold text-[0.9375rem]">{{ $a->completion_pct }}%</span>
                        </div>
                        <div><p class="font-semibold">{{ $a->completion_pct }}% complete</p><p class="text-[0.875rem] text-ink-500">{{ $stage->label() }}</p></div>
                    </div>
                    <a href="{{ route('portal.application.index', $a) }}" class="btn btn-tertiary">Open application</a>
                </div>
                <ul class="mt-6 divide-y divide-ink-100">
                    @foreach($steps as $key => $meta)
                        <li class="py-2.5 flex items-center justify-between gap-3 text-[0.9375rem]">
                            <a href="{{ route('portal.application.step', [$a, $key]) }}" class="no-underline text-ink-900 hover:underline">{{ $meta['title'] }}</a>
                            @if($a->sectionComplete($key))<span class="chip chip-verified">Complete</span>@else<span class="chip chip-review">To do</span>@endif
                        </li>
                    @endforeach
                    @foreach($a->documents->filter(fn($d)=>$d->status->value!=='NOT_REQUIRED') as $d)
                        <li class="py-2.5 flex items-center justify-between gap-3 text-[0.9375rem]">
                            <a href="{{ route('portal.documents.show', [$a, $d]) }}" class="no-underline text-ink-900 hover:underline">{{ $d->title }}</a>
                            <span class="chip {{ $d->status->chipClass() }}">{{ $d->status->label() }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card">
                <p class="eyebrow mb-3">Recent updates</p>
                <ol class="space-y-2 text-[0.9375rem]">
                    @forelse($a->events->take(6) as $e)<li class="flex justify-between gap-4"><span>{{ $e->humanLabel() }}</span><time class="text-ink-500 text-[0.8125rem] shrink-0" datetime="{{ $e->created_at->toIso8601String() }}">{{ $e->created_at->diffForHumans() }}</time></li>@empty<li class="text-ink-500">No activity yet.</li>@endforelse
                </ol>
            </section>
        </div>
        <aside class="lg:col-span-4 space-y-5">
            <div class="card">
                <p class="eyebrow mb-3">Key dates</p>
                <ul class="text-[0.9375rem] space-y-2">
                    @php $deadline = \Carbon\Carbon::create($a->intake_year - 1, 10, 15, 18, 0, 0, 'Europe/London'); @endphp
                    <li class="flex justify-between gap-3"><span>UCAS medicine deadline ({{ $a->intake_year }} entry)</span><span class="font-medium text-right">{{ $deadline->format('j M Y') }}<br><span class="text-[0.8125rem] text-ink-500">{{ $deadline->isPast() ? 'passed' : (int) floor(now()->diffInDays($deadline, true)).' days left' }}</span></span></li>
                    <li class="text-[0.8125rem] text-ink-500">UCAT for {{ $a->intake_year }} entry is sat July–September {{ $a->intake_year - 1 }}. Exact dates are published by the UCAT Consortium each spring.</li>
                </ul>
            </div>
            <div class="card">
                <p class="eyebrow mb-3">Help</p>
                <a href="{{ route('portal.messages.index', $a) }}" class="btn btn-secondary w-full">Message our team</a>
                @if(config('site.whatsapp'))<a href="https://wa.me/{{ config('site.whatsapp') }}?text={{ urlencode('Hello, my application number is '.$a->application_number) }}" class="btn btn-tertiary mt-3 w-full" rel="noopener" target="_blank">WhatsApp (quote {{ $a->application_number }})</a>@endif
                <p class="mt-3 text-[0.8125rem] text-ink-500">Please never send documents by WhatsApp or email; upload them here so they stay protected.</p>
            </div>
            <div class="card">
                <p class="eyebrow mb-3">Payment</p>
                @php $paid = $a->payments->where('status','SUCCEEDED')->sum('amount_minor'); @endphp
                <p class="text-[0.9375rem]">{{ $paid ? 'Paid: £'.number_format($paid/100, 2) : 'No payment recorded yet.' }}</p>
                <a href="{{ route('portal.payments.index', $a) }}" class="btn btn-tertiary mt-2">Payments</a>
            </div>
        </aside>
    </div>
</x-layouts.portal>
