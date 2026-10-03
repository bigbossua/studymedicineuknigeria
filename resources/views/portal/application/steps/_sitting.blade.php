<fieldset class="card space-y-4" data-row-item>
    <div class="flex items-start justify-between gap-3"><legend class="font-semibold">Sitting</legend><button type="button" data-remove-row class="btn btn-tertiary text-[0.875rem]">Remove</button></div>
    <div class="grid sm:grid-cols-3 gap-4">
        <div class="field"><label class="label" for="sit-board-{{ $i }}">Exam board</label><select id="sit-board-{{ $i }}" name="sittings[{{ $i }}][board]" class="input">@foreach(['WAEC'=>'WAEC (WASSCE)','NECO'=>'NECO (SSCE)','CAMBRIDGE'=>'Cambridge IGCSE / O-level','IB'=>'IB (MYP/other)','OTHER'=>'Other'] as $v=>$l)<option value="{{ $v }}" @selected(($s['board'] ?? '')===$v)>{{ $l }}</option>@endforeach</select></div>
        <div class="field"><label class="label" for="sit-year-{{ $i }}">Year</label><input id="sit-year-{{ $i }}" name="sittings[{{ $i }}][year]" inputmode="numeric" value="{{ $s['year'] ?? '' }}" class="input" placeholder="2025"></div>
        <div class="field"><label class="label" for="sit-school-{{ $i }}">School</label><input id="sit-school-{{ $i }}" name="sittings[{{ $i }}][school]" value="{{ $s['school'] ?? '' }}" class="input"></div>
    </div>
    <div>
        <p class="label">Subjects and grades <span class="hint font-normal">(at least 3; include English, Mathematics, Biology, Chemistry and Physics where taken)</span></p>
        <div class="grid sm:grid-cols-3 gap-2 mt-2">
            @foreach(($s['subjects'] ?? [[],[],[],[],[],[],[],[],[]]) as $j => $sub)
                <div class="flex gap-2">
                    <input name="sittings[{{ $i }}][subjects][{{ $j }}][subject]" value="{{ $sub['subject'] ?? ['English Language','Mathematics','Biology','Chemistry','Physics','','','',''][$j] ?? '' }}" class="input" placeholder="Subject" aria-label="Subject {{ $j+1 }}">
                    <select name="sittings[{{ $i }}][subjects][{{ $j }}][grade]" class="input w-28" aria-label="Grade {{ $j+1 }}"><option value="">Grade</option>@foreach(['A1','B2','B3','C4','C5','C6','D7','E8','F9','A*','A','B','C','D','E','7','6','5','4','3'] as $g)<option value="{{ $g }}" @selected(($sub['grade'] ?? '')===$g)>{{ $g }}</option>@endforeach</select>
                </div>
            @endforeach
        </div>
    </div>
</fieldset>
