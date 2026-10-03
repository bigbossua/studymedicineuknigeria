<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements hub', 'title' => 'What do I need to study Medicine in the UK?', 'lede' => 'Ten things every UK medical school looks at. Requirements vary by school, so each section links to what individual schools publish and to the directory where you can compare them.', 'seo' => $seo])
    <ol class="mt-10 grid gap-4 md:grid-cols-2">
        @foreach([
            ['Academic qualifications', 'A-levels or IB for standard entry; a degree for graduate entry. WASSCE and NECO are treated as the GCSE layer, not the entry qualification.', route('requirements.alevels'), 'A-levels and IB'],
            ['Subject requirements', 'Chemistry and Biology (or another science) in almost every offer; some schools add Mathematics or Physics rules.', route('requirements.alevels'), 'Subject rules by school'],
            ['Nigerian school-leaving results', 'What each school says about WAEC and NECO, and the grades they ask for in English and Mathematics.', route('requirements.waec'), 'WAEC and NECO statements'],
            ['English language', 'IELTS 7.0–7.5 overall is typical for Medicine; a few schools accept WAEC or NECO English for specific courses.', route('requirements.english'), 'English requirements'],
            ['Admissions tests', 'The UCAT for most schools, GAMSAT for some graduate programmes, none at a small number of direct-application schools.', route('admissions.ucat'), 'UCAT from Nigeria'],
            ['Personal statement and application', 'Three structured questions on UCAS (4,000 characters). Direct-application schools have their own forms.', route('admissions.howto'), 'How to apply'],
            ['References', 'One academic reference from a teacher or lecturer who knows your recent study.', route('admissions.howto'), 'What UCAS asks'],
            ['Passport and identity', 'Your name must match your passport exactly across UCAS, the university and the visa.', route('apply.index'), 'Our document checklist'],
            ['Financial planning', 'Fees, visa, health surcharge, maintenance funds and living costs; universities and UKVI both check this.', route('fees.index'), 'Fee guide'],
            ['Deadlines and timing', 'UCAS medicine deadline in mid-October; UCAT before that; interviews December–March.', route('admissions.ucas2027'), 'Timeline'],
        ] as $i => [$h, $p, $u, $l])
            <li class="card flex flex-col"><span class="font-mono text-[0.8125rem] text-ink-500">{{ sprintf('%02d', $i + 1) }}</span><h2 class="text-xl font-serif font-semibold mt-1">{{ $h }}</h2><p class="mt-2 text-[0.9375rem] text-ink-700 flex-1">{{ $p }}</p><a href="{{ $u }}" class="btn btn-tertiary mt-3 self-start">{{ $l }}</a></li>
        @endforeach
    </ol>
    <section class="mt-12 grid lg:grid-cols-12 gap-8">
        <div class="lg:col-span-8 prose-site">
            <h2>Requirements vary by medical school</h2>
            <p>New to all of this? Start with the <a href="{{ route('medicine.nigeria') }}">guide for applicants from Nigeria</a>, which puts these ten requirements in order for a WAEC, <a href="{{ route('requirements.neco') }}">NECO</a>, A-level or degree holder, then come back here for the detail. The <a href="{{ route('medicine.index') }}">Medicine pillar</a> explains how the standard, graduate and foundation routes differ, and the <a href="{{ route('faq.index') }}">questions page</a> gives short answers with links back here.</p>
            <p>Two schools can ask for the same A-level grades and yet treat WASSCE English, the UCAT or a Nigerian degree differently. That is why we never write "UK medical schools accept X". Each page in this hub shows what individual schools publish, with the source and the date we last checked it, and the <a href="{{ route('schools.index') }}">directory</a> lets you compare them side by side.</p>
            <dl class="card not-prose mt-4">
                <x-fact-row :fact="$ucas?->fact('deadline_medicine')" label="UCAS medicine deadline, 2027 entry" />
                <x-fact-row :fact="$ucat?->fact('structure')" label="UCAT structure" />
            </dl>
        </div>
        <aside class="lg:col-span-4"><x-cta-band title="Not sure whether your Nigerian qualifications meet the requirements?" :href="route('apply.eligibility')" label="Check your eligibility" class="flex-col items-start">Seven questions, no account needed.</x-cta-band></aside>
    </section>
</article>
</x-layouts.public>
