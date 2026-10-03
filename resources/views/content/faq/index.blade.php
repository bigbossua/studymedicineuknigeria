<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Questions', 'title' => 'Questions Nigerian applicants ask about UK Medicine', 'lede' => 'These are the questions people actually ask on forums and in search, answered straight and linked to the pages where the sourced detail lives. If your question is not here, ask us from your portal or by email.', 'seo' => $seo])
    <div class="mt-10 max-w-3xl divide-y divide-ink-100">
        @foreach($items as $f)
            <details class="py-4" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-serif font-semibold text-lg">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>
        @endforeach
    </div>
    <x-cta-band class="mt-12" title="Ready to check your own situation?" :href="route('apply.eligibility')" label="Check your eligibility" />
</article>
</x-layouts.public>
