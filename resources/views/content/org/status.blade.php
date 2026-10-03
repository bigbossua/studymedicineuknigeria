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
        <p class="text-[0.8125rem] text-ink-500">This page was last updated on 3 October 2026.</p>
    </div>
    <x-related :items="[['label' => 'About us', 'url' => route('about'), 'description' => 'Who we are and how we work.'], ['label' => 'Application service terms', 'url' => route('legal.application-terms'), 'description' => 'What the paid service does and does not include.'], ['label' => 'Contact', 'url' => route('contact'), 'description' => 'How to reach us and what to include.']]" />
</article>
</x-layouts.public>
