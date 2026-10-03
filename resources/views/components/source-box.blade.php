@props(['url' => null, 'organisation' => null, 'title' => null, 'verifiedAt' => null, 'status' => 'VERIFY-ON-PAGE'])
<div {{ $attributes->merge(['class' => 'source-box']) }}>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <span class="eyebrow text-navy-700">Official source</span>
            @if($url)
                · <a href="{{ $url }}" rel="noopener nofollow" target="_blank" class="font-medium">{{ $title ?? $organisation ?? parse_url($url, PHP_URL_HOST) }}<span class="sr-only"> (opens in a new tab)</span> ↗</a>
            @elseif($organisation)
                · <span class="font-medium">{{ $organisation }}</span>
            @endif
        </div>
        <x-verified-badge :status="$status" :date="$verifiedAt" />
    </div>
    @if(trim($slot))<div class="mt-1.5">{{ $slot }}</div>@endif
</div>
