<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Medicine · the pillar', 'title' => 'Study Medicine in the UK: how the routes, requirements, costs and calendar fit together', 'lede' => 'UK medical degrees are five or six years long, lead to provisional registration with the General Medical Council after a licensing assessment, and are applied for through a fixed annual calendar. This page explains the structure for an international applicant; the pages it links to carry the sourced detail, school by school.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 prose-site">
            <h2>1. What a UK medical degree is</h2>
            <p>The primary medical qualification is awarded by a university medical school and is called MBBS, MBChB, MB BCh, BMBS or MB BChir depending on the university; the names are historical and the qualification is the same. The standard course is five years; some schools run six, either because an intercalated bachelor's year is built in or because the programme includes a foundation or gateway year. Teaching moves from early years weighted towards science and clinical skills to later years spent largely on clinical placements in hospitals and general practice. Schools differ in style (integrated, case-based or problem-based curricula, early patient contact, dissection or prosection) more than in content, because all are approved against the same General Medical Council outcomes.</p>

            <h2>2. Three ways in</h2>
            <p><strong>Standard entry</strong> (UCAS code A100 at most schools) is the five-year degree for school leavers with A-levels, the IB or equivalents, and the route most Nigerian applicants take; almost every school requires the UCAT. <strong>Graduate entry</strong> (A101, A102) is a four-year accelerated course for people who already hold a degree; most programmes are home-only and only some accept international applicants. <strong>Foundation or gateway routes</strong> add a preparatory year before Medicine; many are home-only widening-participation schemes, but a few international foundation programmes publish Medicine as a destination.</p>
            <p>For a Nigerian applicant the practical consequence is this: WASSCE and NECO alone are treated as the GCSE layer, not as the entry qualification. Your route is <a href="{{ route('requirements.alevels') }}">A-levels or the IB</a>, a <a href="{{ route('medicine.foundation') }}">foundation year that leads to Medicine</a>, or a degree followed by <a href="{{ route('requirements.gem') }}">graduate or standard entry</a>. The <a href="{{ route('medicine.nigeria') }}">guide for applicants from Nigeria</a> puts the whole journey in order; the <a href="{{ route('requirements.index') }}">requirements hub</a> walks through each requirement.</p>

            <h2>3. Where international students can actually go</h2>
            <p>Of the {{ $total }} medical schools and programmes in our directory, {{ $schools }} publish that they admit international undergraduate applicants and {{ $homeOnly }} are home-only. International places are capped by the Government and are small at every school, typically a few dozen or fewer, so international applicants are usually ranked against each other rather than against home applicants. The <a href="{{ route('schools.index') }}?international=accepts">directory</a> lists each school's policy, places where published, admissions test, application route and fee, each with its source and verification date; we do not rank schools and we never present a gap in what is published as a yes or a no.</p>

            <h2>4. What every medical school looks at</h2>
            <ul>
                <li>Academic qualifications and subject rules (Chemistry and Biology in almost every offer) — <a href="{{ route('requirements.alevels') }}">A-levels and IB</a>, with the <a href="{{ route('requirements.waec') }}">WAEC</a> or <a href="{{ route('requirements.neco') }}">NECO</a> results as the GCSE-level layer</li>
                <li>An admissions test, usually the <a href="{{ route('admissions.ucat') }}">UCAT</a>, sat the summer before you apply; GAMSAT at some graduate programmes</li>
                <li>English language evidence, typically IELTS 7.0–7.5 for Medicine — <a href="{{ route('requirements.english') }}">English requirements</a></li>
                <li>Your personal statement (three structured questions), an academic reference and an interview, usually multiple mini-interviews and often online for applicants abroad — <a href="{{ route('admissions.howto') }}">how to apply</a></li>
                <li>Whether the school has international places at all, and how many — <a href="{{ route('schools.index') }}">directory</a></li>
            </ul>

            <h2>5. The calendar</h2>
            <p>UCAS medicine applications close in mid-October the year before entry; the UCAT must be sat before that, between July and September. Interviews run from December to March, offers follow by May, and the deposit, Confirmation of Acceptance for Studies and Student visa process begins once you accept an offer and meet its conditions. Missing the UCAT window closes the UCAS route for that year at every school that requires it; a few schools take direct applications on their own calendars. Our <a href="{{ route('admissions.ucas2027') }}">2027 timeline</a> has the exact dates with sources.</p>
            <dl class="card mt-4">
                <x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="UCAS deadline for medicine, 2027 entry" />
                <x-fact-row :fact="$ucat?->fact('testing_window')" label="UCAT testing window for 2027 entry" />
            </dl>

            <h2>6. What it costs</h2>
            <p>International medicine fees differ by tens of thousands of pounds a year between schools and are usually higher in clinical years, with annual increases reserved by most universities. Visa, the Immigration Health Surcharge for every year of the course, maintenance funds and living costs come on top, and funding for international medicine students is very limited. Our <a href="{{ route('fees.index') }}">fee guide</a> shows every published fee with its year and source; the <a href="{{ route('fees.total') }}">total cost page</a> adds the rest with the arithmetic shown.</p>

            <h2>7. After graduation</h2>
            <p>UK graduates pass the Medical Licensing Assessment within their degree, apply for GMC provisional registration and enter the two-year Foundation Programme. International graduates of UK schools apply on the same basis as home graduates, with visa sponsorship for Foundation Year 1. Post-study rules change often, so we keep that content factual, dated and separate on the <a href="{{ route('working.index') }}">working in the UK</a> page.</p>
            @if($gmc)
            <dl class="card mt-4">
                <x-fact-row :fact="$gmc->fact('mla')" label="Medical Licensing Assessment" />
                <x-fact-row :fact="$gmc->fact('foundation_eligibility')" label="Foundation Programme eligibility for international graduates" />
            </dl>
            @endif

            <h2>8. Is UK Medicine the right choice for you?</h2>
            <p>We do not answer that for anyone. The honest inputs are: whether a route is open on your qualifications, whether the five-to-six-year cost is sustainable with increases, whether you can meet the UCAT and English bars in the year you plan, and what you intend to do after graduation (train in the UK, return to Nigeria, or keep both open). The pages in this section give the sourced facts for each input; the <a href="{{ route('apply.eligibility') }}">eligibility check</a> gives you the first answer in seven questions.</p>

            <section class="not-prose mt-10">
                <h2 class="text-h3">Questions about UK Medicine</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach
                </div>
            </section>

            <x-cta-band class="mt-10" title="Not sure which route is yours?" :href="route('apply.eligibility')" label="Check your eligibility">Seven questions, no account needed. You will see which routes appear open on published requirements.</x-cta-band>
        </div>
        <aside class="lg:col-span-4 space-y-5">
            <div class="card"><p class="eyebrow mb-3">In this section</p><ul class="space-y-2 text-[0.9375rem]">
                <li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria</a></li><li><a href="{{ route('medicine.foundation') }}">Foundation routes to Medicine</a></li><li><a href="{{ route('requirements.gem') }}">Graduate entry with a Nigerian degree</a></li><li><a href="{{ route('requirements.index') }}">Requirements hub</a></li><li><a href="{{ route('schools.index') }}">UK medical school directory ({{ $schools }} schools open to international applicants)</a></li><li><a href="{{ route('working.index') }}">Working in the UK after the degree</a></li></ul></div>
            <div class="card"><p class="eyebrow mb-3">How we write these pages</p><p class="text-[0.9375rem] text-ink-700">Explanations are ours. Every specific requirement, fee or date is a separate record with an official source and a verification date, and is hidden until a reviewer has confirmed it on the official page. <a href="{{ route('verify') }}">How we verify</a>.</p></div>
        </aside>
    </div>
    <x-related :items="[['label' => 'From Nigeria: the honest guide', 'url' => route('medicine.nigeria')], ['label' => 'WAEC and UK Medicine', 'url' => route('requirements.waec')], ['label' => 'Fee guide', 'url' => route('fees.index')], ['label' => 'UCAT for Nigerian students', 'url' => route('admissions.ucat')], ['label' => 'Working in the UK', 'url' => route('working.index'), 'description' => 'Visa work rules, GMC registration, Foundation training and what is changing.'], ['label' => 'Questions applicants ask', 'url' => route('faq.index')]]" />
</article>
</x-layouts.public>
