<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Our status', 'title' => 'Our status: independence, registrations and agreements', 'lede' => 'A plain, dated statement of what we are and are not. We update this page whenever anything changes.', 'seo' => $seo])
    <div class="mt-10 max-w-3xl space-y-6">
        <section class="card"><p class="eyebrow mb-2">Independence</p><p class="text-[0.9375rem]">StudyMedicineUKNigeria is an independent application-support service for Nigerian students applying to study Medicine and related healthcare courses in the UK. We are not an agent of, or affiliated with, any university, UCAS, the British Council, the General Medical Council or any other body unless expressly stated on this page. We do not receive commission from any university. Students pay us directly for our time and support; we will tell you in writing before you pay if that ever changes.</p></section>
        <section class="card"><p class="eyebrow mb-2">Agreements with universities</p><p class="text-[0.9375rem] font-semibold">We hold no agreements with any university.</p><p class="text-[0.9375rem] text-ink-700 mt-1">If a referral, introducer or representative agreement is ever signed, it will be listed here with the university's name, the agreement type and its dates, and only then will any related wording appear on this site.</p></section>
        <section class="card"><p class="eyebrow mb-2">UCAS</p><p class="text-[0.9375rem]">We help you prepare and check your own UCAS application. We are not a UCAS registered centre.</p></section>
        <section class="card"><p class="eyebrow mb-2">Legal entity and registrations</p>
            <dl class="text-[0.9375rem] grid sm:grid-cols-12 gap-y-2">
                <dt class="sm:col-span-4 text-ink-500">Operating entity</dt><dd class="sm:col-span-8">{{ config('site.legal_name') ?? 'To be published on incorporation' }}{{ config('site.company_number') ? ' · '.config('site.company_number') : '' }}</dd>
                <dt class="sm:col-span-4 text-ink-500">Registered address</dt><dd class="sm:col-span-8">{{ config('site.address') ?? 'To be published' }}</dd>
                <dt class="sm:col-span-4 text-ink-500">UK ICO data-protection fee</dt><dd class="sm:col-span-8">To be published once registered</dd>
                <dt class="sm:col-span-4 text-ink-500">Nigeria Data Protection Commission</dt><dd class="sm:col-span-8">Registration assessed against the "controller of major importance" threshold; status to be published</dd>
                <dt class="sm:col-span-4 text-ink-500">Training</dt><dd class="sm:col-span-8">British Council UK agent and counsellor training certificate: to be published on completion (individual certificate, not an agency approval)</dd>
            </dl></section>
        <section class="card"><p class="eyebrow mb-2">Outcomes</p><p class="text-[0.9375rem]">We cannot and do not guarantee offers, interviews, visas or scholarships. All admissions decisions are made solely by universities.</p></section>
        <section class="prose-site">
            <h2>What the words mean, so you can check any claim</h2>
            <p>The UK sector uses these terms with specific meanings. Knowing them lets you test what any service, including ours, says about itself.</p>
            <ul>
                <li><strong>Education agent / recruitment partner.</strong> A business contracted by a university to recruit students for it, normally paid commission by the university. Universities publish lists of their appointed agents; if a company claims to represent a university and is not on that university's list, the claim is false. <em>We are not one.</em></li>
                <li><strong>Official or authorised representative.</strong> The same appointed, contracted agent, in the wording universities use on their "representatives in your country" pages. <em>We are not one.</em></li>
                <li><strong>Referral partner / introducer.</strong> Paid for introductions under a written agreement, without authority to act for the university. <em>We hold no such agreement; if one is ever signed it will be listed above with its dates.</em></li>
                <li><strong>Sub-agent.</strong> Works under an appointed agent's contract and must be disclosed to the university. <em>We are not one.</em></li>
                <li><strong>Independent counsellor / application-support service.</strong> Advises and supports students who pay for the service directly, with no contract with any provider. <em>This is what we are.</em></li>
                <li><strong>UCAS registered centre.</strong> An operational registration that lets a school or adviser submit and track applications and attach references. It is not a quality mark and not an endorsement by UCAS. <em>We are not registered; you apply as an individual in your own UCAS account and we prepare and check everything with you.</em></li>
                <li><strong>British Council certified agent and counsellor training.</strong> An individual training certificate, not an approval of an agency. We will publish ours when complete and will never describe it as "British Council approved".</li>
            </ul>
            <h2>How to check us, or anyone</h2>
            <ol>
                <li>Ask for the university's name and the agreement type for any claimed partnership, then look for the company on that university's published representative list.</li>
                <li>Ask whether the service receives commission from universities and whether its fee is separate from university costs. Ours is, and we receive none.</li>
                <li>Ask who submits the application and in whose account. With us, it is yours, and nothing is submitted without your recorded approval.</li>
                <li>Ask what exactly is included for the fee (<a href="{{ route('apply.services') }}">our services page</a> lists deliverables and exclusions) and for the refund policy and the service terms in writing before paying: <a href="{{ route('legal.application-terms') }}">ours</a> and <a href="{{ route('legal.refunds') }}">our refund policy</a> are published.</li>
            </ol>
        </section>
        <section>
            <h2 class="text-h3">Questions about our status</h2>
            <div class="mt-4 divide-y divide-ink-100">@foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach</div>
        </section>
        <p class="text-[0.8125rem] text-ink-500">This page was last updated on 3 October 2026.</p>
    </div>
    <x-related :items="[['label' => 'About us', 'url' => route('about'), 'description' => 'Who we are and how we work.'], ['label' => 'Application service terms', 'url' => route('legal.application-terms'), 'description' => 'What the paid service does and does not include.'], ['label' => 'Contact', 'url' => route('contact'), 'description' => 'How to reach us and what to include.'], ['label' => 'How we verify', 'url' => route('verify'), 'description' => 'Source hierarchy, labels and review cadence.'], ['label' => 'Study Medicine in the UK from Nigeria', 'url' => route('medicine.nigeria'), 'description' => 'The guide, start to finish.']]" />
</article>
</x-layouts.public>
