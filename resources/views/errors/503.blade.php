<x-layouts.public :seo="\App\Support\Seo::make('We are updating the site')->noindex()" :hide-floating-cta="true">
    <section class="container-site py-16 sm:py-24">
        <div class="max-w-2xl">
            <p class="eyebrow mb-3">503</p>
            <h1>We are updating the site</h1>
            <p class="lede mt-5">A short maintenance window is in progress. Your application and documents are safe. Please come back in a few minutes.</p>
            <div class="mt-8 flex flex-wrap gap-3"><a href="{{ url('/') }}" class="btn btn-primary">Go to the home page</a><a href="{{ url('/login') }}" class="btn btn-secondary">Student portal</a></div>
            <p class="mt-8 text-[0.875rem] text-ink-500">Need help now? Email <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a> and quote the time this happened.</p>
        </div>
    </section>
</x-layouts.public>
