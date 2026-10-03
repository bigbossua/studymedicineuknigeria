<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Medicine · the pillar', 'title' => 'Study Medicine in the UK: how the routes, requirements, costs and calendar fit together', 'lede' => 'UK medical degrees are five or six years long, lead to provisional registration with the General Medical Council after a licensing assessment, and are applied for through a fixed annual calendar. This page explains the structure; the pages it links to carry the sourced detail.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 prose-site">
            <h2>Three ways in</h2>
            <p><strong>Standard entry</strong> (UCAS code A100 at most schools) is the five-year degree for school leavers with A-levels, the IB or equivalents. Almost every school requires the UCAT. <strong>Graduate entry</strong> (A101/A102) is a four-year accelerated course for people who already hold a degree; most programmes are home-only and only some accept international applicants. <strong>Foundation or gateway routes</strong> add a preparatory year before Medicine; many are home-only widening-participation schemes, but a few international foundation programmes publish Medicine as a destination.</p>
            <p>For a Nigerian applicant the practical consequence is this: WASSCE and NECO alone are treated as the GCSE layer, not as the entry qualification. Your route is A-levels/IB, a foundation year that leads to Medicine, or a degree followed by graduate or standard entry. Our <a href="{{ route('requirements.index') }}">requirements hub</a> walks through each.</p>
            <h2>What every medical school looks at</h2>
            <ul>
                <li>Academic qualifications and subject rules (Chemistry and Biology in almost every offer) — <a href="{{ route('requirements.alevels') }}">A-levels and IB</a></li>
                <li>An admissions test, usually the <a href="{{ route('admissions.ucat') }}">UCAT</a>, sat the summer before you apply</li>
                <li>English language evidence, typically IELTS 7.0–7.5 for Medicine — <a href="{{ route('requirements.english') }}">English requirements</a></li>
                <li>Your personal statement, reference and interview</li>
                <li>Whether the school has international places at all, and how many — <a href="{{ route('schools.index') }}">directory</a></li>
            </ul>
            <h2>The calendar</h2>
            <p>UCAS medicine applications close in mid-October the year before entry; the UCAT must be sat before that, between July and September. Interviews run from December to March, offers follow, and the visa process begins once you accept an offer and meet its conditions. Our <a href="{{ route('admissions.ucas2027') }}">2027 timeline</a> has the exact dates with sources.</p>
            <dl class="card mt-4">
                <x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="UCAS deadline for medicine, 2027 entry" />
                <x-fact-row :fact="$ucat?->fact('testing_window')" label="UCAT testing window for 2027 entry" />
            </dl>
            <h2>What it costs</h2>
            <p>International medicine fees differ by tens of thousands of pounds a year between schools and are usually higher in clinical years. Visa, the Immigration Health Surcharge, maintenance funds and living costs come on top. Our <a href="{{ route('fees.index') }}">fee guide</a> shows every published fee with its year and source.</p>
            <h2>After graduation</h2>
            <p>UK graduates pass the Medical Licensing Assessment within their degree, apply for GMC provisional registration and enter the two-year Foundation Programme. International graduates of UK schools apply on the same basis as home graduates, with visa sponsorship for Foundation Year 1. Post-study rules change often; we keep that content factual, dated and separate.</p>
            <x-cta-band class="mt-10" title="Not sure which route is yours?" :href="route('apply.eligibility')" label="Check your eligibility">Seven questions, no account needed. You will see which routes appear open on published requirements.</x-cta-band>
        </div>
        <aside class="lg:col-span-4 space-y-5">
            <div class="card"><p class="eyebrow mb-3">In this section</p><ul class="space-y-2 text-[0.9375rem]">
                <li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria</a></li><li><a href="{{ route('medicine.foundation') }}">Foundation routes to Medicine</a></li><li><a href="{{ route('requirements.index') }}">Requirements hub</a></li><li><a href="{{ route('schools.index') }}">UK medical school directory ({{ $schools }} schools open to international applicants)</a></li></ul></div>
            <div class="card"><p class="eyebrow mb-3">How we write these pages</p><p class="text-[0.9375rem] text-ink-700">Explanations are ours. Every specific requirement, fee or date is a separate record with an official source and a verification date, and is hidden until a reviewer has confirmed it on the official page.</p></div>
        </aside>
    </div>
    <x-related :items="[['label' => 'From Nigeria: the honest guide', 'url' => route('medicine.nigeria')], ['label' => 'WAEC and UK Medicine', 'url' => route('requirements.waec')], ['label' => 'Fee guide', 'url' => route('fees.index')], ['label' => 'UCAT for Nigerian students', 'url' => route('admissions.ucat')], ['label' => 'Working in the UK', 'url' => route('working.index'), 'description' => 'Visa work rules, GMC registration, Foundation training and what is changing.']]" />
</article>
</x-layouts.public>
