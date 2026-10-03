<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Medicine · routes', 'title' => 'Foundation and gateway routes to Medicine for international students', 'lede' => 'A foundation year can be the route from WASSCE or NECO to a UK medical degree, but only when the programme publishes Medicine as a destination and is open to international students. Many "gateway" years are home-only widening-participation schemes, and many international foundation programmes lead to science degrees but not to Medicine. Here is what we found, school by school, and the questions that separate a real route from an expensive detour.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section class="prose-site">
                <h2>1. Two very different things called "foundation"</h2>
                <p><strong>Gateway or foundation-year medicine courses (A104 and similar)</strong> are six-year programmes run by medical schools themselves, mostly for UK students from under-represented backgrounds; they are usually home-only, and where they are not, the school says so explicitly. <strong>International foundation programmes</strong> are one-year pre-university courses run by universities or pathway providers; a few publish progression to Medicine, always competitive and conditional on grades, the UCAT and interview. A third pattern exists: a <strong>medicine degree with an integrated foundation entry year</strong> designed for international students, which is a six-year route into one named school. Read the published progression statement for the programme you are considering, not the brochure.</p>
            </section>

            <section>
                <h2>2. What universities publish about foundation routes ({{ $published->count() }})</h2>
                <p class="mt-2 text-ink-700">Statements located on official pages. "Progression to Medicine not confirmed" means the university names a foundation programme for Nigerian applicants in general without saying it leads to Medicine; treat that as no until the school confirms in writing.</p>
                @include('content._statement-list', ['items' => $published, 'empty' => 'No foundation-route statements have been recorded yet.'])
            </section>

            @if($foundationCourses->isNotEmpty())
            <section><h2>3. Foundation-entry medicine courses in the directory</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($foundationCourses as $c)<li class="card"><a href="{{ route('schools.show', $c->university) }}" class="font-semibold">{{ $c->university->name }}</a><p class="text-[0.9375rem] text-ink-500">{{ $c->title }}</p></li>@endforeach</ul></section>
            @endif

            <section class="prose-site">
                <h2>{{ $foundationCourses->isNotEmpty() ? '4' : '3' }}. How a foundation route changes your calendar and cost</h2>
                <ul>
                    <li><strong>One more year</strong> of tuition and living costs before the medical degree begins; the foundation year is charged at the provider's fee, the degree at the medical school's international fee (<a href="{{ route('fees.index') }}">fee guide</a>).</li>
                    <li><strong>The UCAT still applies</strong> where the destination school uses it, and it must be sat in the summer of the foundation year, before the UCAS deadline, often while you are still settling in. Programmes that publish a guaranteed or competitive progression route usually also publish the UCAT and interview conditions attached.</li>
                    <li><strong>The visa is for the foundation first.</strong> A Student visa is issued for the foundation programme; progression means a new CAS and visa application for the degree. Below-degree-level courses may require a Secure English Language Test (<a href="{{ route('requirements.english') }}">English requirements</a>).</li>
                    <li><strong>Age and timing.</strong> Some schools publish a minimum age at the start of clinical placements; a student who finishes WASSCE young should check this before choosing a one-year route.</li>
                </ul>
            </section>

            <section class="prose-site">
                <h2>{{ $foundationCourses->isNotEmpty() ? '5' : '4' }}. Questions to ask any provider before you pay</h2>
                <ul><li>Which medical schools, by name, have you progressed students to in the last two years, and how many?</li><li>What exact grades, UCAT score and interview outcome does progression require, and is it guaranteed or competitive?</li><li>Is the foundation year itself open to international students on a Student visa, and does it count towards the UCAS application timeline (UCAT in the summer of the foundation year)?</li><li>What happens, and what do I pay, if I do not progress to Medicine: which other degrees are open, and is any fee refunded?</li><li>Is the progression statement published on the university's own website, and will you put the answer in writing?</li></ul>
                <p>We hold no arrangement with any foundation provider and receive nothing for a referral; the statements above are the universities' own, and the gaps are marked as gaps.</p>
            </section>

            @if($notPublished->isNotEmpty())
            <section>
                <h2>{{ $foundationCourses->isNotEmpty() ? '6' : '5' }}. Schools with no published foundation route for international applicants ({{ $notPublished->count() }})</h2>
                <p class="mt-2 text-ink-700">For these medical schools our research found no statement naming a foundation programme that leads to Medicine for international applicants. That is a gap in what is published, not a statement that no route exists; the A-level or IB route is the published one.</p>
                <ul class="mt-4 flex flex-wrap gap-2">@foreach($notPublished as $f)<li><a href="{{ route('schools.show', $f->subject) }}" class="chip chip-notpublished no-underline hover:bg-navy-100">{{ $f->subject->name }}</a></li>@endforeach</ul>
            </section>
            @endif

            <section>
                <h2>Questions about foundation routes</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach($faqs as $f)<details class="py-3" id="q{{ $f['id'] }}"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><div class="mt-2 text-ink-700 prose-site">{!! $f['a'] !!}</div></details>@endforeach
                </div>
            </section>

            <x-cta-band title="Not sure a foundation route is right for you?" :href="route('apply.eligibility')" label="Check your eligibility">Tell us your qualifications and we will show which routes appear open on published requirements.</x-cta-band>
        </div>
        <aside class="lg:col-span-4 space-y-5">
            <div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('medicine.nigeria') }}">Study Medicine in the UK from Nigeria: the guide</a></li><li><a href="{{ route('requirements.waec') }}">WAEC and UK Medicine</a></li><li><a href="{{ route('requirements.neco') }}">NECO and UK Medicine</a></li><li><a href="{{ route('requirements.alevels') }}">A-levels route</a></li><li><a href="{{ route('admissions.ucat') }}">UCAT from Nigeria</a></li><li><a href="{{ route('schools.index') }}?international=accepts">Directory</a></li></ul></div>
        </aside>
    </div>
</article>
</x-layouts.public>
