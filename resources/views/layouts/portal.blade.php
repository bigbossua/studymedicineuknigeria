@php $seo = $seo ?? \App\Support\Seo::make('Student portal')->noindex(); $seo->noindex(); $user = auth()->user(); $app = $application ?? null; @endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo', ['seo' => $seo])
    <link rel="icon" href="/favicon.ico" sizes="48x48"><link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="/apple-touch-icon.png"><meta name="theme-color" content="#0B3D5C">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/portal.js'])
</head>
<body class="min-h-screen bg-paper-warm flex flex-col">
<a href="#main" class="sr-only-focusable fixed top-2 left-2 z-50 bg-navy-700 text-white px-3 py-2 rounded-sm">Skip to content</a>
<header class="bg-paper border-b border-ink-200">
    <div class="container-site h-16 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
            <a href="{{ route('portal.dashboard') }}" class="shrink-0 no-underline" aria-label="Student portal home"><img src="/images/brand/smukn-logo-horizontal.svg" alt="{{ config('site.name') }}" width="286" height="44" class="h-9 w-auto"></a>
            @if($app)<span class="hidden sm:inline font-mono text-[0.8125rem] text-ink-500 truncate">{{ $app->application_number }}</span>@endif
        </div>
        <nav class="flex items-center gap-4 text-[0.9375rem]" aria-label="Portal">
            @if($app)
                <a href="{{ route('portal.dashboard') }}" class="nav-link hidden md:inline">Dashboard</a>
                <a href="{{ route('portal.application.index', $app) }}" class="nav-link hidden md:inline">Application</a>
                <a href="{{ route('portal.documents.index', $app) }}" class="nav-link hidden md:inline">Documents</a>
                <a href="{{ route('portal.payments.index', $app) }}" class="nav-link hidden md:inline">Payments</a>
                <a href="{{ route('portal.messages.index', $app) }}" class="nav-link hidden md:inline">Messages</a>
            @endif
            <a href="{{ route('portal.profile') }}" class="nav-link">{{ Str::of($user->name)->explode(' ')->first() }}</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-tertiary text-[0.9375rem]">Sign out</button></form>
        </nav>
    </div>
    @if($app)
    <nav class="md:hidden border-t border-ink-100 bg-paper overflow-x-auto" aria-label="Portal sections">
        <div class="container-site flex gap-5 text-[0.875rem] whitespace-nowrap py-2">
            <a href="{{ route('portal.dashboard') }}" class="nav-link">Dashboard</a><a href="{{ route('portal.application.index', $app) }}" class="nav-link">Application</a><a href="{{ route('portal.documents.index', $app) }}" class="nav-link">Documents</a><a href="{{ route('portal.payments.index', $app) }}" class="nav-link">Payments</a><a href="{{ route('portal.submissions.index', $app) }}" class="nav-link">Submissions</a><a href="{{ route('portal.messages.index', $app) }}" class="nav-link">Messages</a>
        </div>
    </nav>
    @endif
</header>
<main id="main" class="flex-1 container-site py-8 sm:py-10">
    @if(session('status'))<div class="alert alert-success mb-6">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger mb-6" role="alert">{{ session('error') }}</div>@endif
    {{ $slot ?? '' }}
    @yield('content')
</main>
<footer class="border-t border-ink-200 bg-paper">
    <div class="container-site py-5 flex flex-col sm:flex-row gap-2 justify-between text-[0.8125rem] text-ink-500">
        <p>Need help? Message us from your application, or email <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a>.</p>
        <p><a href="{{ route('legal.privacy') }}">Privacy</a> · <a href="{{ route('legal.application-terms') }}">Application terms</a></p>
    </div>
</footer>
@stack('scripts')
</body>
</html>
