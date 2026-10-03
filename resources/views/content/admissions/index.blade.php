<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Admissions', 'title' => 'Admissions: UCAS, the UCAT, interviews and how to apply to UK Medicine', 'lede' => 'The UK medicine admissions process for an applicant in Nigeria, in order: the UCAT in summer, UCAS by mid-October, interviews from December, offers, then deposit, CAS and visa.', 'seo' => $seo])
    <div class="mt-10 grid gap-4 md:grid-cols-3">
        @foreach([['UCAS deadlines and timeline', 'Every date for 2027 entry and how to plan 2028.', route('admissions.ucas2027')], ['UCAT for Nigerian students', 'Dates, structure, fees, test centres in Nigeria, which schools require it.', route('admissions.ucat')], ['How to apply', 'UCAS as an individual, the four-choice rule, personal statement, references, direct-application schools.', route('admissions.howto')]] as [$h,$p,$u])
            <a href="{{ $u }}" class="card card-link"><h2 class="text-xl font-serif font-semibold">{{ $h }}</h2><p class="mt-2 text-[0.9375rem] text-ink-700">{{ $p }}</p></a>
        @endforeach
    </div>
    <dl class="card mt-8 max-w-3xl">
        <x-fact-row :fact="$ucat?->fact('testing_window')" label="UCAT testing window (2027 entry)" />
        <x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="UCAS medicine deadline (2027 entry)" />
        <x-fact-row :fact="$ucas?->fact('max_medicine_choices')" label="Choices" />
    </dl>
    <section class="mt-10 prose-site max-w-3xl"><h2>The process in order</h2>
        <ol>
            <li><strong>Choose the route first.</strong> Standard entry (A100), <a href="{{ route('requirements.gem') }}">graduate entry</a> or a <a href="{{ route('medicine.foundation') }}">foundation or gateway year</a> depends on your qualifications; read <a href="{{ route('requirements.index') }}">what schools require</a> before anything else.</li>
            <li><strong>Register for the admissions test in time.</strong> Most UK medical schools use the <a href="{{ route('admissions.ucat') }}">UCAT</a>, sat in the summer before you apply, with registration closing weeks earlier. Missing the window closes those schools for the cycle.</li>
            <li><strong>Shortlist schools that admit international applicants.</strong> Use the <a href="{{ route('schools.index') }}">directory</a>: international eligibility, test, fee and application route are listed per school with the source.</li>
            <li><strong>Apply through UCAS by the medicine deadline</strong> (mid-October in the year before entry) with up to four medicine choices, a personal statement and a reference. A few schools take <a href="{{ route('admissions.howto') }}">direct applications</a> instead.</li>
            <li><strong>Interviews</strong> run from December to March, often online for applicants abroad.</li>
            <li><strong>Offer, deposit, CAS and visa.</strong> Offers arrive from January to May; after accepting, international students pay a deposit, receive the Confirmation of Acceptance for Studies and apply for the Student visa with the maintenance evidence the Home Office requires. The <a href="{{ route('fees.total') }}">total cost page</a> lists each of these amounts with its source.</li>
        </ol>
        <h2>What applicants from Nigeria most often miss</h2>
        <ul>
            <li>English evidence: where a school accepts WAEC English and where it asks for IELTS, and by when (<a href="{{ route('requirements.english') }}">English requirements</a>).</li>
            <li>Test centres and dates for the UCAT in Nigeria are limited; book as soon as registration opens.</li>
            <li>The four-choice rule for medicine: the fifth UCAS choice must be a different course.</li>
            <li>Each dated fact on this site carries a last-verified chip; the <a href="{{ route('admissions.ucas2027') }}">2027 timeline</a> is the page to re-check before each deadline.</li>
        </ul>
    </section>
    <section class="mt-10 prose-site"><h2>Interviews</h2><p>Most schools interview between December and March, increasingly by multiple mini-interview (MMI) and often online for international applicants. We record each school's published format in the <a href="{{ route('schools.index') }}">directory</a>. We do not publish interview "cut-offs" we cannot source.</p></section>
    <x-cta-band class="mt-10" title="Ready to begin your application?" :href="route('apply.index')" label="Apply Online" />
</article>
</x-layouts.public>
