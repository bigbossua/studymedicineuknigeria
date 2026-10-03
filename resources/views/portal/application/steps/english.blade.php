<div class="grid sm:grid-cols-2 gap-5">
    <div class="field sm:col-span-2"><label for="route" class="label">English language evidence <span class="text-accent-600">*</span></label>
        <select id="route" name="route" class="input">@foreach(['IELTS'=>'IELTS (Academic / for UKVI)','TOEFL'=>'TOEFL iBT','PTE'=>'PTE Academic','WAEC_ENGLISH'=>'WAEC/NECO English only (some universities accept this; many medical schools do not)','UNIVERSITY_ASSESSMENT'=>'I expect the university to assess my English','NONE_YET'=>'Nothing yet — I plan to take a test'] as $v=>$l)<option value="{{ $v }}" @selected(($d['route'] ?? 'NONE_YET')===$v)>{{ $l }}</option>@endforeach</select>
        <p class="hint">Medicine courses typically publish IELTS 7.0 to 7.5 overall. We show each school's published figure in the directory.</p></div>
    @include('portal.application.steps._field', ['name' => 'score', 'label' => 'Overall score (if taken)', 'value' => $d['score'] ?? '', 'placeholder' => 'e.g. 7.5'])
    @include('portal.application.steps._field', ['name' => 'test_date', 'label' => 'Test date (if taken)', 'type' => 'date', 'value' => $d['test_date'] ?? ''])
    @include('portal.application.steps._field', ['name' => 'planned_date', 'label' => 'Planned test date (if not yet taken)', 'type' => 'date', 'value' => $d['planned_date'] ?? ''])
</div>
