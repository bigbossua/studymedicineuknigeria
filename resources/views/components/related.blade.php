@props(['title' => 'You may also need', 'items' => []])
@if(count($items))
<aside {{ $attributes->merge(['class' => 'mt-14']) }} aria-labelledby="related-heading">
    <p id="related-heading" class="eyebrow mb-4">{{ $title }}</p>
    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($items as $item)
            <li>
                <a href="{{ $item['url'] }}" class="card card-link h-full">
                    <span class="font-serif font-semibold text-lg text-ink-900 block">{{ $item['label'] }}</span>
                    @if(! empty($item['description']))<span class="mt-1 block text-[0.9375rem] text-ink-500">{{ $item['description'] }}</span>@endif
                </a>
            </li>
        @endforeach
    </ul>
</aside>
@endif
