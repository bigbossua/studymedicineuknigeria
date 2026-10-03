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
        'NOT_FOUND' => ['chip-notpublished', 'Not located in research'],
        'ARCHIVED' => ['chip-notpublished', 'Archived'],
    ];
    [$cls, $label] = $map[$status] ?? ['chip-pending', $status];
@endphp
<a href="{{ route('verify') }}#statuses" {{ $attributes->merge(['class' => "chip $cls no-underline"]) }} aria-label="Verification status: {{ $label }}{{ $date ? ', '.$date : '' }}. What this means" title="What this label means">
    @if($status === 'VERIFIED')<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>@endif
    {{ $label }}@if($date && $status === 'VERIFIED') · {{ $date }}@endif
</a>
