@php $exp = $d['experience'] ?? [[]]; @endphp
<div data-repeat>
    <p class="label">Work, volunteering or caring experience</p>
    <p class="hint mb-3">Medical schools value what you learned, not the prestige of the setting. Hospital, clinic, pharmacy, care-home, community or caring-for-family experience all count.</p>
    <div data-rows class="space-y-4">
        @foreach($exp as $i => $e)@include('portal.application.steps._exp_item', ['i' => $i, 'e' => $e])@endforeach
    </div>
    <template data-row>@include('portal.application.steps._exp_item', ['i' => '__i__', 'e' => []])</template>
    <button type="button" data-add-row class="btn btn-tertiary mt-3">+ Add experience</button>
</div>
<div class="field"><label for="statement_status" class="label">Personal statement <span class="text-accent-600">*</span></label>
    <select id="statement_status" name="statement_status" class="input max-w-xs">@foreach(['not_started'=>'Not started','draft'=>'Draft','final'=>'Final'] as $v=>$l)<option value="{{ $v }}" @selected(($d['statement_status'] ?? 'not_started')===$v)>{{ $l }}</option>@endforeach</select>
    @php($psFormat = \App\Models\Topic::bySlug('ucas-2027')?->fact('personal_statement_format'))
    <p class="hint">@if($psFormat?->isPublishable())UCAS format: {{ $psFormat->value_text }} (official source: UCAS). @else UCAS sets the personal statement format each cycle; check its current guidance before you finalise. @endif You can draft here or upload a file in Documents. We give structural feedback; we do not write it for you.</p></div>
<div class="field"><label for="statement_text" class="label">Draft text (optional)</label><textarea id="statement_text" name="statement_text" rows="10" maxlength="4200" class="input min-h-48">{{ $d['statement_text'] ?? '' }}</textarea></div>
