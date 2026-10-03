@props(['date' => null, 'intake' => null])
@if($date || $intake)
<p {{ $attributes->merge(['class' => 'text-[0.8125rem] text-ink-500 flex flex-wrap gap-x-3']) }}>
    @if($intake)<span class="chip chip-info">For {{ $intake }} entry</span>@endif
    @if($date)<span>Last reviewed: <time datetime="{{ $date }}">{{ \Illuminate\Support\Carbon::parse($date)->format('j F Y') }}</time></span>@endif
</p>
@endif
