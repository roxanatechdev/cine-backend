@if ($paginator->hasPages())
<nav role="navigation" aria-label="Paginación del catálogo">
    <ul class="pagination cineprime-pagination mb-0">

        {{-- Página anterior --}}
        @if ($paginator->onFirstPage())
        <li class="page-item disabled" aria-disabled="true">
            <span class="page-link fs-4" aria-hidden="true">&lsaquo;</span>
        </li>
        @else
        <li class="page-item">
            <button
                type="button"
                class="page-link fs-4"
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
                aria-label="Ir a la página anterior">
                &lsaquo;
            </button>
        </li>
        @endif

        {{-- Números de página --}}
        @foreach ($elements as $element)

        {{-- Separador cuando hay muchas páginas --}}
        @if (is_string($element))
        <li class="page-item disabled" aria-disabled="true">
            <span class="page-link">{{ $element }}</span>
        </li>
        @endif

        {{-- Enlaces de cada grupo de páginas --}}
        @if (is_array($element))
        @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())
        <li class="page-item active" aria-current="page">
            <span class="page-link">{{ $page }}</span>
        </li>
        @else
        <li class="page-item">
            <button
                type="button"
                class="page-link"
                wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
                aria-label="Ir a la página {{ $page }}">
                {{ $page }}
            </button>
        </li>
        @endif
        @endforeach
        @endif

        @endforeach

        {{-- Página siguiente --}}
        @if ($paginator->hasMorePages())
        <li class="page-item">
            <button
                type="button"
                class="page-link fs-4"
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
                aria-label="Ir a la página siguiente">
                &rsaquo;
            </button>
        </li>
        @else
        <li class="page-item disabled" aria-disabled="true">
            <span class="page-link fs-4" aria-hidden="true">&rsaquo;</span>
        </li>
        @endif

    </ul>
</nav>
@endif