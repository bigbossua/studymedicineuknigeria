<x-layouts.portal :seo="$seo" :application="$application">
    <div class="max-w-2xl">
        <a href="{{ route('portal.documents.index', $application) }}" class="text-[0.875rem]">← All documents</a>
        <div class="flex items-start justify-between gap-3 mt-3"><h1 class="text-h2">{{ $document->title }}</h1><span class="chip {{ $document->status->chipClass() }} shrink-0 mt-2">{{ $document->status->label() }}</span></div>
        <p class="text-ink-700 mt-2">{{ $meta['why'] }}</p>
        @if($document->staff_note && $document->status->needsStudent())<x-alert type="danger" class="mt-4" title="Please replace this document">{{ $document->staff_note }}</x-alert>@endif
        @if($document->status->value !== 'NOT_REQUIRED' && $document->status->value !== 'ACCEPTED')
            <form method="post" action="{{ route('portal.documents.upload', [$application, $document]) }}" enctype="multipart/form-data" class="card mt-6 space-y-4">
                @csrf
                <div class="field"><label for="file" class="label">Choose a file</label>
                    <input id="file" name="file" type="file" required accept="{{ collect($meta['formats'])->map(fn($f)=>'.'.$f)->join(',') }}" capture="environment" class="input py-3">
                    <p class="hint">Accepted: {{ strtoupper(implode(', ', $meta['formats'])) }}. Up to 10 MB. All pages, every corner visible, no glare. On a phone you can photograph it directly.</p>
                    @error('file')<p class="error-text">{{ $message }}</p>@enderror</div>
                <button class="btn btn-primary">Upload</button>
            </form>
        @endif
        @if($document->versions->isNotEmpty())
            <section class="mt-8"><h2 class="eyebrow">History</h2>
                <ul class="mt-3 divide-y divide-ink-100 text-[0.9375rem]">
                    @foreach($document->versions as $v)<li class="py-2 flex justify-between gap-3"><span>v{{ $v->version }} · {{ $v->original_filename }} · {{ round($v->size_bytes/1024) }} KB</span><span class="text-ink-500 text-[0.8125rem]">{{ $v->uploaded_at->format('j M Y H:i') }} · <a href="{{ route('portal.documents.download', [$application, $document, $v]) }}">download</a></span></li>@endforeach
                </ul></section>
        @endif
        <section class="mt-8"><h2 class="eyebrow">Status history</h2>
            <ul class="mt-3 text-[0.875rem] text-ink-500 space-y-1">@foreach($document->events as $e)<li>{{ $e->created_at->format('j M Y H:i') }} — {{ \App\Enums\DocumentStatus::from($e->to_status)->label() }}@if($e->reason) · {{ $e->reason }}@endif</li>@endforeach</ul></section>
    </div>
</x-layouts.portal>
