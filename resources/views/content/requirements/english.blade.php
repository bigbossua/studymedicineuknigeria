<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements · English language', 'title' => 'English language requirements for UK Medicine: IELTS scores and WAEC English', 'lede' => 'Medicine has the highest English requirements of any undergraduate course: published bands are typically IELTS 7.0 to 7.5 overall with minimums in each component. A few schools accept a strong WAEC or NECO English grade instead. For the Student visa, universities assess English at degree level themselves.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section><h2>Medicine course requirements recorded ({{ $courseLevel->count() }})</h2>@include('content._statement-list', ['items' => $courseLevel, 'empty' => 'Course-level English requirements are being recorded school by school.'])</section>
            <section><h2>What universities\' Nigeria pages say about English ({{ $nigeriaPage->count() }})</h2>@include('content._statement-list', ['items' => $nigeriaPage])</section>
            <section class="prose-site">
                <h2>Practical notes</h2>
                <ul><li>Test results are usually valid for two years at the point of enrolment; time your test accordingly.</li><li>Where a school lists IELTS, it normally also accepts TOEFL iBT, PTE Academic and others at equivalent scores; check the school's English-language page for the exact mapping.</li><li>Nigeria is not on the UK's list of majority-English-speaking countries for visa purposes, but for degree-level study the university, not UKVI, assesses your English and confirms it on your CAS.</li></ul>
            </section>
            <x-cta-band title="Check all your requirements together" :href="route('apply.eligibility')" label="Check your eligibility" />
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('requirements.waec') }}">WAEC and UK Medicine</a></li><li><a href="{{ route('requirements.index') }}">Requirements hub</a></li><li><a href="{{ route('schools.index') }}">Directory</a></li></ul></div></aside>
    </div>
</article>
</x-layouts.public>
