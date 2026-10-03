@props(['type' => 'info', 'title' => null])
<div {{ $attributes->merge(['class' => "alert alert-$type"]) }} role="{{ $type === 'danger' ? 'alert' : 'note' }}">
    @if($title)<p class="font-semibold mb-1">{{ $title }}</p>@endif
    <div>{{ $slot }}</div>
</div>
