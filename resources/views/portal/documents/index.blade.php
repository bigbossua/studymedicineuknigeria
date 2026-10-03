<x-layouts.portal :seo="$seo" :application="$application">
    <h1 class="text-h2">Your documents</h1>
    <p class="text-ink-700 mt-2 max-w-2xl">Only documents that apply to your answers are listed. Upload a clear scan or photo; each file is checked and either accepted or returned with a reason. Please do not send documents by email or WhatsApp.</p>
    @foreach($groups as $title => $docs)
        @if($docs->isNotEmpty())
            <section class="mt-8" aria-labelledby="g-{{ Str::slug($title) }}">
                <h2 id="g-{{ Str::slug($title) }}" class="eyebrow">{{ $title }} ({{ $docs->count() }})</h2>
                <ul class="mt-3 grid gap-3 md:grid-cols-2">
                    @foreach($docs as $d)
                        <li class="card flex flex-col gap-2">
                            <div class="flex items-start justify-between gap-3"><a href="{{ route('portal.documents.show', [$application, $d]) }}" class="font-semibold no-underline text-ink-900 hover:underline">{{ $d->title }}</a><span class="chip {{ $d->status->chipClass() }} shrink-0">{{ $d->status->label() }}</span></div>
                            <p class="text-[0.875rem] text-ink-500">{{ $d->required_reason }}</p>
                            @if($d->staff_note && $d->status->needsStudent())<p class="text-[0.875rem] text-danger-600"><span class="font-semibold">Our note:</span> {{ $d->staff_note }}</p>@endif
                            @if($d->currentVersion)<p class="text-[0.8125rem] text-ink-500">{{ $d->currentVersion->original_filename }} · v{{ $d->currentVersion->version }} · {{ $d->currentVersion->uploaded_at->format('j M Y') }}</p>@endif
                            <div class="mt-1"><a href="{{ route('portal.documents.show', [$application, $d]) }}" class="btn {{ $d->status->needsStudent() ? 'btn-primary btn-sm' : 'btn-tertiary' }}">{{ $d->status->needsStudent() ? ($d->currentVersion ? 'Replace document' : 'Upload now') : 'View' }}</a></div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endforeach
</x-layouts.portal>
