@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="pagination-link is-disabled" aria-disabled="true">&laquo; Previous</span>
        @else
            <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Previous</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-link is-disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-link is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination-link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &raquo;</a>
        @else
            <span class="pagination-link is-disabled" aria-disabled="true">Next &raquo;</span>
        @endif
    </nav>
@endif
