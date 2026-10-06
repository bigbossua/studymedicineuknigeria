@php
    $seo = $seo ?? \App\Support\Seo::make(config('site.name'));
    $currentRoute = request()->route()?->getName();
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo', ['seo' => $seo])
    <link rel="icon" href="/favicon.ico" sizes="48x48">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#0B3D5C">
    <link rel="preload" href="/fonts/SourceSerif4-normal-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/Inter-normal-latin.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.analytics')
    @stack('head')
</head>
<body class="min-h-screen flex flex-col {{ $bodyClass ?? '' }}">
<a href="#main" class="sr-only-focusable fixed top-2 left-2 z-50 bg-navy-700 text-white px-3 py-2 rounded-sm">Skip to content</a>

<aside class="hidden md:block bg-navy-900 text-white/80 text-[0.8125rem]" aria-label="About this service">
    <div class="container-site flex items-center justify-between gap-6 h-9">
        <p class="flex items-center gap-2"><x-icon name="shield-check" :size="15" class="text-gold-400" />Independent application support for Nigerian applicants · not an agent of any university</p>
        <p class="flex items-center gap-5"><a href="{{ route('verify') }}" class="text-white/80 no-underline hover:text-white">How we verify</a><a href="mailto:{{ config('site.email') }}" class="text-white/80 no-underline hover:text-white">{{ config('site.email') }}</a></p>
    </div>
</aside>
<header class="sticky top-0 z-40 border-b border-ink-200 bg-paper">
    <div class="container-site flex items-center justify-between gap-4 h-[72px]">
        <a href="{{ route('home') }}" class="flex items-center no-underline shrink-0" aria-label="{{ config('site.name') }} — home">
            <img src="/images/brand/smukn-logo-horizontal.svg" alt="{{ config('site.name') }}" width="286" height="44" class="h-10 sm:h-11 w-auto" fetchpriority="high">
        </a>
        <nav class="hidden lg:flex items-center gap-1" aria-label="Primary">
            @foreach(config('site.nav') as $item)
                @php $href = \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#'; $active = $currentRoute && str_starts_with($currentRoute, explode('.', $item['route'])[0]); @endphp
                <a href="{{ $href }}" class="nav-link px-3 rounded-sm hover:bg-navy-50" @if($active) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
        </nav>
        <div class="flex items-center gap-3">
            <a href="{{ route('apply.index') }}" class="btn btn-primary hidden sm:inline-flex">Apply Online</a>
            <button type="button" class="lg:hidden inline-flex items-center justify-center w-11 h-11 rounded-sm border border-ink-300 text-ink-900" data-nav-toggle aria-expanded="false" aria-controls="mobile-nav" aria-label="Open menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>
    <div id="mobile-nav" class="lg:hidden fixed inset-0 top-[72px] z-40 bg-paper border-t border-ink-200 overflow-y-auto" data-nav-panel hidden>
        <nav class="container-site py-4 flex flex-col" aria-label="Primary mobile">
            @foreach(config('site.nav') as $item)
                @php $href = \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#'; @endphp
                <a href="{{ $href }}" class="nav-link text-lg py-3 border-b border-ink-100 flex items-center justify-between">{{ $item['label'] }}<x-icon name="chevron-right" :size="18" class="text-ink-300" /></a>
            @endforeach
            <a href="{{ route('apply.index') }}" class="btn btn-primary btn-lg mt-5">Apply Online</a>
            <a href="{{ route('apply.eligibility') }}" class="btn btn-secondary btn-lg mt-3">Check your eligibility</a>
            <p class="hint mt-6 flex items-center gap-2"><x-icon name="shield-check" :size="15" />Independent · not an agent of any university</p>
            <p class="hint mt-2">{{ config('site.email') }}</p>
        </nav>
    </div>
</header>

<main id="main" class="flex-1">
    @isset($seo)
        @if($seo->breadcrumbs)
            <div class="bg-paper-warm">
                <div class="container-site pt-5">
                    <x-breadcrumbs :items="$seo->breadcrumbs" />
                </div>
            </div>
        @endif
    @endisset
    {{ $slot ?? '' }}
    @yield('content')
</main>

<footer class="mt-24 bg-navy-950 text-white/75 text-[0.9375rem]">
    <div class="border-b border-white/10">
        <div class="container-site py-10 grid gap-6 md:grid-cols-12 md:items-center">
            <div class="md:col-span-7">
                <p class="eyebrow text-gold-400! mb-2">Your route to UK Medicine</p>
                <ol class="flex flex-wrap gap-x-5 gap-y-2 text-white/85">
                    @foreach(\App\Support\MedicineRoute::steps() as $i => $step)
                        <li class="flex items-center gap-2"><span class="font-sans font-semibold tabular-nums tracking-wide text-[0.75rem] text-white/65">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span><a href="{{ route($step['route']) }}" class="text-white/85 no-underline hover:text-white hover:underline">{{ $step['label'] }}</a></li>
                    @endforeach
                </ol>
            </div>
            <div class="md:col-span-5 flex flex-col sm:flex-row md:justify-end gap-3">
                <a href="{{ route('apply.eligibility') }}" class="btn btn-lg border border-white/40 text-white hover:bg-white/10">Check your eligibility</a>
                <a href="{{ route('apply.index') }}" class="btn btn-primary btn-lg">Apply Online</a>
            </div>
        </div>
    </div>
    <div class="container-site py-14 grid gap-10 md:grid-cols-12">
        <div class="md:col-span-4">
            <img src="/images/brand/smukn-logo-horizontal-reverse.svg" alt="" width="286" height="44" class="h-10 w-auto" loading="lazy">
            <p class="mt-5 max-w-prose">{{ config('site.default_description') }}</p>
            <p class="mt-4 text-[0.875rem] text-white/55">{{ config('site.status_statement') }}</p>
        </div>
        <div class="md:col-span-8 grid grid-cols-2 sm:grid-cols-3 gap-8 md:pl-8">
            @foreach([
                'Understand' => [[route('medicine.index'), 'Study Medicine in the UK'], [route('medicine.nigeria'), 'Applying from Nigeria'], [route('requirements.index'), 'Requirements'], [route('fees.index'), 'Fees &amp; costs'], [route('schools.index'), 'UK medical schools'], [route('admissions.index'), 'Admissions &amp; UCAT'], [route('working.index'), 'Working in the UK']],
                'Apply' => [[route('apply.index'), 'Apply Online'], [route('apply.services'), 'Our services'], [route('apply.eligibility'), 'Check your eligibility'], [route('login'), 'Student portal login'], [route('faq.index'), 'Questions']],
                'Organisation' => [[route('about'), 'About'], [route('status'), 'Our status'], [route('verify'), 'How we verify'], [route('contact'), 'Contact'], [route('legal.privacy'), 'Privacy'], [route('legal.terms'), 'Terms'], [route('legal.application-terms'), 'Application terms'], [route('legal.refunds'), 'Refund policy']],
            ] as $heading => $links)
                <div>
                    <p class="eyebrow text-white/50! mb-4">{{ $heading }}</p>
                    <ul class="space-y-2.5">
                        @foreach($links as [$href, $text])<li><a href="{{ $href }}" class="text-white/80 no-underline hover:text-white hover:underline">{!! $text !!}</a></li>@endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-site py-6 flex flex-col md:flex-row gap-3 md:items-center justify-between text-[0.8125rem] text-white/55">
            <p>&copy; {{ date('Y') }} {{ config('site.legal_name') ?? config('site.name') }} · <a href="mailto:{{ config('site.email') }}" class="text-white/85 underline decoration-white/40 hover:decoration-white">{{ config('site.email') }}</a>@if(config('site.ga4_id')) · <button type="button" data-consent-reset class="text-white/85 underline decoration-white/40 hover:decoration-white">Cookie settings</button> @endif @if(config('site.whatsapp')) · <a href="https://wa.me/{{ config('site.whatsapp') }}?text={{ rawurlencode('Hello, I would like help studying Medicine in the UK from Nigeria.') }}" rel="noopener" target="_blank" class="text-white/85 underline decoration-white/40 hover:decoration-white">WhatsApp</a>@endif</p>
            <p class="flex items-center gap-2"><x-icon name="badge-check" :size="15" class="text-gold-400" />Information is checked against official sources and shows a last-verified date. Admission decisions are made solely by universities.</p>
        </div>
    </div>
</footer>

{{-- hideFloatingCta: true hides both buttons (sign-in, errors); 'apply' keeps WhatsApp on the pages that are the apply route --}}
@php($fab = ($hideFloatingCta ?? false) === 'apply' ? 'contact' : (($hideFloatingCta ?? false) ? null : 'all'))
@if($fab && ($fab === 'all' || config('site.whatsapp')))
<aside data-floating-cta class="floating-cta flex flex-col items-end gap-3" aria-label="{{ $fab === 'all' ? 'Quick contact and apply' : 'Quick contact' }}">
    <x-whatsapp-cta />
    @if($fab === 'all')<a href="{{ route('apply.index') }}" class="btn btn-primary shadow-[var(--shadow-card)]">Apply Online</a>@endif
</aside>
@endif
@stack('body-end')
@stack('scripts')
</body>
</html>
