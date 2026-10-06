<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Admissions', 'title' => 'Admissions: UCAS, the UCAT, interviews and how to apply to UK Medicine', 'lede' => 'The UK medicine admissions process for an applicant in Nigeria, in order: the UCAT, UCAS by the medicine deadline, interviews and offers, then deposit, CAS and visa.', 'seo' => $seo])
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
            <li><strong>Choose the route first.</strong> If you are applying from Nigeria, the <a href="{{ route('medicine.nigeria') }}">guide for Nigerian applicants</a> sets out which routes are open on WAEC, NECO, A-levels or a degree. Standard entry (A100), <a href="{{ route('requirements.gem') }}">graduate entry</a> or a <a href="{{ route('medicine.foundation') }}">foundation or gateway year</a> depends on your qualifications; read <a href="{{ route('requirements.index') }}">what schools require</a> before anything else.</li>
            <li><strong>Register for the admissions test in time.</strong> Check which test each school on your list names. For the <a href="{{ route('admissions.ucat') }}">UCAT</a>, registration and booking close before the testing window ends (dates above and on the UCAT page). Missing the window closes the schools that use it for the cycle.</li>
            <li><strong>Shortlist schools that admit international applicants.</strong> Use the <a href="{{ route('schools.index') }}">directory</a>: international eligibility, test, fee and application route are listed per school with the source.</li>
            <li><strong>Apply through UCAS by the medicine deadline</strong> (the date is above; it falls in the year before entry) with up to four medicine choices, a personal statement and a reference. A few schools take <a href="{{ route('admissions.howto') }}">direct applications</a> instead.</li>
            <li><strong>Interviews</strong> follow over the winter and spring. Interview dates and formats are published by each school (some in person, some online); see each school's page.</li>
            <li><strong>Offer, deposit, CAS and visa.</strong> After accepting an offer, international students pay a deposit, receive the Confirmation of Acceptance for Studies and apply for the Student visa with the maintenance evidence the Home Office requires. The <a href="{{ route('fees.total') }}">total cost page</a> lists each of these amounts with its source.</li>
        </ol>
        <h2>Points to check early</h2>
        <ul>
            <li>English evidence: where a school accepts WAEC English and where it asks for IELTS, and by when (<a href="{{ route('requirements.english') }}">English requirements</a>).</li>
            <li>The UCAT Consortium does not publish centre capacity; book as soon as booking opens.</li>
            <li>The four-choice rule for medicine: the fifth UCAS choice must be a different course.</li>
            <li>Short answers to common questions are on the <a href="{{ route('faq.index') }}">questions page</a>.</li>
            <li>Each dated fact on this site carries a last-verified chip; the <a href="{{ route('admissions.ucas2027') }}">2027 timeline</a> is the page to re-check before each deadline.</li>
        </ul>
    </section>
    <section class="mt-10 max-w-4xl">
        <h2>Where are you today? The next step for each position</h2>
        <p class="mt-2 text-ink-700">The cycle is unforgiving but predictable. Find your row; the right-hand column is the one thing to do next.</p>
        <table class="table-stack mt-4">
            <thead><tr><th>Your position</th><th>What it means</th><th>Next step</th></tr></thead>
            <tbody>
                <tr><td data-label="Position">Still choosing a route (WAEC/NECO, A-levels, degree)</td><td data-label="Means">Nothing in the calendar binds you yet; the route decides which test and which schools</td><td data-label="Next"><a href="{{ route('requirements.index') }}">Requirements hub: which route is yours</a></td></tr>
                <tr><td data-label="Position">Route chosen, UCAT not yet registered, applying next cycle</td><td data-label="Means">UCAT registration and booking open on dates the UCAT Consortium publishes for each cycle (the UCAT page shows the current dates). The Consortium does not publish centre capacity, so book as soon as booking opens</td><td data-label="Next"><a href="{{ route('admissions.ucat') }}">UCAT: dates and booking from Nigeria</a></td></tr>
                <tr><td data-label="Position">UCAT sat this summer, UCAS not yet submitted</td><td data-label="Means">You have until the UCAS medicine deadline (above); four medicine choices, a reference and the three-question statement</td><td data-label="Next"><a href="{{ route('admissions.howto') }}">How to apply, step by step</a></td></tr>
                <tr><td data-label="Position">No UCAT result and the deadline has passed</td><td data-label="Means">UCAT-requiring schools are closed for this cycle; direct-application schools and next year remain</td><td data-label="Next"><a href="{{ route('admissions.howto') }}#direct">Direct-application schools</a> · <a href="{{ route('admissions.ucas2027') }}">Plan the next cycle</a></td></tr>
                <tr><td data-label="Position">Submitted, waiting</td><td data-label="Means">Interview dates and formats are published by each school (some in person, some online); offers follow</td><td data-label="Next"><a href="{{ route('schools.index') }}">Each school's published interview format</a></td></tr>
                <tr><td data-label="Position">Offer received</td><td data-label="Means">Conditions, deposit, CAS, then the Student visa with maintenance evidence and a TB test</td><td data-label="Next"><a href="{{ route('fees.total') }}">Total cost and the visa sequence</a></td></tr>
            </tbody>
        </table>
    </section>
    <section class="mt-10 prose-site"><h2>Interviews</h2><p>Each school publishes its own interview dates and format (panel or multiple mini-interview, in person or online), and some set different arrangements for international applicants. We record each school's published format in the <a href="{{ route('schools.index') }}">directory</a>. We do not publish interview "cut-offs" we cannot source.</p></section>
    <section class="mt-10 max-w-3xl">
        <h2>Questions about admissions</h2>
        <div class="mt-4 divide-y divide-ink-100">@foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach</div>
    </section>
    <x-cta-band class="mt-10" title="Ready to begin your application?" :href="route('apply.index')" label="Apply Online" />
    <x-route-map current="application" />
</article>
</x-layouts.public>
