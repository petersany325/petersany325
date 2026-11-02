@if ($paginator->hasPages())
    <div class="pagination">
        @if ($paginator->onFirstPage())
            <span>قبلی</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}">قبلی</a>
        @endif

        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if ($page == $paginator->currentPage())
                <span class="active"><span>{{ $page }}</span></span>
            @else
                <a href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">بعدی</a>
        @else
            <span>بعدی</span>
        @endif
    </div>
@endif
