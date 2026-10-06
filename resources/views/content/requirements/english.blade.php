<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements · English language', 'title' => 'English language requirements for UK Medicine: IELTS scores and WAEC English', 'lede' => 'Each UK medical school publishes its own English requirement, as an IELTS band with minimums in each component or an equivalent test it accepts. A few schools accept a WAEC or NECO English grade instead, and the grade they ask for differs. This page shows what each school publishes, which test to take and when, and how the visa layer works for an applicant from Nigeria.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section class="prose-site">
                <h2>1. Two layers: the medical school and the visa</h2>
                <p>Your English is assessed twice. The <strong>medical school</strong> sets the academic requirement for its course and checks it before an unconditional offer; this is the demanding layer, and it is the one every statement below describes. The <strong>Student visa</strong> has a separate English rule, set by the Home Office (section 6). Check both layers separately: evidence that satisfies one may not satisfy the other.</p>
            </section>

            <section><h2>2. Statements from a medical school's own admissions page ({{ $courseLevel->count() }})</h2>
                <p class="mt-2 text-ink-700">Course-specific wording from a medical school's own admissions page. These are the figures that bind; a university's general English page may be lower.</p>
                @include('content._statement-list', ['items' => $courseLevel, 'empty' => 'Course-level English requirements are being recorded school by school.'])
            </section>

            <section><h2>3. IELTS and equivalent bands by school ({{ $bands->count() }})</h2>
                <p class="mt-2 text-ink-700">Published for international applicants, mostly on university Nigeria or international pages. Where a statement reads "general", it applies to undergraduate study as a whole and Medicine may require more: check the course page, linked from each school's record.</p>
                @include('content._statement-list', ['items' => $bands, 'empty' => 'No band statements recorded yet.'])
            </section>

            <section><h2>4. Where WAEC or NECO English is accepted instead of a test ({{ $waecEnglish->count() }})</h2>
                <p class="mt-2 text-ink-700">The grade differs by school (C6 at one, C4 or B3 at another), some statements are general rather than Medicine-specific, and some add a time limit on the result. Read the exact wording, then confirm with the school in writing before you skip the IELTS.</p>
                @include('content._statement-list', ['items' => $waecEnglish, 'empty' => 'No school in our records publishes WAEC or NECO English acceptance for Medicine.'])
            </section>

            <section class="prose-site">
                <h2>5. Which test to take, and when</h2>
                <ul>
                    <li><strong>Check which tests each school accepts.</strong> IELTS Academic appears in the statements above; TOEFL iBT, PTE Academic or Cambridge C1 Advanced count only where a school lists them, at scores it maps itself on its English-language page.</li>
                    <li><strong>IELTS for UKVI (Academic)</strong> is the same test taken at a UKVI-approved centre. You need it only if a school or the visa route requires a Secure English Language Test; check with the school before booking.</li>
                    <li><strong>Validity.</strong> Results are usually accepted for two years from the test date at the point of enrolment; some schools (Plymouth, in our records) ask for a result within twelve months of entry. Sit the test so it is still valid in September of your entry year.</li>
                    <li><strong>Component minimums are the trap.</strong> An overall 7.5 with a 6.5 in Writing fails a "7.0 in each component" rule. Plan your preparation around your weakest component, and allow time for a second sitting.</li>
                    <li><strong>Timing.</strong> The school needs the result before it confirms your offer, and you need it before the CAS. Taking the test before you submit UCAS lets you upload it with the application; taking it after an offer is possible but leaves less margin.</li>
                </ul>
            </section>

            <section class="prose-site">
                <h2>6. The visa layer for an applicant from Nigeria</h2>
                <p>The Student visa has its own English requirement, set by the Home Office. Read the English language section of <a href="https://www.gov.uk/student-visa" rel="noopener" target="_blank">GOV.UK's Student visa guidance</a> and ask your university's visa team how your English will be assessed for your Confirmation of Acceptance for Studies. The <a href="{{ route('fees.total') }}">total cost page</a> carries the visa facts we have verified.</p>
            </section>

            <section>
                <h2>Questions about English evidence</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach
                </div>
            </section>

            <x-cta-band title="Check all your requirements together" :href="route('apply.eligibility')" label="Check your eligibility">The eligibility check asks which English evidence you hold and shows the routes that appear open.</x-cta-band>
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria: the guide</a></li><li><a href="{{ route('requirements.waec') }}">WAEC and UK Medicine</a></li><li><a href="{{ route('requirements.neco') }}">NECO and UK Medicine</a></li><li><a href="{{ route('requirements.index') }}">Requirements hub</a></li><li><a href="{{ route('schools.index') }}?international=accepts">Directory</a></li></ul></div></aside>
    </div>
    <x-route-map current="requirements" />
</article>
</x-layouts.public>
