@props(['fact' => null, 'label', 'suffix' => ''])
<div {{ $attributes->merge(['class' => 'py-3 border-b border-ink-100 last:border-0 grid sm:grid-cols-12 gap-2 sm:gap-4']) }}>
    <dt class="sm:col-span-4 text-[0.9375rem] text-ink-500">{{ $label }}</dt>
    <dd class="sm:col-span-8">
        @if($fact && $fact->isPublishable() && $fact->displayValue() !== null)
            <p class="font-medium">{{ $fact->displayValue() }}{{ $suffix }}</p>
        @elseif($fact && in_array($fact->verification_status, ['NOT_FOUND','NOT_PUBLISHED']))
            <p class="text-ink-500">Not yet confirmed from the official source.</p>
        @else
            <p class="text-ink-500">Being verified against the official source.</p>
        @endif
        <div class="mt-1 flex flex-wrap items-center gap-2 text-[0.8125rem]">
            @if($fact)<x-verified-badge :status="$fact->verification_status" :date="$fact->verified_at?->format('j M Y')" />
            @if($fact->source_url)<a href="{{ $fact->source_url }}" rel="noopener nofollow" target="_blank" class="text-ink-500">Official source ↗</a>@endif @endif
        </div>
    </dd>
</div>
