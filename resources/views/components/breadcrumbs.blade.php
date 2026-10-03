@props(['items' => []])
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <li><a href="{{ route('home') }}">Home</a></li>
        @foreach($items as $i => $item)
            <li aria-hidden="true" class="text-ink-300">/</li>
            <li>
                @if(! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" class="text-ink-700">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
