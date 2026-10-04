<x-layouts.public :seo="$seo" :hide-floating-cta="true">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Apply Online · eligibility', 'title' => 'Check your eligibility for UK Medicine', 'lede' => 'Seven questions, no account needed. You will see which routes appear open on published requirements and what to read next. This is a route map from published rules, not an admissions decision; a qualified reviewer confirms your position if you proceed.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-7">
            @if($result)
                <section class="card-raised border-l-4 border-l-navy-700" aria-labelledby="res-h">
                    <p class="eyebrow">Your route map · {{ $result['intake_year'] }} entry</p>
                    <h2 id="res-h" class="text-xl font-serif font-semibold mt-1">{{ $result['summary'] }}</h2>
                    <ul class="mt-5 space-y-3">
                        @foreach($result['routes'] as [$label, $state, $why])
                            <li class="flex gap-3"><span class="chip {{ ['open' => 'chip-verified', 'possible' => 'chip-info', 'conditional' => 'chip-review', 'closed' => 'chip-danger'][$state] }} shrink-0 mt-0.5">{{ ucfirst($state) }}</span><div><p class="font-semibold">{{ $label }}</p><p class="text-[0.9375rem] text-ink-700">{{ $why }}</p></div></li>
                        @endforeach
                    </ul>
                    <p class="mt-5 text-[0.875rem] text-ink-500">Based on published requirements we have recorded; schools differ and change their rules. "Open" means no published rule excludes you, not that you will be admitted.</p>
                    <div class="mt-6 flex flex-col sm:flex-row gap-3"><a href="{{ route('register') }}" class="btn btn-primary btn-lg">Create your account</a><a href="{{ route('apply.services') }}" class="btn btn-secondary btn-lg">Suggested: {{ $result['tier'] }} — see services</a></div>
                    <p class="mt-4 text-[0.9375rem]">Read next: @foreach($result['reads'] as $r)<a href="{{ route($r) }}" class="mr-3">{{ Str::of($r)->replace('.', ' › ')->replace(['medicine', 'requirements', 'admissions', 'schools', 'index', 'nigeria', 'waec', 'foundation', 'alevels', 'gem', 'ucat'], ['Medicine', 'Requirements', 'Admissions', 'Schools', 'hub', 'from Nigeria', 'WAEC', 'foundation routes', 'A-levels', 'graduate entry', 'UCAT']) }}</a>@endforeach</p>
                </section>
                <p class="mt-6"><a href="{{ route('apply.eligibility') }}" class="btn btn-tertiary">Start again</a></p>
            @else
                <form method="post" action="{{ route('apply.eligibility.submit') }}" class="card-raised space-y-6" novalidate>
                    @csrf
                    @if($errors->any())<x-alert type="danger" title="Please check the following"><ul class="list-disc pl-5 text-[0.9375rem]">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-alert>@endif
                    <fieldset class="field"><legend class="label">1. Your highest qualification so far</legend>
                        @foreach(['waec_only' => 'WAEC/NECO only (no A-levels, IB or degree yet)', 'alevels_ib' => 'A-levels or IB (completed or in progress)', 'foundation' => 'An international foundation programme (in progress or completed)', 'nigerian_degree' => 'A Nigerian (or other) university degree', 'other' => 'Something else'] as $v => $l)
                            <label class="flex items-center gap-3 py-1.5 text-[0.9375rem]"><input type="radio" name="qualification" value="{{ $v }}" class="w-4 h-4" @checked(old('qualification')===$v)> {{ $l }}</label>
                        @endforeach</fieldset>
                    <fieldset class="field"><legend class="label">2. Chemistry and Biology</legend>
                        @foreach(['yes' => 'I have (or am taking) both Chemistry and Biology at A-level/IB Higher Level', 'partial' => 'One of them, or only at WAEC/NECO level', 'no' => 'Neither'] as $v => $l)<label class="flex items-center gap-3 py-1.5 text-[0.9375rem]"><input type="radio" name="sciences" value="{{ $v }}" class="w-4 h-4" @checked(old('sciences')===$v)> {{ $l }}</label>@endforeach</fieldset>
                    <fieldset class="field"><legend class="label">3. English language evidence</legend>
                        @foreach(['ielts' => 'IELTS/TOEFL/PTE result at 7.0 or above (or booked)', 'waec_english' => 'WAEC/NECO English only', 'none' => 'None yet'] as $v => $l)<label class="flex items-center gap-3 py-1.5 text-[0.9375rem]"><input type="radio" name="english" value="{{ $v }}" class="w-4 h-4" @checked(old('english')===$v)> {{ $l }}</label>@endforeach</fieldset>
                    <fieldset class="field"><legend class="label">4. UCAT</legend>
                        @foreach(['taken' => 'Taken in the current cycle', 'planned' => 'Planned', 'none' => 'Not planned / unsure'] as $v => $l)<label class="flex items-center gap-3 py-1.5 text-[0.9375rem]"><input type="radio" name="ucat" value="{{ $v }}" class="w-4 h-4" @checked(old('ucat')===$v)> {{ $l }}</label>@endforeach</fieldset>
                    <div class="field max-w-xs"><label for="intake_year" class="label">5. Intended entry year</label><select id="intake_year" name="intake_year" class="input">@foreach(range(now()->year + 1, now()->year + 3) as $y)<option value="{{ $y }}" @selected((int)old('intake_year', 2028)===$y)>September {{ $y }}</option>@endforeach</select></div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="field"><label for="name" class="label">6. Your name</label><input id="name" name="name" value="{{ old('name') }}" class="input" autocomplete="name"></div>
                        <div class="field"><label for="email" class="label">7. Email for your route map</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="input" autocomplete="email"></div>
                        <div class="field"><label for="whatsapp" class="label">WhatsApp (optional)</label><input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" class="input" placeholder="+234"></div>
                    </div>
                    <label class="flex items-start gap-3 text-[0.9375rem]"><input type="checkbox" name="consent" value="1" class="mt-1 w-4 h-4" @checked(old('consent'))><span>I agree that Study Medicine UK Nigeria may store these answers and contact me about my route. <a href="{{ route('legal.privacy') }}">Privacy notice</a>.</span></label>
                    <button type="submit" class="btn btn-primary btn-lg">Show my route map</button>
                </form>
            @endif
        </div>
        <aside class="lg:col-span-5 space-y-5">
            <div class="card"><p class="eyebrow mb-3">How this works</p><p class="text-[0.9375rem] text-ink-700">Your answers are matched against the requirements UK medical schools publish, which we record school by school with sources. The result is a route map: which categories of entry appear open, possible, conditional or closed for your intended year. It is deliberately cautious and never says "you are eligible".</p></div>
            <div class="card"><p class="eyebrow mb-3">What you can read meanwhile</p><ul class="space-y-2 text-[0.9375rem]"><li><a href="{{ route('requirements.waec') }}">WAEC and UK Medicine</a></li><li><a href="{{ route('admissions.ucat') }}">UCAT from Nigeria</a></li><li><a href="{{ route('fees.index') }}">Fee guide</a></li></ul></div>
        </aside>
    </div>
    <x-route-map current="eligibility" />
</article>
</x-layouts.public>
