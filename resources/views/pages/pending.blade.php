<x-layouts.public :seo="$seo">
    <section class="container-site py-16 sm:py-24">
        <div class="max-w-2xl">
            <p class="eyebrow mb-3">In preparation</p>
            <h1 class="text-balance">{{ $title }}</h1>
            <p class="lede mt-5">This page is being prepared and verified against official sources before publication. Nothing is published here until every fact carries an official source and a last-verified date.</p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                <a href="{{ route('home') }}" class="btn btn-secondary">Back to the homepage</a>
                <a href="mailto:{{ config('site.email') }}" class="btn btn-tertiary">Email {{ config('site.email') }}</a>
            </div>
        </div>
    </section>
</x-layouts.public>
