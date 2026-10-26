@props([
    'paginator',
    'label' => 'registros',
    'paginationView' => 'vendor.livewire.pagination-bootstrap',
])

@if ($paginator->total() > 0)
    <div {{ $attributes->class(['footer-table d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-3 border-top border-secondary border-opacity-50 px-3']) }}>
        <div class="text-secondary small" aria-live="polite">
            Mostrando
            <strong class="text-white">{{ $paginator->firstItem() ?? 0 }}</strong>
            a
            <strong class="text-white">{{ $paginator->lastItem() ?? 0 }}</strong>
            de
            <strong class="text-white">{{ $paginator->total() }}</strong>
            {{ $label }}
        </div>

        @if ($paginator->hasPages())
            <x-admin.tabla.paginacion class="catalogo-paginacion">
                {{ $paginator->links($paginationView) }}
            </x-admin.tabla.paginacion>
        @endif
    </div>
@endif
