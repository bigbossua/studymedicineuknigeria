<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Working in the UK', 'title' => 'Working in the UK during and after a medical degree', 'lede' => 'What the rules allow while you study, the steps from graduation to your first hospital post, where the Graduate visa fits, and which parts of this are still proposals. Every dated fact below carries its verification state and official source.', 'seo' => $seo])

    @if($gated)
    <div class="alert alert-warning mt-6 max-w-3xl" role="note"><p class="font-semibold">Verification in progress</p><p class="text-[0.9375rem] mt-1">Figures on this page are shown only once confirmed on GOV.UK, the GMC or the UK Foundation Programme Office. Rows marked “being verified” are checked against the official page before they appear.</p></div>
    @endif

    <section class="mt-10 grid gap-4 md:grid-cols-3 max-w-5xl" aria-label="Summary">
        <div class="card"><p class="eyebrow mb-2 text-success-600">What is certain</p><p class="text-[0.9375rem] text-ink-700">A UK medical degree leads to the same GMC registration route for every graduate, whatever their nationality. Student visa work rules are published by GOV.UK.</p></div>
        <div class="card"><p class="eyebrow mb-2">What is competitive</p><p class="text-[0.9375rem] text-ink-700">Foundation Programme places and later specialty training are allocated by national competition. Nothing here is guaranteed to anyone.</p></div>
        <div class="card"><p class="eyebrow mb-2 text-warning-700">What is changing</p><p class="text-[0.9375rem] text-ink-700">Graduate visa length, visa maintenance figures and the priority given to UK medical graduates in training allocation have all changed recently; each is shown below with its source and verification status. A 2027 entrant graduates into rules written between now and 2033.</p></div>
    </section>

    <div class="mt-12 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-14">
            <section id="during"><h2>During the degree: Student visa work rules</h2>
                <p class="text-ink-700 mt-2">The legal maximum, not a planning assumption. Medical courses have long terms and clinical placements, and medical-school term dates often differ from the rest of the university; the university’s immigration team defines what counts as term time for you.</p>
                <dl class="card mt-4">
                    <x-fact-row :fact="$visa?->fact('work_term_time')" label="Paid work allowed" />
                    <x-fact-row :fact="$visa?->fact('dependants')" label="Dependants" />
                    <x-fact-row :fact="$visa?->fact('maintenance_outside_london_monthly_gbp')" label="Maintenance funds to show (outside London, per month)"  />
                    <x-fact-row :fact="$visa?->fact('maintenance_london_monthly_gbp')" label="Maintenance funds to show (London, per month)"  />
                    <x-fact-row :fact="$visa?->fact('ihs_per_year_gbp')" label="Immigration Health Surcharge (per year of visa)"  />
                </dl>
                <div class="prose-site mt-5">
                    <p>Not allowed on a Student visa: self-employment or freelance work, work as a professional sportsperson or entertainer, and filling a permanent full-time vacancy. Paid work and volunteering hours count together towards the weekly cap. Healthcare-assistant bank shifts in vacations are employment, not self-employment, but confirm with your university’s immigration team before you start.</p>
                </div>
            </section>

            <section id="after"><h2>After you graduate: from final year to Foundation Year 1</h2>
                <p class="text-ink-700 mt-2">The order of steps for a graduate of a UK medical school who needs a visa. Nationality is not a criterion at any step; none of the steps is automatic.</p>
                <ol class="mt-4 space-y-3 list-decimal pl-5 text-[0.9375rem] text-ink-700 max-w-prose">
                    <li><strong>Pass the Medical Licensing Assessment inside your degree.</strong> The Applied Knowledge Test and the Clinical and Professional Skills Assessment are part of the course for UK students from 2024/25.</li>
                    <li><strong>Apply to the UK Foundation Programme in the autumn of final year</strong> through your medical school, on the same basis as home graduates. You indicate on the application that you will need sponsorship.</li>
                    <li><strong>Allocation</strong> to a foundation school follows a national process. After allocation, those who need a work visa are contacted about a Certificate of Sponsorship.</li>
                    <li><strong>Apply to the GMC for provisional registration with a licence to practise</strong> after graduating, with a fitness-to-practise declaration.</li>
                    <li><strong>Switch from the Student visa to the Health and Care Worker visa</strong> with the employing trust as sponsor, before Foundation Year 1 starts in early August.</li>
                    <li><strong>Full GMC registration</strong> follows satisfactory completion of Foundation Year 1.</li>
                </ol>
                <dl class="card mt-6">
                    <x-fact-row :fact="$gmc?->fact('mla')" label="Medical Licensing Assessment" />
                    <x-fact-row :fact="$gmc?->fact('provisional_registration')" label="Provisional registration" />
                    <x-fact-row :fact="$gmc?->fact('foundation_eligibility')" label="Foundation Programme eligibility" />
                    <x-fact-row :fact="$gmc?->fact('f1_visa_route')" label="Visa for Foundation Year 1" />
                </dl>
            </section>

            <section id="graduate-visa"><h2>The Graduate visa: a fallback, not the main route</h2>
                <p class="text-ink-700 mt-2">Most UK medical graduates who need sponsorship move straight from the Student visa to the Health and Care Worker visa for Foundation training. The Graduate visa matters if no post is allocated or a gap must be bridged. It allows work at any skill level and does not count towards settlement.</p>
                <dl class="card mt-4">
                    <x-fact-row :fact="$grad?->fact('length')" label="Length of the Graduate visa" />
                    <x-fact-row :fact="$grad?->fact('fee_gbp')" label="Application fee"  />
                    <x-fact-row :fact="$visa?->fact('ihs_per_year_gbp')" label="Immigration Health Surcharge (per year)"  />
                </dl>
                <p class="text-[0.9375rem] text-ink-700 mt-4">The length is set by the date of the Graduate visa application, not the course start date. A 2027 entrant finishing in 2032 or 2033 applies under whatever rule is then in force.</p>
            </section>

            <section id="prioritisation"><h2>Prioritising UK graduates for training</h2>
                <dl class="card mt-4">
                    <x-fact-row :fact="$gmc?->fact('prioritisation_act')" label="Who counts as a UK medical graduate" />
                </dl>
                <div class="prose-site mt-5">
                    @if($gmc?->fact('prioritisation_act')?->isPublishable())
                        <p>The definition above decides who is in the priority group for medical training allocation. Its wording turns on where the training took place, not on nationality or fee status, so it reads on international students who complete a UK medical degree in the UK in the same way as on home students; a degree awarded by a UK university but taught mostly outside the UK is treated differently. What the priority means in practice (which allocation rounds, at which stage, for which posts) is set in guidance that changes from year to year: read the NHS England applicant information and the UK Foundation Programme Office pages linked below before relying on it.</p>
                    @else
                        <p>Government policy is to give UK medical graduates priority in training allocation. Who counts as a UK medical graduate, and what that means for an international student who qualified in the UK, is set in legislation and NHS guidance that we are re-checking; until the definition is verified here, read the NHS England applicant information and the UK Foundation Programme Office pages linked below, and treat priority as policy direction, not a promise.</p>
                    @endif
                </div>
            </section>

            <section id="check" class="prose-site"><h2>Where to check for yourself</h2>
                <p>Every figure on this page will change at least once before a 2027 entrant graduates. Learn where the rules live rather than memorising numbers:</p>
                <ul>
                    <li>GOV.UK: <a href="https://www.gov.uk/student-visa" rel="noopener" target="_blank">Student visa</a>, <a href="https://www.gov.uk/graduate-visa" rel="noopener" target="_blank">Graduate visa</a>, <a href="https://www.gov.uk/health-care-worker-visa" rel="noopener" target="_blank">Health and Care Worker visa</a>.</li>
                    <li>General Medical Council: <a href="https://www.gmc-uk.org/education/medical-licensing-assessment" rel="noopener" target="_blank">Medical Licensing Assessment</a> and <a href="https://www.gmc-uk.org/registration-and-licensing/join-the-register/provisional-registration" rel="noopener" target="_blank">provisional registration</a>.</li>
                    <li>UK Foundation Programme Office: <a href="https://foundationprogramme.nhs.uk/" rel="noopener" target="_blank">eligibility and right-to-work guidance</a>.</li>
                    <li>NHS England: <a href="https://www.england.nhs.uk/long-read/medical-training-prioritisation-bill-information-for-applicants-to-medical-training/" rel="noopener" target="_blank">medical training prioritisation, information for applicants</a>; the legislation itself: <a href="https://www.legislation.gov.uk/ukpga/2026/7" rel="noopener" target="_blank">Medical Training (Prioritisation) Act 2026</a>.</li>
                    <li>British Medical Association: <a href="https://www.bma.org.uk/advice-and-support/international-doctors/training-in-the-uk/visa-guide-for-international-doctors-and-students-training-in-the-uk" rel="noopener" target="_blank">visa guide for international students and doctors</a>.</li>
                </ul>
            </section>
        </div>
        <aside class="lg:col-span-4 space-y-6">
            <div class="card"><p class="eyebrow mb-2">Plain statement</p><p class="text-[0.9375rem] text-ink-700">A place at a UK medical school does not carry a right to practise, a guaranteed job or a guaranteed visa. We do not advise qualified doctors on PLAB or housemanship routes; the BMA and GMC publish that guidance.</p></div>
            <div class="card"><p class="eyebrow mb-2">Costs that depend on these rules</p><p class="text-[0.9375rem] text-ink-700">Maintenance funds, the health surcharge and visa fees are added up on the <a href="{{ route('fees.total') }}">total cost page</a>.</p></div>
            <div class="card"><p class="eyebrow mb-2">Last reviewed</p><p class="text-[0.9375rem] text-ink-700">Research record 10, reviewed {{ \Carbon\Carbon::parse($seo->lastReviewed)->format('j F Y') }}. Rules on this page are re-checked at every GOV.UK Statement of Changes and before each UCAS cycle.</p></div>
        </aside>
    </div>

    <x-related :items="[['label' => 'Total cost of studying Medicine in the UK', 'url' => route('fees.total'), 'description' => 'Tuition, visa, surcharge and living costs with sources.'], ['label' => 'Study Medicine in the UK from Nigeria', 'url' => route('medicine.nigeria'), 'description' => 'The honest guide for 2027 and 2028 entry.'], ['label' => 'Questions applicants ask', 'url' => route('faq.index'), 'description' => 'Sourced answers, including working after graduation.']]" />
    <x-cta-band class="mt-12" title="Check which routes are open to you" :href="route('apply.eligibility')" label="Check your eligibility" />
    <x-route-map current="career" />
</article>
</x-layouts.public>
