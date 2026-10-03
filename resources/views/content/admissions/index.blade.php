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
    <section class="mt-10 prose-site"><h2>Interviews</h2><p>Most schools interview between December and March, increasingly by multiple mini-interview (MMI) and often online for international applicants. We record each school's published format in the <a href="{{ route('schools.index') }}">directory</a>. We do not publish interview "cut-offs" we cannot source.</p></section>
    <x-cta-band class="mt-10" title="Ready to begin your application?" :href="route('apply.index')" label="Apply Online" />
</article>
</x-layouts.public>
