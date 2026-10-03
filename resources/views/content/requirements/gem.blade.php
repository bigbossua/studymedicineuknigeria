<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Requirements · graduate entry', 'title' => 'Graduate Entry Medicine in the UK with a Nigerian degree', 'lede' => 'Four-year graduate-entry programmes (A101/A102) exist at many UK schools, but most are home-only and only some accept international applicants. Many Nigerian graduates instead apply to the standard five-year course, where graduates are assessed on degree class plus the UCAT. Here is what schools publish.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12">
            <section><h2>Graduate entry: what schools publish for international applicants ({{ $gem->count() }})</h2>@include('content._statement-list', ['items' => $gem, 'empty' => 'No graduate-entry statements for international applicants have been recorded yet.'])</section>
            @if($gemCourses->isNotEmpty())<section><h2>Graduate-entry courses in the directory</h2><ul class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($gemCourses as $c)<li class="card"><a href="{{ route('schools.show', $c->university) }}" class="font-semibold">{{ $c->university->name }}</a><p class="text-[0.9375rem] text-ink-500">{{ $c->title }}{{ $c->ucas_code ? ' · '.$c->ucas_code : '' }}</p></li>@endforeach</ul></section>@endif
            <section class="prose-site">
                <h2>Three things to check about your Nigerian degree</h2>
                <ul><li><strong>Comparability.</strong> UK universities use UK ENIC to compare overseas degrees; a Nigerian bachelor's with Second Class Upper is commonly compared to a UK 2:1, but each school decides. Ask the school how it treats your CGPA scale.</li><li><strong>Subject.</strong> Some graduate-entry programmes require a science or health-related first degree; others accept any discipline.</li><li><strong>Tests.</strong> GAMSAT or UCAT, depending on the programme; both have fixed windows. <a href="{{ route('admissions.ucat') }}">UCAT page</a>.</li></ul>
            </section>
            <x-cta-band title="Hold a Nigerian degree?" :href="route('apply.eligibility')" label="Check your eligibility">We will show which graduate and standard-entry routes appear open on published requirements.</x-cta-band>
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('schools.index') }}?test=GAMSAT">Schools using GAMSAT</a></li><li><a href="{{ route('fees.index') }}">Fee guide</a></li><li><a href="{{ route('admissions.howto') }}">How to apply</a></li></ul></div></aside>
    </div>
</article>
</x-layouts.public>
