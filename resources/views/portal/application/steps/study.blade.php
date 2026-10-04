<div class="grid sm:grid-cols-2 gap-5">
    <div class="field"><label for="course_family" class="label">What do you want to study? <span class="text-accent-600">*</span></label>
        <select id="course_family" name="course_family" class="input">@foreach(['medicine'=>'Medicine (standard entry, MBBS/MBChB)','graduate_medicine'=>'Graduate Entry Medicine (I have or will have a degree)','foundation_medicine'=>'Medicine with a foundation year first','dentistry'=>'Dentistry','other'=>'Other healthcare course'] as $v=>$l)<option value="{{ $v }}" @selected(($d['course_family'] ?? 'medicine')===$v)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label for="entry_type" class="label">Entry type <span class="text-accent-600">*</span></label>
        <select id="entry_type" name="entry_type" class="input">@foreach(['standard'=>'Standard entry (school-leaver qualifications)','graduate'=>'Graduate entry','foundation'=>'Foundation / gateway year first'] as $v=>$l)<option value="{{ $v }}" @selected(($d['entry_type'] ?? 'standard')===$v)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label for="intake_year" class="label">Entry year <span class="text-accent-600">*</span></label>
        <select id="intake_year" name="intake_year" class="input">@foreach(range(now()->year + 1, now()->year + 4) as $y)<option value="{{ $y }}" @selected((int)($d['intake_year'] ?? $application->intake_year)===$y)>September {{ $y }}</option>@endforeach</select></div>
    <div class="field"><label for="ucas_status" class="label">Your UCAS application <span class="text-accent-600">*</span></label>
        <select id="ucas_status" name="ucas_status" class="input">@foreach(['not_started'=>'Not started','started'=>'Started but not submitted','submitted'=>'Submitted','offer'=>'I already hold an offer'] as $v=>$l)<option value="{{ $v }}" @selected(($d['ucas_status'] ?? 'not_started')===$v)>{{ $l }}</option>@endforeach</select></div>
</div>
<fieldset class="field"><legend class="label">Preferred universities (up to 4, optional)</legend>
    <p class="hint">Leave blank if you want us to shortlist with you from the directory.</p>
    <div class="grid sm:grid-cols-2 gap-3 mt-2">@for($i=0;$i<4;$i++)<input name="preferred_universities[{{ $i }}]" value="{{ $d['preferred_universities'][$i] ?? '' }}" class="input" placeholder="University {{ $i+1 }}" aria-label="Preferred university {{ $i+1 }}">@endfor</div>
</fieldset>
