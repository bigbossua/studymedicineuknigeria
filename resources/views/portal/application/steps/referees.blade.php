@php $refs = $d['referees'] ?? [[]]; @endphp
<p class="hint">UCAS requires one academic reference. Give the person who knows your recent study best, usually a teacher, principal or lecturer. We will not contact them without your consent below.</p>
<div data-repeat>
    <div data-rows class="space-y-4">@foreach($refs as $i => $r)@include('portal.application.steps._ref_item', ['i' => $i, 'r' => $r])@endforeach</div>
    <template data-row>@include('portal.application.steps._ref_item', ['i' => '__i__', 'r' => []])</template>
    <button type="button" data-add-row class="btn btn-tertiary mt-3">+ Add a second referee</button>
</div>
<label class="flex items-start gap-3 text-[0.9375rem]"><input type="checkbox" name="consent_contact_referees" value="1" class="mt-1 w-4 h-4" @checked(!empty($d['consent_contact_referees']))> I consent to StudyMedicineUKNigeria contacting the referee(s) above about my application if needed. <span class="text-accent-600">*</span></label>
