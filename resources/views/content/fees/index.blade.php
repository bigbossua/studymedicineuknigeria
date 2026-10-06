<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Fees', 'title' => 'UK medical school fees for international students', 'lede' => 'International tuition for Medicine at UK medical schools, one row per school, with the fee year, whether clinical years cost more, and the official source. The lowest and highest published rates are summarised separately from the table. Our service fee, if you use us, is separate from all of this.', 'seo' => $seo])
    @if($count)
    <section class="mt-8 card max-w-3xl border-l-4 border-l-navy-700" aria-label="Published per-year rates">
        <h2 class="eyebrow">Published per-year international rates (our summary of the figures below)</h2>
        <p class="mt-2 text-2xl font-serif font-semibold">£{{ number_format($min) }} – £{{ number_format($max) }} <span class="text-base font-sans font-normal text-ink-500">per year: the rate each school publishes for the fee year shown, across {{ $count }} {{ Str::plural('school', $count) }}</span></p>
        <p class="mt-2 text-[0.875rem] text-ink-500">At some schools the published rate covers the early years only: clinical and later years can be charged at a higher rate, and some courses last four or six years, so a full course can cost more than the rate shown times five. Some schools include or add an NHS clinical placement levy, and some reserve the right to annual increases. The "Clinical years differ?" column and each school's page say where this is published.</p>
    </section>
    @endif
    <section class="mt-8">
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h2>Published international fees by medical school</h2>
            <nav class="text-[0.875rem]" aria-label="Sort order">Sort: <a href="{{ route('fees.index') }}" class="chip {{ $sort === 'name' ? 'chip-info' : 'chip-pending' }} no-underline" @if($sort === 'name') aria-current="true" @endif>by university</a> <a href="{{ route('fees.index', ['sort' => 'fee']) }}" class="chip {{ $sort === 'fee' ? 'chip-info' : 'chip-pending' }} no-underline" @if($sort === 'fee') aria-current="true" @endif>lowest published fee first</a></nav>
        </div>
        @if($sort === 'fee')<p class="mt-2 text-[0.9375rem] text-ink-700">"Cheapest" here means the lowest fee a university has published for the year shown; clinical years, annual increases and living costs can change the order of the real total. Schools with no publishable fee appear last.</p>@endif
        <table class="table-stack mt-4">
            <thead><tr><th>University</th><th>Course</th><th>International fee / year</th><th>Fee year</th><th>Clinical years differ?</th><th>Status · source</th></tr></thead>
            <tbody>
            @foreach($rows as $f)
                @php $c = $f->subject; $u = $c->university; $cl = $c->facts->where('key','clinical_years_fee_differs')->first(); @endphp
                <tr>
                    <td data-label="University"><a href="{{ route('schools.show', $u) }}">{{ $u->name }}</a>@if($u->international_policy==='home_only')<span class="chip chip-danger ml-1">home only</span>@endif</td>
                    <td data-label="Course">{{ $c->title }}{{ $c->shortUcasCode() ? ' · '.$c->shortUcasCode() : '' }}</td>
                    <td data-label="Fee">@if($f->isPublishable() && $f->value_number)<span class="font-semibold">{{ $f->displayValue() }}</span>@elseif($f->verification_status==='NOT_PUBLISHED')<span class="text-ink-500">Not published / not open</span>@else<span class="text-ink-500">Being verified</span>@endif</td>
                    <td data-label="Fee year">{{ $f->academic_year ?? '—' }}</td>
                    <td data-label="Clinical years">{{ $cl && $cl->isPublishable() && $cl->value_bool !== null ? ($cl->value_bool ? 'Yes' : 'No') : '—' }}</td>
                    <td data-label="Status"><x-verified-badge :status="$f->verification_status" :date="$f->verified_at?->format('j M Y')" /> @if($f->source_url && ($f->isPublishable() || $f->verification_status === 'NOT_PUBLISHED'))<a href="{{ $f->source_url }}" rel="noopener nofollow" target="_blank" class="text-[0.8125rem]">source ↗</a>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
    <section class="mt-10 grid lg:grid-cols-12 gap-8">
        <div class="lg:col-span-8 prose-site">
            <h2>What is not in the table</h2>
            <p>Tuition is the largest cost but not the only one. You will also pay the Student visa fee, the Immigration Health Surcharge for every year of the course, show maintenance funds, and meet living costs. Our <a href="{{ route('fees.total') }}">total cost page</a> adds these together, with the arithmetic shown. Some university-wide international scholarships exclude Medicine (examples with sources on the total cost page); check each school's funding page.</p>
            <dl class="card not-prose mt-4">
                <x-fact-row :fact="$visa?->fact('ihs_per_year_gbp')" label="Immigration Health Surcharge (per year)" suffix=" per year" />
                <x-fact-row :fact="$visa?->fact('maintenance_outside_london_monthly_gbp')" label="Maintenance funds (outside London, per month)" suffix=" per month" />
            </dl>
        </div>
        <aside class="lg:col-span-4"><x-cta-band title="Know what the costs are. Ready to check your application?" :href="route('apply.index')" label="Apply Online" class="flex-col items-start" /></aside>
    </section>
    <x-related :items="[['label' => 'Study Medicine in the UK from Nigeria', 'url' => route('medicine.nigeria'), 'description' => 'The full guide: routes, schools, calendar and cost in one place'], ['label' => 'Total cost of studying Medicine in the UK', 'url' => route('fees.total')], ['label' => 'Medical school directory', 'url' => route('schools.index')], ['label' => 'Requirements hub', 'url' => route('requirements.index')], ['label' => 'Our application support services', 'url' => route('apply.services')]]" />
    <x-route-map current="fees" />
</article>
</x-layouts.public>
