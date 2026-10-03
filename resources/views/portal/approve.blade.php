<x-layouts.portal :seo="$seo" :application="$application">
    <div class="max-w-3xl">
        <p class="eyebrow">Student approval required</p>
        <h1 class="text-h2 mt-1">Review and approve your application</h1>
        <p class="text-ink-700 mt-2">Nothing is submitted to any university until you approve this exact package. Check every detail. If anything is wrong, request changes instead of approving.</p>

        <section class="card mt-8"><p class="eyebrow mb-3">Where this application will go</p>
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-[0.9375rem]">
                <div><dt class="text-ink-500">University</dt><dd class="font-semibold">{{ $submission->university?->name ?? '—' }}</dd></div>
                <div><dt class="text-ink-500">Course</dt><dd class="font-semibold">{{ $submission->course?->title ?? 'Medicine' }}{{ $submission->course?->ucas_code ? ' ('.$submission->course->ucas_code.')' : '' }}</dd></div>
                <div><dt class="text-ink-500">Intake</dt><dd class="font-semibold">{{ $submission->intake ?? $application->intake_year.' entry' }}</dd></div>
                <div><dt class="text-ink-500">Who presses submit</dt><dd class="font-semibold">{{ $submission->routeLabel() }}</dd></div>
            </dl>
            @if($submission->choices->isNotEmpty())<p class="mt-4 text-[0.9375rem]"><span class="text-ink-500">UCAS choices:</span> {{ $submission->choices->map(fn($c)=>$c->label ?? ($c->course?->university?->name.' — '.$c->course?->title))->join('; ') }}</p>@endif
        </section>

        <section class="card mt-5"><p class="eyebrow mb-3">Your information</p>
            @foreach(\App\Services\Applications\FormSteps::all() as $key => $meta)
                @php $sec = $package['form'][$key] ?? []; @endphp
                <details class="border-t border-ink-100 py-3 first:border-0"><summary class="cursor-pointer font-medium">{{ $meta['title'] }}</summary>
                    <pre class="mt-2 text-[0.8125rem] whitespace-pre-wrap font-sans text-ink-700">{{ json_encode(collect($sec)->except(['_token'])->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>
            @endforeach
        </section>

        <section class="card mt-5"><p class="eyebrow mb-3">Documents included ({{ $docs->count() }})</p>
            <ul class="divide-y divide-ink-100 text-[0.9375rem]">@foreach($docs as $d)<li class="py-2 flex justify-between gap-3"><span>✓ {{ $d->title }}</span><span class="text-ink-500 text-[0.8125rem]">v{{ $d->currentVersion?->version }} · {{ substr($d->currentVersion?->sha256 ?? '', 0, 12) }}</span></li>@endforeach</ul>
        </section>

        <section class="card mt-5"><p class="eyebrow mb-3">Service and fees</p>
            <p class="text-[0.9375rem]">Service: <span class="font-semibold">{{ $package['tier'] }}</span>. Payments received: {{ count($package['payments']) ? implode(', ', $package['payments']) : 'none yet' }}. University tuition and application fees are separate and paid by you to the university.</p>
        </section>

        <form method="post" action="{{ route('portal.approve.store', $application) }}" class="card-raised mt-8 space-y-4 border-l-4 border-l-accent-600">
            @csrf<input type="hidden" name="hash" value="{{ $hash }}">
            <p class="eyebrow">Declaration ({{ \App\Models\Authorisation::DECLARATION_VERSION }})</p>
            <p class="text-[0.9375rem] text-ink-900">{{ $declaration }}</p>
            <label class="flex items-start gap-3 text-[0.9375rem]"><input type="checkbox" name="confirm" value="1" required class="mt-1 w-4 h-4"> I have read the declaration and I approve this application package exactly as shown above.</label>
            <div class="field"><label for="typed_name" class="label">Type your full name to sign</label><input id="typed_name" name="typed_name" required class="input" autocomplete="off" placeholder="{{ auth()->user()->name }}"></div>
            <p class="text-[0.8125rem] text-ink-500">We record the date, time, your name as typed, your IP address and a fingerprint of this package. If anything in the package changes afterwards, this approval is automatically cancelled and you will be asked again.</p>
            <button class="btn btn-primary btn-lg">Approve and authorise</button>
        </form>

        <details class="mt-6"><summary class="cursor-pointer text-[0.9375rem]">Something is wrong — request changes instead</summary>
            <form method="post" action="{{ route('portal.approve.changes', $application) }}" class="card mt-3 space-y-3">@csrf
                <div class="field"><label for="note" class="label">What needs to change?</label><textarea id="note" name="note" rows="4" required class="input min-h-24"></textarea></div>
                <button class="btn btn-secondary">Send to our team</button></form></details>
    </div>
</x-layouts.portal>
