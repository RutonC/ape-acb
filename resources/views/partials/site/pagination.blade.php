@if ($paginator->hasPages())
    <nav class="site-pagination" aria-label="{{ __('Paginação') }}">
        @if ($paginator->onFirstPage())
            <span class="site-pagination__item is-disabled" aria-hidden="true"><x-heroicon-o-chevron-left class="size-4" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="site-pagination__item" rel="prev" aria-label="{{ __('Anterior') }}"><x-heroicon-o-chevron-left class="size-4" /></a>
        @endif

        @foreach (range(1, $paginator->lastPage()) as $number)
            @if ($number === $paginator->currentPage())
                <span class="site-pagination__item is-active" aria-current="page">{{ $number }}</span>
            @else
                <a href="{{ $paginator->url($number) }}" class="site-pagination__item">{{ $number }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="site-pagination__item" rel="next" aria-label="{{ __('Seguinte') }}"><x-heroicon-o-chevron-right class="size-4" /></a>
        @else
            <span class="site-pagination__item is-disabled" aria-hidden="true"><x-heroicon-o-chevron-right class="size-4" /></span>
        @endif
    </nav>
@endif
