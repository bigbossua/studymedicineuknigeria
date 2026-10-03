<x-layouts.public :seo="\App\Support\Seo::make('Page not found')->noindex()" :hide-floating-cta="true">
    <section class="container-site py-16 sm:py-24">
        <div class="max-w-2xl">
            <p class="eyebrow mb-3">404</p>
            <h1>We could not find that page</h1>
            <p class="lede mt-5">The address may have changed or never existed. These are the places most people are looking for.</p>
            <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                @foreach([
                    ['Study Medicine in the UK from Nigeria', route('medicine.nigeria')],
                    ['Requirements for Nigerian applicants', route('requirements.index')],
                    ['UK medical school fees', route('fees.index')],
                    ['UK medical school directory', route('schools.index')],
                    ['UCAT for Nigerian students', route('admissions.ucat')],
                    ['Apply Online', route('apply.index')],
                ] as [$label, $url])
                    <li><a href="{{ $url }}" class="card card-link font-medium">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
    </section>
</x-layouts.public>
