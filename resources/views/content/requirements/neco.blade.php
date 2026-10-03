<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements · Nigerian qualifications', 'title' => 'NECO and UK Medicine: what medical schools say about the NECO SSCE', 'lede' => 'Most UK medical school pages name WASSCE and are silent on NECO. Where NECO is mentioned, it is treated in the same way as WASSCE: as the GCSE layer, not as the entry qualification for Medicine. If a school is silent, confirm with its admissions team before you rely on it.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section><h2>Statements that mention NECO ({{ $mentionsNeco->count() }})</h2>@include('content._statement-list', ['items' => $mentionsNeco, 'empty' => 'None of the statements we have recorded names NECO explicitly. Treat NECO as WASSCE and confirm with the school.'])</section>
            <section class="prose-site"><h2>Statements that name WASSCE only</h2><p>These {{ $all->count() - $mentionsNeco->count() }} statements refer to WASSCE. In practice UK universities that recognise WASSCE at GCSE level generally recognise NECO SSCE on the same terms, but that is an inference, not a published rule: ask the school to confirm in writing. The full list is on the <a href="{{ route('requirements.waec') }}">WAEC page</a>.</p></section>
            <section class="prose-site"><h2>Your route with NECO</h2><p>Identical to the WAEC route: A-levels or IB and then standard entry with the UCAT, or a foundation programme that publishes Medicine as a destination, or a degree first. Our <a href="{{ route('apply.eligibility') }}">eligibility check</a> maps your answers to these routes.</p></section>
            <x-cta-band title="Not sure whether your Nigerian qualifications meet the requirements?" :href="route('apply.eligibility')" label="Check your eligibility" />
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('requirements.waec') }}">WAEC and UK Medicine</a></li><li><a href="{{ route('requirements.english') }}">English requirements (incl. NECO English)</a></li><li><a href="{{ route('medicine.foundation') }}">Foundation routes</a></li></ul></div></aside>
    </div>
</article>
</x-layouts.public>
