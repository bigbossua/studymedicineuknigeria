{{-- list of university statements (ReferenceFact with subject University) --}}
@props(['items', 'empty' => 'No published statement was located.'])
@if($items->isEmpty())
    <x-alert type="info" class="mt-4">{{ $empty }}</x-alert>
@else
<div class="mt-4 space-y-3">
    @foreach($items->sortBy(fn ($f) => ($f->verification_status === 'NOT_PUBLISHED' ? '1' : '0').$f->subject?->name) as $f)
        <x-fact :status="$f->verification_status" :source="$f->source_url" :verified-at="$f->verified_at?->format('j M Y')">
            <p class="font-semibold"><a href="{{ route('schools.show', $f->subject) }}" class="no-underline hover:underline text-ink-900">{{ $f->subject->name }}</a>@if($f->academic_year) <span class="text-ink-500 font-normal text-[0.875rem]">· {{ $f->academic_year }}</span>@endif</p>
            <p class="mt-1 text-[0.9375rem]">{{ $f->displayValue() }}</p>
        </x-fact>
    @endforeach
</div>
@endif
