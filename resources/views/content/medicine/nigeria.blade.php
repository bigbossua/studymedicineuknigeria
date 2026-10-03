<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Nigeria → United Kingdom · Medicine', 'title' => 'Study Medicine in the UK from Nigeria: the honest guide', 'lede' => 'If you hold WAEC or NECO, A-levels, the IB or a Nigerian degree and want to become a doctor in the UK, this page tells you which routes are genuinely open, what it costs, when things happen, and how to apply, with every claim traceable to a university or official page.', 'seo' => $seo])
    <x-photo slug="nigeria-guide" class="mt-8 max-w-3xl" ratio="16/9" sizes="(min-width: 1024px) 48rem, 100vw" />

    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section class="prose-site">
                <h2>1. Start with the one fact that changes everything</h2>
                <p>In our review of published UK medical school pages, <strong>no school offers direct entry to the standard five-year Medicine degree on WASSCE or NECO alone</strong>. Universities that address Nigerian applicants treat WASSCE as the equivalent of GCSEs and ask for A-levels, the IB, a recognised foundation year or part of a degree. Two schools say so explicitly; others route WASSCE holders to a foundation programme. That is not a closed door, it is a map: your route is A-levels or IB, a foundation year that leads to Medicine, or a degree first.</p>
                <p class="text-[0.9375rem] text-ink-500">The statements below are what universities publish. Those marked "Verification pending" were located on official pages during research and are shown here outside production only until a reviewer confirms them.</p>
            </section>
            <section>
                <h2>What universities publish about WAEC and NECO ({{ $waec->count() }} statements)</h2>
                @include('content._statement-list', ['items' => $waec->take(6)])
                <a href="{{ route('requirements.waec') }}" class="btn btn-tertiary mt-3">All {{ $waec->count() }} statements, and where WAEC English is accepted</a>
            </section>
            <section class="prose-site">
                <h2>2. Which medical schools you can actually apply to</h2>
                <p>Of the UK medical schools in our directory, <strong>{{ $accepting }}</strong> accept international undergraduate applicants and <strong>{{ $homeOnly }}</strong> are home-only. International places are capped and small, often 10 to 30 a school, and a handful of schools run international-only cohorts. The <a href="{{ route('schools.index') }}">directory</a> lets you filter by eligibility, admissions test and application route, and every record shows its sources.</p>
            </section>
            <section class="prose-site">
                <h2>3. The calendar is unforgiving</h2>
                <p>Medicine runs on a fixed cycle: the UCAT in July–September, UCAS by mid-October, interviews December–March. Miss the UCAT and the UCAS medicine route for that year is effectively closed, leaving only the few schools that do not use the UCAT.</p>
                <dl class="card not-prose mt-4">
                    <x-fact-row :fact="$ucat?->fact('testing_window')" label="UCAT testing window (2027 entry)" />
                    <x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="UCAS medicine deadline (2027 entry)" />
                    <x-fact-row :fact="$ucas?->fact('max_medicine_choices')" label="Medicine choices on UCAS" />
                </dl>
                <p class="mt-4">If you are reading this after September 2026 without a UCAT result, the realistic plan is <strong>2028 entry</strong> (UCAT in summer 2027, UCAS by October 2027), or a non-UCAT school for 2027. Our <a href="{{ route('admissions.ucas2027') }}">timeline page</a> sets out both.</p>
            </section>
            <section class="prose-site">
                <h2>4. The cost is substantial and must be planned</h2>
                <p>International Medicine fees range widely between schools and usually rise in clinical years. Add the Student visa fee, the Immigration Health Surcharge for every year, the maintenance funds you must show, and living costs. Our <a href="{{ route('fees.index') }}">fee guide</a> lists each published fee with its year; the <a href="{{ route('fees.total') }}">total cost page</a> adds everything up with the arithmetic shown.</p>
                @php $pub = $fees->filter(fn($f)=>$f->isPublishable() && $f->value_number); @endphp
                @if($pub->count())<p class="not-prose card text-[0.9375rem]">Published international fees we have recorded so far span <strong>£{{ number_format($pub->min('value_number')) }}</strong> to <strong>£{{ number_format($pub->max('value_number')) }}</strong> per year across {{ $pub->count() }} schools (our aggregation of official figures; each is sourced on the fee guide).</p>@endif
            </section>
            <section class="prose-site">
                <h2>5. How to apply, and how we help</h2>
                <p>Most schools are applied to through UCAS, which you do in your own UCAS account; we prepare everything and guide you. A few schools take direct applications. We are an independent application-support service: we assess your profile against published requirements, build your document checklist, review every document, prepare the complete package, and only after your explicit approval does any submission begin. We do not guarantee admission, we are not an agent of any university, and our fee is separate from university fees.</p>
            </section>
            <section aria-labelledby="faq-h">
                <h2 id="faq-h">Questions Nigerian applicants ask</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach($faqs as $f)<details class="py-3"><summary class="cursor-pointer font-medium text-[1.0625rem]">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 text-[0.9375rem]">{!! $f['a'] !!}</div></details>@endforeach
                </div>
                <a href="{{ route('faq.index') }}" class="btn btn-tertiary mt-3">All questions</a>
            </section>
            <x-cta-band title="Ready to check your own situation?" :href="route('apply.eligibility')" label="Check your eligibility" :secondary-href="route('apply.index')" secondary-label="Apply Online">Seven questions, no account needed. Then, if you choose, create your account and start a structured application you can leave and return to.</x-cta-band>
        </div>
        <aside class="lg:col-span-4 space-y-5">
            <div class="card"><p class="eyebrow mb-3">Your route in one minute</p>
                <ul class="space-y-3 text-[0.9375rem]">
                    <li><span class="font-semibold">WAEC/NECO only →</span> A-levels/IB, or a foundation year that leads to Medicine. <a href="{{ route('requirements.waec') }}">Read</a></li>
                    <li><span class="font-semibold">A-levels/IB →</span> standard entry (A100) with UCAT. <a href="{{ route('requirements.alevels') }}">Read</a></li>
                    <li><span class="font-semibold">Nigerian degree →</span> graduate entry at the few schools open to internationals, or standard entry as a graduate. <a href="{{ route('requirements.gem') }}">Read</a></li>
                </ul></div>
            <div class="card"><p class="eyebrow mb-3">Where to go next</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('requirements.index') }}">What do I need? (requirements hub)</a></li><li><a href="{{ route('schools.index') }}">Medical school directory</a></li><li><a href="{{ route('admissions.ucat') }}">UCAT from Nigeria</a></li><li><a href="{{ route('fees.index') }}">Fee guide</a></li></ul></div>
        </aside>
    </div>
    <x-related :items="[['label' => 'WAEC and UK Medicine', 'url' => route('requirements.waec'), 'description' => 'Every statement, by university'], ['label' => 'Fee guide', 'url' => route('fees.index'), 'description' => 'Published international fees with years'], ['label' => 'UCAS 2027 timeline', 'url' => route('admissions.ucas2027'), 'description' => 'Dates for 2027 and 2028 planning'], ['label' => 'Apply Online', 'url' => route('apply.index'), 'description' => 'Structured, resumable application']]" />
</article>
</x-layouts.public>
