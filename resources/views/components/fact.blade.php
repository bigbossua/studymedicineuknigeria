@props(['status' => 'VERIFY-ON-PAGE', 'source' => null, 'sourceTitle' => null, 'verifiedAt' => null])
@php $show = $status === 'VERIFIED' || \App\Models\ReferenceFact::showsUnverified(); @endphp
@if($show)
<div {{ $attributes->merge(['class' => 'card']) }}>
    <div>{{ $slot }}</div>
    <div class="mt-3 flex flex-wrap items-center gap-2 text-[0.8125rem] text-ink-500">
        <x-verified-badge :status="$status" :date="$verifiedAt" />
        @if($source)<a href="{{ $source }}" rel="noopener nofollow" target="_blank">{{ $sourceTitle ?? 'Official source' }} ↗</a>@endif
    </div>
</div>
@else
<div {{ $attributes->merge(['class' => 'card border-dashed']) }}>
    <p class="text-ink-500 text-[0.9375rem]">This detail is being verified against the official source and will appear once confirmed.</p>
</div>
@endif
