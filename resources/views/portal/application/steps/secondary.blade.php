@php $sittings = $d['sittings'] ?? [['board' => 'WAEC', 'subjects' => [[],[],[],[],[],[],[],[],[]]]]; @endphp
<div data-repeat>
    <div data-rows class="space-y-6">
        @foreach($sittings as $i => $s)
            @include('portal.application.steps._sitting', ['i' => $i, 's' => $s])
        @endforeach
    </div>
    <template data-row>@include('portal.application.steps._sitting', ['i' => '__i__', 's' => ['subjects' => [[],[],[],[],[],[],[],[],[]]]])</template>
    <button type="button" data-add-row class="btn btn-tertiary mt-3">+ Add another sitting (for example NECO as well as WAEC)</button>
</div>
