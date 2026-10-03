{{-- shared article header: eyebrow, h1, lede, reviewed line --}}
@props(['eyebrow' => null, 'title', 'lede' => null, 'seo'])
<header class="max-w-3xl">
    @if($eyebrow)<p class="eyebrow mb-3">{{ $eyebrow }}</p>@endif
    <h1 class="text-balance">{{ $title }}</h1>
    @if($lede)<p class="lede mt-5">{{ $lede }}</p>@endif
    <x-reviewed :date="$seo->lastReviewed" :intake="$seo->intakeYear" class="mt-4" />
</header>
