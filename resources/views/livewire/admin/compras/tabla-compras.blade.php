<div>
    @php
    $estadoOptions = [
    '' => 'Todos los estados',
    'INICIADA' => 'Iniciada',
    'PENDIENTE_PAGO' => 'Pendiente de pago',
    'CONFIRMADA' => 'Confirmada',
    'CANCELADA' => 'Cancelada',
    'EXPIRADA' => 'Expirada',
    ];
    $perPageOptions = ['10' => '10 por página', '25' => '25 por página', '50' => '50 por página', '100' => '100 por página'];
    $hayFiltros = trim($q) !== '' || $estado !== '' || $fechaDesde !== '' || $fechaHasta !== '';
    $columnasOrdenables = [
    'id_compra' => 'ID', 'comprador' => 'Comprador', 'creada_en' => 'Creada',
    'vence_reserva' => 'Vence reserva', 'total' => 'Total', 'estado' => 'Estado',
    ];
    @endphp

    <x-admin.tabla.contenedor class="admin-table-card rounded-3">
        <div class="p-3 p-md-4 admin-table-filters border-bottom">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Historial de compras</h2>
                    <p class="admin-table-count mb-0" aria-live="polite">
                        {{ $this->compras->total() }} {{ $this->compras->total() === 1 ? 'compra encontrada' : 'compras encontradas' }}
                    </p>
                </div>
            </div>

            <div class="row g-2 align-items-end">
                <div class="col-12 col-xl-4">
                    <label for="compras-busqueda" class="form-label">Buscar compra o comprador</label>

                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
                        <input
                            id="compras-busqueda"
                            type="search"
                            aria-label="Buscar compras"
                            maxlength="150"
                            class="form-control cm-field-filter ps-5"
                            wire:model.live.debounce.300ms="q"
                            placeholder="ID de compra, nombre o correo…"
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label for="compras-estado_toggle" class="form-label">Estado de la compra</label>
                    <x-form.select-filter name="estado" id="compras-estado" :options="$estadoOptions" :selected="$estado"
                        placeholder="Todos los estados" wire:model.live="estado" aria-label="Filtrar por estado de compra" />
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label for="compras-per-page_toggle" class="form-label">Registros por página</label>
                    <x-form.select-filter name="perPage" id="compras-per-page" :options="$perPageOptions" :selected="(string) $perPage"
                        placeholder="10 por página" wire:model.live="perPage" aria-label="Registros por página" />
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label for="compras-desde" class="form-label">Desde</label>
                    <input id="compras-desde" type="date" class="form-control" wire:model.live="fechaDesde" aria-label="Fecha inicial de compra">
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label for="compras-hasta" class="form-label">Hasta</label>
                    <input id="compras-hasta" type="date" class="form-control" wire:model.live="fechaHasta" aria-label="Fecha final de compra">
                </div>
            </div>

            @if ($hayFiltros)
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="limpiarFiltros" wire:loading.attr="disabled">
                    <span class="material-symbols-outlined align-middle" aria-hidden="true">filter_alt_off</span>
                    Limpiar filtros
                </button>
            </div>
            @endif
        </div>

        @if ($this->compras->isEmpty())
        <div class="admin-table-empty">
            <h2 class="h5">No se encontraron compras</h2>
            <p class="mb-0">Prueba con otros criterios de búsqueda o limpia los filtros para ver el historial completo.</p>
            @if ($hayFiltros)
            <button type="button" class="btn btn-outline-light mt-3" wire:click="limpiarFiltros">Limpiar filtros</button>
            @endif
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle admin-table mb-0">
                <thead>
                    <tr>
                        @foreach ($columnasOrdenables as $campo => $etiqueta)
                        <th scope="col" aria-sort="{{ $sort === $campo ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" class="btn btn-link p-0 text-decoration-none text-reset fw-semibold d-inline-flex align-items-center gap-1"
                                wire:click="sortBy('{{ $campo }}')" wire:loading.attr="disabled" aria-label="Ordenar por {{ $etiqueta }}">
                                <span>{{ $etiqueta }}</span>
                                <span class="material-symbols-outlined" style="font-size:18px" aria-hidden="true">{{ $this->indicadorOrden($campo) }}</span>
                            </button>
                        </th>
                        @endforeach
                        <th scope="col" class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->compras as $compra)
                    <tr wire:key="compra-{{ $compra->id_compra }}">
                        <td class="fw-semibold text-nowrap">#{{ $compra->id_compra }}</td>
                        <td>
                            <span class="fw-semibold">{{ $compra->comprador?->nombre ?? 'Comprador no disponible' }}</span>
                            <div class="muted small">{{ $compra->comprador?->correo ?? '—' }}</div>
                        </td>
                        <td class="text-nowrap">{{ $compra->creada_en?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-nowrap">{{ $compra->vence_reserva?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-nowrap">{{ $compra->moneda }} {{ number_format((float) $compra->total, 2, ',', '.') }}</td>
                        <td><span class="badge bg-{{ match($compra->estado) {'CONFIRMADA' => 'success', 'CANCELADA','EXPIRADA' => 'secondary', 'PENDIENTE_PAGO' => 'warning', default => 'info'} }}">{{ str_replace('_', ' ', $compra->estado) }}</span></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.compras.show', $compra->id_compra) }}" class="btn admin-action-icon"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Ver detalle"
                                aria-label="Ver detalle de la compra #{{ $compra->id_compra }}">
                                <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.tabla.pie :paginator="$this->compras" label="compras" class="admin-table-footer p-3" />
        @endif
    </x-admin.tabla.contenedor>
</div>