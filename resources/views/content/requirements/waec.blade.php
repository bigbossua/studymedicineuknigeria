<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements · Nigerian qualifications', 'title' => 'WAEC (WASSCE) and UK Medicine: what each medical school publishes', 'lede' => 'The short answer: no UK medical school we reviewed admits to the standard Medicine degree on WASSCE alone. WASSCE is treated as the GCSE layer; the offer is made on A-levels, the IB or a recognised foundation year. The long answer, school by school with sources, is below.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section class="prose-site">
                <h2>How to read these statements</h2>
                <p>Some statements come from the university's Medicine pages and are <strong>medicine-specific</strong>. Others come from the university's general "Nigeria" entry-requirements page and apply to undergraduate study broadly; Medicine often has stricter rules, so treat those as a starting point and confirm. Where a university publishes nothing about Nigeria, we say so rather than guess.</p>
            </section>
            <section><h2>Medicine-specific statements ({{ $specific->count() }})</h2>@include('content._statement-list', ['items' => $specific, 'empty' => 'No medicine-specific WAEC statement has been recorded yet.'])</section>
            <section><h2>General university statements about WASSCE ({{ $general->count() }})</h2>@include('content._statement-list', ['items' => $general])</section>
            <section><h2>Where WAEC or NECO English is accepted as English-language evidence ({{ $english->count() }})</h2>
                <p class="text-ink-700 mt-2">A few schools publish that a good grade in WAEC/NECO English Language satisfies their English requirement, sometimes for Medicine specifically and sometimes as a general rule whose application to Medicine must be confirmed. Other schools ask for IELTS or an equivalent test, at the band each publishes (<a href="{{ route('requirements.english') }}">English requirements</a>).</p>
                @include('content._statement-list', ['items' => $english, 'empty' => 'No WAEC/NECO English acceptance statements recorded yet.'])</section>
            <section class="prose-site">
                <h2>What this means for you</h2>
                <p class="text-[0.9375rem] text-ink-700">Shortlist from the <a href="{{ route('schools.index') }}?waec=published">schools that publish a WAEC or NECO statement</a>; the directory marks the others as having no Nigeria-specific statement located.</p>
                <ul>
                    <li><strong>WAEC only, strong sciences:</strong> plan A-levels (Chemistry, Biology and a third subject) or the IB, then standard entry with the UCAT; or a foundation programme that publishes Medicine as a destination. <a href="{{ route('medicine.foundation') }}">Foundation routes</a>.</li>
                    <li><strong>WAEC plus A-levels in progress:</strong> you are on the standard route; focus on predicted grades, the UCAT window and English evidence. <a href="{{ route('requirements.alevels') }}">A-levels page</a>.</li>
                    <li><strong>WAEC plus a Nigerian degree:</strong> graduate entry where open to internationals, or standard entry as a graduate. <a href="{{ route('requirements.gem') }}">Graduate entry page</a>.</li>
                </ul>
            </section>
            <x-cta-band title="Not sure whether your Nigerian qualifications meet the requirements?" :href="route('apply.eligibility')" label="Check your eligibility">Five questions about your route, then your name and email; no account needed. You will see which routes appear open and what to read next.</x-cta-band>
        </div>
        <aside class="lg:col-span-4 space-y-5">
            <div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('requirements.neco') }}">NECO and UK Medicine</a></li><li><a href="{{ route('requirements.english') }}">English language requirements</a></li><li><a href="{{ route('requirements.alevels') }}">A-levels for UK Medicine</a></li><li><a href="{{ route('schools.index') }}">Directory</a></li></ul></div>
            <div class="card"><p class="eyebrow mb-3">Myth check</p><p class="text-[0.9375rem] text-ink-700">"UK universities accept WAEC for Medicine" is true only in the sense that WASSCE covers the GCSE layer. It is not an entry qualification for Medicine at any school we found. Be cautious of any agent who says otherwise.</p></div>
        </aside>
    </div>
    <x-related :items="[['label' => 'Foundation routes to Medicine', 'url' => route('medicine.foundation')], ['label' => 'Fee guide', 'url' => route('fees.index')], ['label' => 'UCAT for Nigerian students', 'url' => route('admissions.ucat')], ['label' => 'Apply Online', 'url' => route('apply.index')]]" />
    <x-route-map current="qualifications" />
</article>
</x-layouts.public>
