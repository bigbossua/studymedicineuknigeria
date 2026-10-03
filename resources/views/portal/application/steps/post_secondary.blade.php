<label class="flex items-center gap-3 text-[0.9375rem]"><input type="checkbox" name="none" value="1" class="w-4 h-4" @checked(!empty($d['none']))> I have not studied anything after secondary school yet</label>
@php $items = $d['items'] ?? [[]]; @endphp
<div data-repeat>
    <div data-rows class="space-y-5">
        @foreach($items as $i => $it)@include('portal.application.steps._post_item', ['i' => $i, 'it' => $it])@endforeach
    </div>
    <template data-row>@include('portal.application.steps._post_item', ['i' => '__i__', 'it' => []])</template>
    <button type="button" data-add-row class="btn btn-tertiary mt-3">+ Add another qualification</button>
</div>
