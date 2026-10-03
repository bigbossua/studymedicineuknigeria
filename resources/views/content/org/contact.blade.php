<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Contact', 'title' => 'Contact us', 'lede' => 'The quickest route for anything about your application is the Messages page in your portal; it keeps everything on your record and alerts our team.', 'seo' => $seo])
    <div class="mt-10 grid md:grid-cols-2 gap-6 max-w-3xl">
        <div class="card"><p class="eyebrow mb-2">Email</p><a href="mailto:{{ config('site.email') }}" class="text-lg font-semibold">{{ config('site.email') }}</a><p class="mt-2 text-[0.9375rem] text-ink-500">We reply within two working days. Please do not email documents; upload them in your portal where they are protected.</p></div>
        <div class="card"><p class="eyebrow mb-2">Student portal</p><a href="{{ route('login') }}" class="btn btn-secondary">Sign in and message us</a><p class="mt-2 text-[0.9375rem] text-ink-500">Quote your application number (SMN-…) in any message.</p></div>
        @if(config('site.whatsapp'))<div class="card"><p class="eyebrow mb-2">WhatsApp</p><a href="https://wa.me/{{ config('site.whatsapp') }}" rel="noopener" target="_blank" class="btn btn-tertiary">Message on WhatsApp</a><p class="mt-2 text-[0.9375rem] text-ink-500">For quick questions only; your portal remains the record.</p></div>@endif
        <div class="card"><p class="eyebrow mb-2">Address</p><p class="text-[0.9375rem]">{{ config('site.address') ?? 'Registered address to be published on Our status.' }}</p></div>
    </div>
</article>
</x-layouts.public>
