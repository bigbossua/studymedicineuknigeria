@php $seo = ($seo ?? \App\Support\Seo::make('Admin'))->noindex(); @endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo', ['seo' => $seo])
    <link rel="icon" href="/favicon.svg" type="image/svg+xml"><meta name="theme-color" content="#0B3D5C">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-100/60 flex flex-col">
<header class="bg-navy-900 text-white">
    <div class="container-site h-14 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4"><a href="{{ route('admin.dashboard') }}" class="no-underline text-white flex items-center gap-2"><img src="/favicon.svg" alt="" width="28" height="28"><span class="font-serif font-semibold">Admin</span></a><span class="chip bg-white/10 text-white/80">{{ auth()->user()->role }}</span></div>
        <nav class="flex items-center gap-4 text-[0.875rem] overflow-x-auto whitespace-nowrap" aria-label="Admin">
            @foreach([['admin.dashboard','Dashboard'],['admin.applications.index','Applications'],['admin.leads','Leads'],['admin.payments','Payments'],['admin.reference.index','Verification'],['admin.reference.universities','Universities'],['admin.tiers','Services'],['admin.redirects','Redirects'],['admin.users','Users'],['admin.funnel','Funnel'],['admin.seo','SEO'],['admin.professions','Subjects'],['admin.audit','Audit']] as [$r,$l])
                <a href="{{ route($r) }}" class="no-underline text-white/80 hover:text-white {{ request()->routeIs($r) ? 'text-white font-semibold underline underline-offset-4' : '' }}">{{ $l }}</a>
            @endforeach
            <form method="post" action="{{ route('logout') }}">@csrf<button class="text-white/70 hover:text-white">Sign out</button></form>
        </nav>
    </div>
</header>
<main class="flex-1 container-site py-8">
    @if(session('status'))<div class="alert alert-success mb-5">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger mb-5" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger mb-5"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    {{ $slot ?? '' }}
</main>
</body>
</html>
