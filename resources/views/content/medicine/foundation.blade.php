<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Medicine · routes', 'title' => 'Foundation and gateway routes to Medicine for international students', 'lede' => 'A foundation year can be the route from WASSCE or NECO to a UK medical degree, but only when the programme publishes Medicine as a destination and is open to international students. Many "gateway" years are home-only widening-participation schemes. Here is what we found, school by school.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-10">
            <section class="prose-site">
                <h2>Two very different things called "foundation"</h2>
                <p><strong>Gateway or foundation-year medicine courses (A104 and similar)</strong> are six-year programmes run by medical schools themselves, mostly for UK students from under-represented backgrounds; they are usually home-only. <strong>International foundation programmes</strong> are one-year pre-university courses run by universities or pathway providers; a few publish progression to Medicine, always competitive and conditional on grades, the UCAT and interview. Read the published progression terms, not the brochure.</p>
            </section>
            <section>
                <h2>What universities publish about foundation routes</h2>
                @include('content._statement-list', ['items' => $routes, 'empty' => 'No foundation-route statements have been recorded yet.'])
            </section>
            @if($foundationCourses->isNotEmpty())
            <section><h2>Foundation-entry medicine courses in the directory</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($foundationCourses as $c)<li class="card"><a href="{{ route('schools.show', $c->university) }}" class="font-semibold">{{ $c->university->name }}</a><p class="text-[0.9375rem] text-ink-500">{{ $c->title }}</p></li>@endforeach</ul></section>
            @endif
            <section class="prose-site">
                <h2>Questions to ask any provider before you pay</h2>
                <ul><li>Which medical schools, by name, have you progressed students to in the last two years, and how many?</li><li>What exact grades, UCAT score and interview outcome does progression require, and is it guaranteed or competitive?</li><li>Is the foundation year itself open to international students on a Student visa, and does it count towards the UCAS application timeline (UCAT in the summer of the foundation year)?</li><li>What happens, and what do I pay, if I do not progress to Medicine?</li></ul>
            </section>
            <x-cta-band title="Not sure a foundation route is right for you?" :href="route('apply.eligibility')" label="Check your eligibility">Tell us your qualifications and we will show which routes appear open on published requirements.</x-cta-band>
        </div>
        <aside class="lg:col-span-4 space-y-5">
            <div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('requirements.waec') }}">WAEC and UK Medicine</a></li><li><a href="{{ route('requirements.alevels') }}">A-levels route</a></li><li><a href="{{ route('schools.index') }}">Directory</a></li></ul></div>
        </aside>
    </div>
</article>
</x-layouts.public>
