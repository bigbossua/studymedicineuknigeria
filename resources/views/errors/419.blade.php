<x-layouts.public :seo="\App\Support\Seo::make('Your session expired')->noindex()" :hide-floating-cta="true">
    <section class="container-site py-16 sm:py-24">
        <div class="max-w-2xl">
            <p class="eyebrow mb-3">419</p>
            <h1>Your session expired</h1>
            <p class="lede mt-5">For your security, forms expire after an hour of inactivity. Go back and submit the form again.</p>
            <div class="mt-8 flex flex-wrap gap-3"><a href="{{ url('/') }}" class="btn btn-primary">Go to the home page</a><a href="{{ url('/login') }}" class="btn btn-secondary">Student portal</a></div>
            <p class="mt-8 text-[0.875rem] text-ink-500">Need help now? Email <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a> and quote the time this happened.</p>
        </div>
    </section>
</x-layouts.public>
