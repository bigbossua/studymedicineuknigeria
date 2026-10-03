<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements · standard entry', 'title' => 'A-levels for UK Medicine from Nigeria: grades, subjects and how international applicants are assessed', 'lede' => 'Standard-entry Medicine (A100) is offered on A-levels or the IB. Typical offers are AAA to A*AA including Chemistry and Biology, or IB 36 to 38 with 6s and 7s in Higher Level sciences. Cambridge International A-levels taken in Nigeria are assessed the same way as UK A-levels, subject to each school\'s rules.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section><h2>What schools publish ({{ $reqs->count() }} records)</h2>@include('content._statement-list', ['items' => $reqs])</section>
            <section class="prose-site">
                <h2>Subject rules to watch</h2>
                <ul><li>Chemistry is required almost everywhere; Biology is required or strongly expected; a third subject is usually free, though some schools exclude General Studies or Critical Thinking.</li><li>Some schools require GCSE-level (WASSCE) grades in English, Mathematics and the sciences alongside A-levels; the WAEC grades they ask for are on our <a href="{{ route('requirements.waec') }}">WAEC page</a>.</li><li>Resits and "achieved vs predicted" policies differ; check the school's selection policy document.</li></ul>
                <h2>Choosing where to take A-levels in Nigeria</h2>
                <p>Several colleges in Lagos, Abuja and elsewhere offer Cambridge International A-levels. We do not rank or recommend providers. Ask for their Cambridge centre number, their recent grade distribution in Chemistry and Biology, and whether they support UCAT preparation and UCAS references.</p>
            </section>
            <x-cta-band title="On the A-level route?" :href="route('admissions.ucat')" label="Plan your UCAT" :secondary-href="route('apply.eligibility')" secondary-label="Check your eligibility">The UCAT window falls in July–September of your final A-level year. Plan it now.</x-cta-band>
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('admissions.ucat') }}">UCAT from Nigeria</a></li><li><a href="{{ route('requirements.english') }}">English requirements</a></li><li><a href="{{ route('schools.index') }}">Directory</a></li><li><a href="{{ route('fees.index') }}">Fee guide</a></li></ul></div></aside>
    </div>
</article>
</x-layouts.public>
