<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Admissions · timeline', 'title' => 'UCAS deadlines and timeline for Medicine, 2027 entry, and how to plan 2028', 'lede' => 'Every date that matters, in order, for an applicant in Nigeria. Medicine has the earliest UCAS deadline of any course and the UCAT must be sat before it, so the whole year hinges on two windows.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section><h2>2027 entry: the official dates</h2>
                <dl class="card mt-4">
                    <x-fact-row :fact="$ucas?->fact('applications_open')" label="UCAS applications open" />
                    <x-fact-row :fact="$ucat?->fact('testing_window')" label="UCAT testing window" />
                    <x-fact-row :fact="$ucas?->fact('submission_opens')" label="UCAS submissions open" />
                    <x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="Medicine deadline (equal consideration)" />
                    <x-fact-row :fact="$ucat?->fact('results_to_universities')" label="UCAT results to universities" />
                    <x-fact-row :fact="$ucas?->fact('deadline_main')" label="Main deadline (other courses)" />
                    <x-fact-row :fact="$ucas?->fact('extra_opens')" label="UCAS Extra opens" />
                    <x-fact-row :fact="$ucas?->fact('clearing_opens')" label="Clearing opens" />
                    <x-fact-row :fact="$ucas?->fact('final_date')" label="Final date to add choices" />
                </dl></section>
            <section class="prose-site">
                <h2>What happens after you apply</h2>
                <ol><li><strong>December–March:</strong> interviews, mostly multiple mini-interviews, often online for international applicants.</li><li><strong>By May:</strong> most decisions; you reply to offers by the UCAS deadline for your decision date.</li><li><strong>June–August:</strong> meet conditions (final grades, English test), pay the university's international deposit, receive your Confirmation of Acceptance for Studies (CAS).</li><li><strong>From up to six months before the course:</strong> apply for the Student visa with CAS, maintenance evidence and a TB test certificate from an approved clinic in Nigeria.</li><li><strong>September:</strong> travel and enrol.</li></ol>
                <dl class="card not-prose mt-4">
                    <x-fact-row :fact="$visa?->fact('tb_test')" label="TB test" />
                    <x-fact-row :fact="$visa?->fact('maintenance_outside_london_monthly_gbp')" label="Maintenance funds to show (outside London, per month)" suffix=" per month" />
                    <x-fact-row :fact="$visa?->fact('maintenance_london_monthly_gbp')" label="Maintenance funds to show (London, per month)" suffix=" per month" />
                </dl>
                <h2>Planning 2028 entry from Nigeria</h2>
                <ul><li><strong>Now:</strong> confirm your qualification route (<a href="{{ route('requirements.index') }}">requirements hub</a>), shortlist schools that accept international applicants (<a href="{{ route('schools.index') }}">directory</a>), book an English test if needed.</li><li><strong>May–June 2027:</strong> UCAT registration opens; register on day one and book a Lagos or Abuja slot as soon as booking opens.</li><li><strong>July–September 2027:</strong> sit the UCAT early in the window.</li><li><strong>September 2027:</strong> submit UCAS with up to four medicine choices; the deadline will again fall in mid-October 2027.</li></ul>
                <p class="text-[0.9375rem] text-ink-500">2028-cycle dates are published by UCAS and the UCAT Consortium in spring 2027; we will add them with sources when they appear.</p>
            </section>
            <x-cta-band title="Start now, submit when the window opens" :href="route('apply.index')" label="Apply Online">Your application, documents and shortlist are prepared in your portal; nothing is sent to any university until you approve it.</x-cta-band>
        </div>
        <aside class="lg:col-span-4 space-y-5"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('admissions.ucat') }}">UCAT from Nigeria</a></li><li><a href="{{ route('admissions.howto') }}">How to apply</a></li><li><a href="{{ route('fees.total') }}">Total cost</a></li></ul></div></aside>
    </div>
</article>
</x-layouts.public>
