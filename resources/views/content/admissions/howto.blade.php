<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Admissions · process', 'title' => 'How to apply to UK Medicine from Nigeria: UCAS and direct-application medical schools', 'lede' => 'Most UK medical schools are applied to through UCAS, in your own account, by the mid-October deadline. A few take direct applications. Either way the application is yours; we prepare and check everything with you and guide you through the official route. We are not a UCAS registered centre.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section class="prose-site">
                <h2>Applying through UCAS as an individual</h2>
                <ol><li>Create your UCAS Hub account and start an undergraduate application for the right entry year.</li><li>Enter your qualifications exactly as certificated: WASSCE/NECO subjects and grades, A-levels (achieved or predicted) and your English test.</li><li>Choose up to four medicine courses; a fifth choice must be a different course.</li><li>Answer the three personal-statement questions within the overall character limit.</li><li>Arrange your reference: a teacher or lecturer, submitted through UCAS.</li><li>Upload supporting documents where UCAS allows it, pay the fee and submit before the medicine deadline.</li></ol>
                <dl class="card not-prose mt-4">
                    <x-fact-row :fact="$ucas?->fact('max_medicine_choices')" label="Choices" />
                    <x-fact-row :fact="$ucas?->fact('personal_statement_format')" label="Personal statement" />
                    <x-fact-row :fact="$ucas?->fact('document_upload')" label="Document upload" />
                    <x-fact-row :fact="$ucas?->fact('application_fee_gbp')" label="UCAS application fee" />
                    <x-fact-row :fact="$ucas?->fact('agent_statement')" label="What UCAS says about agents" />
                </dl>
            </section>
            <section><h2>Medical schools that take direct applications ({{ $direct->count() }})</h2>
                <p class="text-ink-700 mt-2">These schools accept applications through their own portals (some also through UCAS). Their deadlines, tests and fees differ from the UCAS norm; each school's page carries the source.</p>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($direct->sortBy(fn($c)=>$c->university->name) as $c)<li class="card"><a href="{{ route('schools.show', $c->university) }}" class="font-semibold">{{ $c->university->name }}</a><p class="text-[0.9375rem] text-ink-500">{{ $c->title }} · {{ $c->application_route === 'BOTH' ? 'UCAS or direct' : 'Direct' }}{{ $c->admissions_test ? ' · '.($c->admissions_test === 'NONE' ? 'no test' : $c->admissions_test) : '' }}</p></li>@endforeach</ul>
            </section>
            <section class="prose-site">
                <h2>How we fit in</h2>
                <p>In your portal we collect your qualifications once, build a document checklist for the schools you are considering, review each document, give structural feedback on your statement, and prepare a complete package. For UCAS we produce a copy-across guide for every section. Only after you approve the package does submission begin, and where the university requires you to submit personally, you do, with us beside you. Details on <a href="{{ route('apply.index') }}">Apply Online</a>.</p>
            </section>
            <x-cta-band title="Ready to begin your application?" :href="route('apply.index')" label="Apply Online" />
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('admissions.ucas2027') }}">Deadlines and timeline</a></li><li><a href="{{ route('admissions.ucat') }}">UCAT</a></li><li><a href="{{ route('status') }}">Our status (not an agent)</a></li></ul></div></aside>
    </div>
</article>
</x-layouts.public>
