@props(['status' => 'VERIFY-ON-PAGE', 'source' => null, 'sourceTitle' => null, 'verifiedAt' => null])
{{-- A statement that is not verified renders nothing where unverified facts are hidden (production): lists filter those
     out before counting, so no placeholder card stands in for a hidden record. --}}
@php $show = $status === 'VERIFIED' || \App\Models\ReferenceFact::showsUnverified(); @endphp
@if($show)
<div {{ $attributes->merge(['class' => 'card']) }}>
    <div>{{ $slot }}</div>
    <div class="mt-3 flex flex-wrap items-center gap-2 text-[0.8125rem] text-ink-500">
        <x-verified-badge :status="$status" :date="$verifiedAt" />
        @if($source)<a href="{{ $source }}" rel="noopener nofollow" target="_blank">{{ $sourceTitle ?? 'Official source' }} ↗</a>@endif
    </div>
</div>
@endif
