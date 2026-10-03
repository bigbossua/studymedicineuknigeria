@props(['title' => null, 'href' => null, 'label' => 'Apply Online', 'secondaryHref' => null, 'secondaryLabel' => null])
<section {{ $attributes->merge(['class' => 'cta-band']) }} aria-label="Next step">
    <div>
        @if($title)<p class="font-serif text-2xl sm:text-3xl font-semibold text-white text-balance">{{ $title }}</p>@endif
        @if(trim($slot))<p class="mt-2 max-w-prose">{{ $slot }}</p>@endif
    </div>
    <div class="flex flex-col sm:flex-row gap-3 shrink-0">
        <a href="{{ $href ?? route('apply.index') }}" class="btn btn-primary btn-lg">{{ $label }}</a>
        @if($secondaryHref)<a href="{{ $secondaryHref }}" class="btn btn-lg border border-white/60 text-white hover:bg-white/10">{{ $secondaryLabel }}</a>@endif
    </div>
</section>
