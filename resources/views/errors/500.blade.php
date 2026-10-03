<x-layouts.public :seo="\App\Support\Seo::make('Something went wrong on our side')->noindex()" :hide-floating-cta="true">
    <section class="container-site py-16 sm:py-24">
        <div class="max-w-2xl">
            <p class="eyebrow mb-3">500</p>
            <h1>Something went wrong on our side</h1>
            <p class="lede mt-5">The error has been recorded and our team will look at it. Nothing you entered in your portal is lost: autosave keeps your work. Please try again in a few minutes.</p>
            <div class="mt-8 flex flex-wrap gap-3"><a href="{{ url('/') }}" class="btn btn-primary">Go to the home page</a><a href="{{ url('/login') }}" class="btn btn-secondary">Student portal</a></div>
            <p class="mt-8 text-[0.875rem] text-ink-500">Need help now? Email <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a> and quote the time this happened.</p>
        </div>
    </section>
</x-layouts.public>
