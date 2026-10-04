<fieldset class="card space-y-4"><legend class="font-semibold">UCAT</legend>
    <div class="grid sm:grid-cols-3 gap-4">
        <div class="field"><label for="ucat_status" class="label">Status <span class="text-accent-600">*</span></label><select id="ucat_status" name="ucat_status" class="input">@foreach(['taken'=>'Taken','planned'=>'Planned','not_planned'=>'Not planned'] as $v=>$l)<option value="{{ $v }}" @selected(($d['ucat_status'] ?? 'planned')===$v)>{{ $l }}</option>@endforeach</select></div>
        @include('portal.application.steps._field', ['name' => 'ucat_year', 'label' => 'Test year', 'value' => $d['ucat_year'] ?? '', 'placeholder' => (string) now()->year])
        @include('portal.application.steps._field', ['name' => 'ucat_score', 'label' => 'Total score (/2700)', 'value' => $d['ucat_score'] ?? '', 'hint' => 'Since 2025 the UCAT has three cognitive sections; totals are out of 2700.'])
        @include('portal.application.steps._field', ['name' => 'ucat_sjt', 'label' => 'SJT band', 'value' => $d['ucat_sjt'] ?? '', 'placeholder' => 'Band 1–4'])
    </div>
</fieldset>
<fieldset class="card space-y-4"><legend class="font-semibold">GAMSAT (graduate entry only)</legend>
    <div class="grid sm:grid-cols-3 gap-4">
        <div class="field"><label for="gamsat_status" class="label">Status <span class="text-accent-600">*</span></label><select id="gamsat_status" name="gamsat_status" class="input">@foreach(['not_planned'=>'Not planned','planned'=>'Planned','taken'=>'Taken'] as $v=>$l)<option value="{{ $v }}" @selected(($d['gamsat_status'] ?? 'not_planned')===$v)>{{ $l }}</option>@endforeach</select></div>
        @include('portal.application.steps._field', ['name' => 'gamsat_score', 'label' => 'Overall score', 'value' => $d['gamsat_score'] ?? ''])
    </div>
</fieldset>
