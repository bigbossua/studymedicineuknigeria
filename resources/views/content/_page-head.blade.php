{{-- Shared article header: a full-width warm band with the applicant journey, eyebrow, h1, lede and reviewed line.
     Included inside <article class="container-site pt-6">; the band bleeds to the viewport edges. --}}
@props(['eyebrow' => null, 'title', 'lede' => null, 'seo', 'icon' => null])
@php
    $step = \App\Support\MedicineRoute::currentKey(request()->route()?->getName());
    $icon ??= match ($step) {
        'course' => 'stethoscope', 'requirements' => 'clipboard-check', 'qualifications' => 'graduation-cap', 'universities' => 'school',
        'fees' => 'pound-sterling', 'application' => 'calendar-days', 'career' => 'heart-pulse', 'eligibility' => 'compass', 'apply' => 'file-text',
        default => null,
    };
@endphp
<header class="bleed -mt-6 bg-paper-warm border-b border-ink-200 pt-8 pb-10 sm:pt-10 sm:pb-14">
    @if($step)
        <x-journey :current="$step" class="mb-8 hidden md:block" />
    @endif
    <div @class(['grid lg:grid-cols-12 gap-10 items-center' => ! empty($glance)])>
    <div @class(['max-w-3xl', 'lg:col-span-8' => ! empty($glance)])>
        @if($eyebrow)
            <p class="eyebrow mb-4 flex items-center gap-2">@if($icon)<x-icon :name="$icon" :size="16" class="text-navy-500" />@endif{{ $eyebrow }}</p>
        @endif
        <h1 class="text-balance">{{ $title }}</h1>
        @if($lede)<p class="lede mt-5 text-pretty">{{ $lede }}</p>@endif
        <x-reviewed :date="$seo->lastReviewed" :intake="$seo->intakeYear" class="mt-5" />
    </div>
    @if(! empty($glance))
        {{-- at a glance: figures this page itself states, never new claims --}}
        <aside class="lg:col-span-4 card-raised" aria-label="At a glance">
            <p class="eyebrow mb-4">At a glance</p>
            <dl class="grid grid-cols-2 gap-x-5 gap-y-5">
                @foreach($glance as [$value, $text])
                    <div><dt class="sr-only">{{ $text }}</dt><dd class="font-serif font-semibold text-3xl text-navy-700 leading-none">{{ $value }}</dd><dd class="mt-1.5 text-[0.8125rem] text-ink-700 leading-snug">{{ $text }}</dd></div>
                @endforeach
            </dl>
        </aside>
    @endif
    </div>
</header>
