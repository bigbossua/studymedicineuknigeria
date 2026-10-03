<x-layouts.portal :seo="$seo" :application="$application">
    <h1 class="text-h2">University submissions</h1>
    <p class="text-ink-700 mt-2 max-w-2xl">Each target university or UCAS application is tracked here from proposal to the university's response.</p>
    @forelse($submissions as $s)
        <section class="card mt-6">
            <div class="flex items-start justify-between gap-3 flex-wrap"><div><h2 class="text-xl font-serif font-semibold">{{ $s->university?->name ?? 'UCAS application' }}</h2><p class="text-[0.9375rem] text-ink-500">{{ $s->course?->title ?? 'Medicine' }} · {{ $s->intake ?? $application->intake_year.' entry' }}</p></div><span class="chip chip-info">{{ $s->statusLabel() }}</span></div>
            <p class="mt-3 text-[0.9375rem]"><span class="text-ink-500">Route:</span> {{ $s->routeLabel() }}</p>
            @if($s->authorisation)<p class="text-[0.9375rem]"><span class="text-ink-500">Your approval:</span> {{ $s->authorisation->approved_at->format('j F Y, H:i') }} (signed "{{ $s->authorisation->typed_name }}")</p>@endif
            @if($s->external_reference)<p class="text-[0.9375rem]"><span class="text-ink-500">Reference:</span> <span class="font-mono">{{ $s->external_reference }}</span></p>@endif
            @if(in_array($s->route_code,['UCAS_STUDENT','DIRECT_PORTAL_STUDENT']) && in_array($s->status,['AUTHORISED','PACKAGE_READY']))
                <form method="post" action="{{ route('portal.submissions.recorded', [$application, $s]) }}" class="mt-4 card bg-navy-50 grid sm:grid-cols-3 gap-3 items-end">@csrf
                    <div class="field sm:col-span-3"><p class="font-semibold text-[0.9375rem]">Once you have submitted through {{ $s->route_code==='UCAS_STUDENT' ? 'UCAS' : 'the university\'s form' }}, record it here</p></div>
                    <div class="field"><label class="label" for="ref-{{ $s->id }}">{{ $s->route_code==='UCAS_STUDENT' ? 'UCAS Personal ID' : 'University reference' }}</label><input id="ref-{{ $s->id }}" name="external_reference" required class="input"></div>
                    <div class="field"><label class="label" for="date-{{ $s->id }}">Date submitted</label><input id="date-{{ $s->id }}" name="submitted_on" type="date" required class="input"></div>
                    <button class="btn btn-secondary">Record submission</button></form>
            @endif
            <ol class="mt-4 text-[0.875rem] text-ink-500 space-y-1">@foreach($s->events as $e)<li>{{ $e->created_at->format('j M Y H:i') }} — {{ $e->to_status }}@if($e->note) · {{ $e->note }}@endif</li>@endforeach</ol>
        </section>
    @empty
        <x-alert type="info" class="mt-6">No submission target has been proposed yet. After your documents are complete and reviewed, our team will propose the university and route and ask for your approval.</x-alert>
    @endforelse
</x-layouts.portal>
