@if ($paginator->hasPages())
<nav class="mt-4 flex items-center justify-between gap-4 text-[0.875rem]" aria-label="Pagination">
    @if ($paginator->onFirstPage())<span class="btn btn-tertiary opacity-50" aria-disabled="true">Previous</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-tertiary">Previous</a>@endif
    @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-tertiary">Next</a>@else<span class="btn btn-tertiary opacity-50" aria-disabled="true">Next</span>@endif
</nav>
@endif
