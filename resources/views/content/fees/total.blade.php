<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Fees · planning', 'title' => 'Total cost of studying Medicine in the UK from Nigeria', 'lede' => 'Tuition is only part of the bill. This page adds the visa, the Immigration Health Surcharge, the maintenance funds you must show and living costs to the published tuition rates, and shows the arithmetic. Every input is a sourced fact; where the official figure has not yet been confirmed, the line says so instead of guessing.', 'seo' => $seo])
    @if($gated)<x-alert type="info" class="mt-6 max-w-3xl" title="Page in verification">Some inputs below have not yet been confirmed on their official pages. This page is not indexed until they are.</x-alert>@endif
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-10">
            <section><h2>Inputs</h2>
                <dl class="card mt-4">
                    <div class="py-3 border-b border-ink-100 grid sm:grid-cols-12 gap-2"><dt class="sm:col-span-4 text-ink-500 text-[0.9375rem]">Published per-year tuition rates (our summary)</dt><dd class="sm:col-span-8 font-medium">@if($fees->count())£{{ number_format($fees->min('value_number')) }} – £{{ number_format($fees->max('value_number')) }} per year, as each school publishes it for the fee year shown ({{ $schoolCount }} {{ Str::plural('school', $schoolCount) }}; <a href="{{ route('fees.index') }}">fee guide</a>). Clinical and later years can be charged at a higher rate.@else Being verified @endif</dd></div>
                    <x-fact-row :fact="$visa?->fact('application_fee_gbp')" label="Student visa application fee" />
                    <x-fact-row :fact="$visa?->fact('ihs_per_year_gbp')" label="Immigration Health Surcharge" suffix=" per year" />
                    <x-fact-row :fact="$visa?->fact('maintenance_outside_london_monthly_gbp')" label="Maintenance funds (outside London)" suffix=" per month, for up to 9 months" />
                    <x-fact-row :fact="$visa?->fact('maintenance_london_monthly_gbp')" label="Maintenance funds (London)" suffix=" per month, for up to 9 months" />
                    <x-fact-row :fact="$ucat?->fact('fee_international_gbp')" label="UCAT (outside UK)" />
                    <x-fact-row :fact="$ucas?->fact('application_fee_gbp')" label="UCAS application fee" />
                    <x-fact-row :fact="$visa?->fact('tb_test')" label="TB test" />
                    <x-fact-row :fact="$costs?->fact('living_costs_note')" label="Living costs" />
                    <x-fact-row :fact="$costs?->fact('funding_note')" label="Funding" />
                </dl></section>
            <section class="prose-site"><h2>Illustration: a floor for five years outside London</h2>
                @php $ihs = $visa?->fact('ihs_per_year_gbp'); $maint = $visa?->fact('maintenance_outside_london_monthly_gbp'); $lo = $lowest?->value_number; @endphp
                @if($lowest && $ihs?->isPublishable() && $maint?->isPublishable())
                <p>This is a floor, not an estimate of what you will pay. It takes the lowest published per-year international rate for a standard-entry course in our records, <strong>£{{ number_format($lo) }}</strong> ({{ $lowest->subject->university->name }}{{ $lowest->academic_year ? ', fee year '.$lowest->academic_year : '' }}; graduate-entry rates are left out), and applies it to all five years. That rate is published for the fee year shown: clinical and later years can be charged at a higher rate, fees can rise each year, and some courses last six years, so the real total is higher wherever any of these applies.</p>
                <ul>
                    <li>Tuition at the lowest published per-year rate: 5 × £{{ number_format($lo) }} = <strong>£{{ number_format(5*$lo) }}</strong></li>
                    <li>Health surcharge: 5 × £{{ number_format($ihs->value_number) }} = <strong>£{{ number_format(5*$ihs->value_number) }}</strong></li>
                    <li>Living costs at the UKVI maintenance rate as a floor: 5 × 9 × £{{ number_format($maint->value_number) }} = <strong>£{{ number_format(45*$maint->value_number) }}</strong> (real living costs over a 12-month year are higher)</li>
                </ul>
                <p>Floor before the visa fee, tests, travel, any higher clinical-year rate and annual increases: <strong>£{{ number_format(5*$lo + 5*$ihs->value_number + 45*$maint->value_number) }}</strong>. For any other school, repeat the sum with its own published rate for each year from the <a href="{{ route('fees.index') }}">fee guide</a>.</p>
                @else<p>The illustration appears once a tuition rate, the health surcharge and the maintenance figures are verified.</p>@endif
                <p class="text-[0.9375rem] text-ink-500">This is our arithmetic from official inputs, not an official figure. Fees can rise each year and sterling–naira movements matter; budget a margin.</p>
            </section>
            <x-cta-band title="Know what the costs are. Ready to check your application?" :href="route('apply.index')" label="Apply Online" />
        </div>
        <aside class="lg:col-span-4"><div class="card"><p class="eyebrow mb-3">Related</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('fees.index') }}">Fee guide by school</a></li><li><a href="{{ route('admissions.ucas2027') }}">Timeline to visa</a></li></ul></div></aside>
    </div>
    <x-route-map current="fees" />
</article>
</x-layouts.public>
