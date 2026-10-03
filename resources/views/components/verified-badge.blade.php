@props(['status' => 'VERIFY-ON-PAGE', 'date' => null])
@php
    $map = [
        'VERIFIED' => ['chip-verified', 'Verified'],
        'VERIFY-ON-PAGE' => ['chip-pending', 'Verification pending'],
        'REVIEW_DUE' => ['chip-review', 'Review due'],
        'SOURCE_CHANGED' => ['chip-review', 'Source changed'],
        'NOT_PUBLISHED' => ['chip-notpublished', 'Not published by the university'],
        'NOT PUBLISHED' => ['chip-notpublished', 'Not published by the university'],
        'DATA_UNAVAILABLE' => ['chip-notpublished', 'Not available'],
        'ARCHIVED' => ['chip-notpublished', 'Archived'],
    ];
    [$cls, $label] = $map[$status] ?? ['chip-pending', $status];
@endphp
<span {{ $attributes->merge(['class' => "chip $cls"]) }} aria-label="Verification status: {{ $label }}{{ $date ? ', '.$date : '' }}">
    @if($status === 'VERIFIED')<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>@endif
    {{ $label }}@if($date && $status === 'VERIFIED') · {{ $date }}@endif
</span>
