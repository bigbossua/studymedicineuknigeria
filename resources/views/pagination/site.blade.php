@if ($paginator->hasPages())
<nav class="mt-4 flex items-center justify-between gap-4 text-[0.875rem]" aria-label="Pagination">
    <p class="text-ink-500">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
    <ul class="flex flex-wrap items-center gap-1">
        @if ($paginator->onFirstPage())
            <li><span class="btn btn-tertiary opacity-50" aria-disabled="true">Previous</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-tertiary">Previous</a></li>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element))<li><span class="px-2 text-ink-500">{{ $element }}</span></li>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li><span class="chip chip-info" aria-current="page">{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}" class="px-2 py-1" aria-label="Page {{ $page }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-tertiary">Next</a></li>
        @else
            <li><span class="btn btn-tertiary opacity-50" aria-disabled="true">Next</span></li>
        @endif
    </ul>
</nav>
@endif
