<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Questions', 'title' => 'Questions Nigerian applicants ask about UK Medicine', 'lede' => 'These are the questions people actually ask on forums and in search, answered straight and linked to the pages where the sourced detail lives. If your question is not here, ask us from your portal or by email.', 'seo' => $seo])
    <nav class="mt-8 flex flex-wrap gap-2 text-[0.875rem]" aria-label="Question groups">@foreach($grouped as $group => $qs)<a href="#{{ Str::slug($group) }}" class="chip chip-pending no-underline hover:bg-navy-100">{{ $group }} ({{ $qs->count() }})</a>@endforeach</nav>
    <div class="mt-6 max-w-3xl space-y-10">
        @foreach($grouped as $group => $qs)
            <section id="{{ Str::slug($group) }}" aria-labelledby="g-{{ Str::slug($group) }}">
                <h2 id="g-{{ Str::slug($group) }}">{{ $group }}</h2>
                <div class="mt-3 divide-y divide-ink-100">
                    @foreach($qs as $f)
                        <details class="py-4" id="q{{ $f['id'] }}"><summary class="cursor-pointer"><h3 class="inline font-serif font-semibold text-lg">{{ $f['q'] }}</h3></summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
    <x-cta-band class="mt-12" title="Ready to check your own situation?" :href="route('apply.eligibility')" label="Check your eligibility" />
    <x-related title="Where the sourced detail lives" :items="[['label' => 'Study Medicine in the UK from Nigeria', 'url' => route('medicine.nigeria'), 'description' => 'The full guide, start to finish'], ['label' => 'WAEC and UK Medicine', 'url' => route('requirements.waec'), 'description' => 'What each school publishes'], ['label' => 'Fee guide', 'url' => route('fees.index'), 'description' => 'International fees by school'], ['label' => 'UCAT for Nigerian students', 'url' => route('admissions.ucat'), 'description' => 'Dates, centres, structure'], ['label' => 'Medical school directory', 'url' => route('schools.index'), 'description' => 'Who accepts international applicants'], ['label' => 'How to apply', 'url' => route('admissions.howto'), 'description' => 'UCAS and direct applications']]" />
    <x-route-map />
</article>
</x-layouts.public>
