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

<header class="border-b border-ink-200 bg-paper">
    <div class="container-site flex items-center justify-between gap-4 h-[72px]">
        <a href="{{ route('home') }}" class="flex items-center no-underline shrink-0" aria-label="{{ config('site.name') }} — home">
            <img src="/images/brand/smukn-logo-horizontal.svg" alt="{{ config('site.name') }}" width="286" height="44" class="h-10 sm:h-11 w-auto" fetchpriority="high">
        </a>
        <nav class="hidden lg:flex items-center gap-7" aria-label="Primary">
            @foreach(config('site.nav') as $item)
                @php $href = \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#'; $active = $currentRoute && str_starts_with($currentRoute, explode('.', $item['route'])[0]); @endphp
                <a href="{{ $href }}" class="nav-link" @if($active) aria-current="page" @endif>{{ $item['label'] }}</a>
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
                <a href="{{ $href }}" class="nav-link text-lg py-3 border-b border-ink-100">{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ route('apply.index') }}" class="btn btn-primary btn-lg mt-5">Apply Online</a>
            <p class="hint mt-6">{{ config('site.email') }}</p>
        </nav>
    </div>
</header>

<main id="main" class="flex-1">
    @isset($seo)
        @if($seo->breadcrumbs)
            <div class="container-site pt-5">
                <x-breadcrumbs :items="$seo->breadcrumbs" />
            </div>
        @endif
    @endisset
    {{ $slot ?? '' }}
    @yield('content')
</main>

<footer class="mt-20 border-t border-ink-200 bg-paper-warm">
    <div class="container-site py-12 grid gap-10 md:grid-cols-12">
        <div class="md:col-span-5">
            <img src="/images/brand/smukn-logo-horizontal.svg" alt="" width="286" height="44" class="h-10 w-auto" loading="lazy">
            <p class="mt-4 text-[0.9375rem] text-ink-700 max-w-prose">{{ config('site.default_description') }}</p>
            <p class="mt-4 text-[0.875rem] text-ink-500">{{ config('site.status_statement') }}</p>
        </div>
        <div class="md:col-span-7 grid grid-cols-2 sm:grid-cols-3 gap-8 text-[0.9375rem]">
            <div>
                <p class="eyebrow mb-3">Understand</p>
                <ul class="space-y-2">
                    <li><a href="{{ route('medicine.index') }}">Study Medicine in the UK</a></li>
                    <li><a href="{{ route('requirements.index') }}">Requirements</a></li>
                    <li><a href="{{ route('fees.index') }}">Fees &amp; costs</a></li>
                    <li><a href="{{ route('schools.index') }}">UK medical schools</a></li>
                    <li><a href="{{ route('admissions.index') }}">Admissions &amp; UCAT</a></li>
                </ul>
            </div>
            <div>
                <p class="eyebrow mb-3">Apply</p>
                <ul class="space-y-2">
                    <li><a href="{{ route('apply.index') }}">Apply Online</a></li>
                    <li><a href="{{ route('apply.services') }}">Services &amp; pricing</a></li>
                    <li><a href="{{ route('apply.eligibility') }}">Check your eligibility</a></li>
                    <li><a href="{{ route('login') }}">Student portal login</a></li>
                    <li><a href="{{ route('faq.index') }}">Questions</a></li>
                </ul>
            </div>
            <div>
                <p class="eyebrow mb-3">Organisation</p>
                <ul class="space-y-2">
                    <li><a href="{{ route('about') }}">About</a></li>
                    <li><a href="{{ route('status') }}">Our status</a></li>
                    <li><a href="{{ route('contact') }}">Contact</a></li>
                    <li><a href="{{ route('legal.privacy') }}">Privacy</a></li>
                    <li><a href="{{ route('legal.terms') }}">Terms</a></li>
                    <li><a href="{{ route('legal.application-terms') }}">Application terms</a></li>
                    <li><a href="{{ route('legal.refunds') }}">Refund policy</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="border-t border-ink-200">
        <div class="container-site py-5 flex flex-col sm:flex-row gap-2 sm:items-center justify-between text-[0.8125rem] text-ink-500">
            <p>&copy; {{ date('Y') }} {{ config('site.legal_name') ?? config('site.name') }}. <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a></p>
            <p>Information is checked against official sources and shows a last-verified date. Admission decisions are made solely by universities.</p>
        </div>
    </div>
</footer>

@unless(($hideFloatingCta ?? false))
<div data-floating-cta class="floating-cta">
    <a href="{{ route('apply.index') }}" class="btn btn-primary">Apply Online</a>
</div>
@endunless
@stack('body-end')
@stack('scripts')
</body>
</html>
