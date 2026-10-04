<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Admissions · process', 'title' => 'How to apply to UK Medicine from Nigeria: UCAS and direct-application medical schools', 'lede' => 'Most UK medical schools are applied to through UCAS, in your own account, by the mid-October deadline. A few take direct applications. Either way the application is yours; we prepare and check everything with you and guide you through the official route. We are not a UCAS registered centre and not an agent of any university.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section class="prose-site">
                <h2>1. Before you apply: route, test and shortlist</h2>
                <p>An application is only as strong as the decisions before it. Settle three things first: the <a href="{{ route('medicine.nigeria') }}">route that is open on your qualifications</a> (standard entry with A-levels or IB, a <a href="{{ route('medicine.foundation') }}">foundation year that leads to Medicine</a>, or <a href="{{ route('requirements.gem') }}">entry as a graduate</a>); the admissions test the schools on your list use, sat in the summer before you apply (<a href="{{ route('admissions.ucat') }}">UCAT from Nigeria</a>); and a shortlist drawn only from <a href="{{ route('schools.index') }}?international=accepts">schools that admit international applicants</a>, checked against each school's published requirements for WAEC or NECO holders, English evidence and fees.</p>
            </section>

            <section class="prose-site">
                <h2>2. Applying through UCAS as an individual</h2>
                <ol>
                    <li><strong>Create your UCAS Hub account</strong> and start an undergraduate application for the right entry year. Use the name on your international passport exactly; it must match across UCAS, the university, your English test and the visa.</li>
                    <li><strong>Enter your qualifications exactly as certificated:</strong> WASSCE or NECO subjects and grades, A-levels (achieved or predicted) or IB, any degree, and your English test if you have one. Do not translate grades into UK equivalents yourself; universities do that.</li>
                    <li><strong>Choose up to four medicine courses.</strong> The fifth choice must be a different course (many applicants choose a biomedical or related degree; it is not compulsory to use the fifth).</li>
                    <li><strong>Answer the three personal-statement questions</strong> within the overall character limit. Each answer has a minimum length. The questions ask why this course, how your studies prepared you, and what else you have done to prepare: Nigerian clinical shadowing, volunteering and reflective reading all belong here.</li>
                    <li><strong>Arrange your reference.</strong> As an independent applicant you enter a referee's details and UCAS emails them a secure link. The referee must know you academically or professionally (a teacher, principal or lecturer); family and friends are not accepted. If your school is a UCAS registered centre, you apply through its buzzword instead and the school adds predicted grades and the reference.</li>
                    <li><strong>Upload supporting documents</strong> where UCAS offers it: certificates, transcripts, translations and English-language evidence can travel with the application rather than being requested by each school separately.</li>
                    <li><strong>Pay the application fee and submit</strong> before the medicine deadline, allowing for your referee's time; the application cannot be sent without the reference.</li>
                </ol>
                <dl class="card not-prose mt-4">
                    <x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="UCAS medicine deadline (2027 entry)" />
                    <x-fact-row :fact="$ucas?->fact('max_medicine_choices')" label="Choices" />
                    <x-fact-row :fact="$ucas?->fact('personal_statement_format')" label="Personal statement" />
                    <x-fact-row :fact="$ucas?->fact('document_upload')" label="Document upload" />
                    <x-fact-row :fact="$ucas?->fact('application_fee_gbp')" label="UCAS application fee" />
                    <x-fact-row :fact="$ucas?->fact('agent_statement')" label="What UCAS says about agents" />
                </dl>
            </section>

            <section class="prose-site">
                <h2>3. The documents a Nigerian applicant should have ready</h2>
                <ul>
                    <li>International passport (data page), valid well beyond your intended arrival.</li>
                    <li>WAEC or NECO certificate or statement of result, and the scratch-card or verification details a university may ask for.</li>
                    <li>A-level or IB certificates, or your school's predicted grades letter if results are pending; degree certificate and transcripts for graduates.</li>
                    <li>English-language evidence: an IELTS (Academic or UKVI) or other accepted test, or the WAEC/NECO English grade where a school <a href="{{ route('requirements.english') }}">publishes that it counts</a>.</li>
                    <li>Referee's name, role, institution and email, agreed with them in advance.</li>
                    <li>Evidence of work experience or shadowing (dates, setting, supervisor) for your statement and interviews; it is not uploaded to UCAS but you will be asked about it.</li>
                </ul>
                <p>In your portal we turn this into a personal checklist, review each upload for legibility and name-matching, and tell you what each shortlisted school additionally asks for.</p>
            </section>

            <section id="direct"><h2>4. Medical schools that take direct applications ({{ $direct->count() }})</h2>
                <p class="text-ink-700 mt-2">These schools accept applications through their own portals (some also through UCAS). Their deadlines, tests, intakes and fees differ from the UCAS norm, and a direct application does not use one of your four UCAS medicine choices. Each school's page carries the source.</p>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($direct->sortBy(fn($c)=>$c->university->name) as $c)<li class="card"><a href="{{ route('schools.show', $c->university) }}" class="font-semibold">{{ $c->university->name }}</a><p class="text-[0.9375rem] text-ink-500">{{ $c->title }} · {{ $c->application_route === 'BOTH' ? 'UCAS or direct' : 'Direct' }}{{ $c->admissions_test ? ' · '.($c->admissions_test === 'NONE' ? 'no test' : $c->admissions_test) : '' }}</p></li>@endforeach</ul>
            </section>

            <section class="prose-site">
                <h2>5. If you have missed the UCAS medicine deadline, or have no UCAT result</h2>
                <p>UCAS does not reopen medicine for the cycle, and a UCAT-requiring school cannot consider you without a score from the current test year. The honest options are the direct-application schools above for the coming intake, each with its own deadline and selection method, or a planned application for the following year: UCAT in the summer, UCAS by mid-October. Our <a href="{{ route('admissions.ucas2027') }}">timeline page</a> sets out both calendars.</p>
                @if($ucat)
                <dl class="card not-prose mt-4">
                    <x-fact-row :fact="$ucat->fact('testing_window')" label="UCAT testing window (2027 entry)" />
                </dl>
                @endif
            </section>

            <section class="prose-site">
                <h2>6. After you submit</h2>
                <ol>
                    <li><strong>Interviews</strong> run from December to March, usually multiple mini-interviews and often online for applicants abroad. We record each school's published format in the directory.</li>
                    <li><strong>Offers</strong> arrive between January and May and are usually conditional on final grades and English evidence.</li>
                    <li><strong>Firm and insurance choices</strong> are made in UCAS by its reply deadline.</li>
                    <li><strong>Deposit, CAS and visa.</strong> After you meet the conditions and pay the university's deposit, it issues the Confirmation of Acceptance for Studies; you then apply for the Student visa with the maintenance evidence the Home Office requires, book the TB test, and plan travel. Costs and timings are on the <a href="{{ route('fees.total') }}">total cost page</a>.</li>
                </ol>
            </section>

            <section class="prose-site">
                <h2>7. How we fit in</h2>
                <p>In your portal we collect your qualifications once, build a document checklist for the schools you are considering, review each document, give structural feedback on your statement, and prepare a complete package. For UCAS we produce a copy-across guide for every section. Only after you approve the package does submission begin, and where the university requires you to submit personally, you do, with us beside you. Admission decisions are made solely by universities; we make no claim about outcomes. What each service level includes and excludes is on the <a href="{{ route('apply.services') }}">services page</a>; details on <a href="{{ route('apply.index') }}">Apply Online</a> and <a href="{{ route('status') }}">Our status</a>.</p>
            </section>

            <section>
                <h2>Questions about applying</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach
                </div>
            </section>

            <x-cta-band title="Ready to begin your application?" :href="route('apply.index')" label="Apply Online">Create your account, answer the eligibility questions and receive your personal document checklist.</x-cta-band>
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria: the guide</a></li><li><a href="{{ route('admissions.ucas2027') }}">Deadlines and timeline</a></li><li><a href="{{ route('admissions.ucat') }}">UCAT for Nigerian students</a></li><li><a href="{{ route('requirements.english') }}">English requirements</a></li><li><a href="{{ route('faq.index') }}">Questions applicants ask</a></li><li><a href="{{ route('status') }}">Our status (not an agent)</a></li></ul></div></aside>
    </div>
    <x-route-map current="application" />
</article>
</x-layouts.public>
