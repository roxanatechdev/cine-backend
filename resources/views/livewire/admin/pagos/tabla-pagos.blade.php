<div>
    @php
    $estadoOptions = ['' => 'Todos los estados', 'PENDIENTE' => 'Pendiente', 'APROBADO' => 'Aprobado', 'RECHAZADO' => 'Rechazado'];
    $metodoOptions = ['' => 'Todos los métodos', 'QR' => 'QR', 'TARJETA' => 'Tarjeta'];
    $perPageOptions = ['10' => '10 por página', '25' => '25 por página', '50' => '50 por página', '100' => '100 por página'];
    $hayFiltros = trim($q) !== '' || $estado !== '' || $metodo !== '' || $fechaDesde !== '' || $fechaHasta !== '';
    $columnasOrdenables = [
    'id_pago' => 'Pago', 'id_compra' => 'Compra', 'comprador' => 'Comprador',
    'metodo' => 'Método', 'importe' => 'Importe', 'estado' => 'Estado',
    'referencia_externa' => 'Referencia externa', 'creado_en' => 'Creado', 'aprobado_en' => 'Aprobado',
    ];
    @endphp

    <x-admin.tabla.contenedor class="admin-table-card rounded-3">
        <x-admin.tabla.filtros tag="div" class="admin-table-filters px-2 py-3 p-md-4">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-xl-5">
                    <label for="pagos-busqueda" class="form-label">Buscar pago</label>
                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
                        <input
                            id="pagos-busqueda"
                            type="search"
                            aria-label="Buscar pagos"
                            maxlength="100"
                            class="form-control cm-field-filter ps-5"
                            wire:model.live.debounce.300ms="q"
                            placeholder="ID de pago/compra, comprador, correo o referencia…"
                            autocomplete="off">
                    </div>
                </div>

                <div class="col-6 col-sm-6 col-xl-3">
                    <label for="pagos-estado_toggle" class="form-label">Estado</label>
                    <x-form.select-filter name="estado" id="pagos-estado" :options="$estadoOptions" :selected="$estado"
                        placeholder="Todos los estados" wire:model.live="estado" aria-label="Filtrar por estado del pago" />
                </div>

                <div class="col-6 col-sm-6 col-xl-4">
                    <label for="pagos-metodo_toggle" class="form-label">Método</label>
                    <x-form.select-filter name="metodo" id="pagos-metodo" :options="$metodoOptions" :selected="$metodo"
                        placeholder="Todos los métodos" wire:model.live="metodo" aria-label="Filtrar por método de pago" />
                </div>

                <!-- ================= Fila 2: Fechas, Paginación y Limpieza (Suma: 3 + 3 + 3 + 3 = 12) ================= -->
                <div class="col-6 col-sm-6 col-md-3 col-xl-3">
                    <label for="pagos-desde" class="form-label">Desde</label>
                    <input id="pagos-desde" type="date" class="form-control" wire:model.live="fechaDesde" aria-label="Fecha inicial de creación del pago">
                </div>

                <div class="col-6 col-sm-6 col-md-3 col-xl-3">
                    <label for="pagos-hasta" class="form-label">Hasta</label>
                    <input id="pagos-hasta" type="date" class="form-control" wire:model.live="fechaHasta" aria-label="Fecha final de creación del pago">
                </div>

                <div class="col-6 col-sm-6 col-md-3 col-xl-3">
                    <label for="pagos-per-page_toggle" class="form-label">Registros por página</label>
                    <x-form.select-filter name="perPage" id="pagos-per-page" :options="$perPageOptions" :selected="(string) $perPage"
                        placeholder="10 por página" wire:model.live="perPage" aria-label="Registros por página" />
                </div>

                <!-- Columna del Botón Limpiar con reserva de espacio para evitar saltos -->
                <div class="col-6 col-sm-6 col-md-3 col-xl-3 d-flex align-items-end">
                    @if ($hayFiltros)
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="limpiarFiltros" wire:loading.attr="disabled" style="min-height: 2.375rem;">
                        <span class="material-symbols-outlined align-middle me-1" aria-hidden="true">filter_alt_off</span>
                        Limpiar filtros
                    </button>
                    @else
                    <!-- Espaciador invisible que mantiene la estructura simétrica cuando no hay filtros -->
                    <div class="w-100" style="min-height: 2.375rem;" aria-hidden="true"></div>
                    @endif
                </div>

            </div>
        </x-admin.tabla.filtros>

        @if ($this->pagos->isEmpty())
        <div class="admin-table-empty">
            <h2 class="h5">No se encontraron pagos</h2>
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
                    @foreach ($this->pagos as $pago)
                    <tr wire:key="pago-{{ $pago->id_pago }}">
                        <td class="fw-semibold text-nowrap">#{{ $pago->id_pago }}</td>
                        <td><a href="{{ route('admin.compras.show', $pago->id_compra) }}" class="link-info">#{{ $pago->id_compra }}</a></td>
                        <td><span class="fw-semibold">{{ $pago->comprador_nombre }}</span>
                            <div class="muted small">{{ $pago->comprador_correo }}</div>
                        </td>
                        <td>{{ $pago->metodo }}</td>
                        <td class="text-nowrap">{{ $pago->compra_moneda }} {{ number_format((float) $pago->importe, 2, ',', '.') }}</td>
                        <td><span class="badge bg-{{ match($pago->estado) {'APROBADO' => 'success', 'RECHAZADO' => 'secondary', 'PENDIENTE' => 'warning', default => 'info'} }}">{{ $pago->estado }}</span></td>
                        <td class="text-break">{{ $pago->referencia_externa ?: '—' }}</td>
                        <td class="text-nowrap">{{ $pago->creado_en ? \Illuminate\Support\Carbon::parse($pago->creado_en)->format('d/m/Y H:i') : '—' }}</td>
                        <td class="text-nowrap">{{ $pago->aprobado_en ? \Illuminate\Support\Carbon::parse($pago->aprobado_en)->format('d/m/Y H:i') : '—' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.pagos.show', $pago->id_pago) }}" class="admin-action-icon"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Ver detalle"
                                aria-label="Ver detalle del pago #{{ $pago->id_pago }}">
                                <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.tabla.pie :paginator="$this->pagos" label="pagos" class="admin-table-footer p-3" />
        @endif
    </x-admin.tabla.contenedor>

    @script
    <script>
        document.addEventListener('click', function(event) {
            const option = event.target.closest('[data-dropdown-option]');
            if (!option) return;
            const dropdown = option.closest('.form-cm__dropdown');
            if (!dropdown) return;
            const input = dropdown.querySelector('input[type="hidden"]');
            const label = dropdown.querySelector('[data-dropdown-label]');
            if (!input || !label) return;
            input.value = option.dataset.value ?? '';
            label.textContent = option.textContent.trim();
            dropdown.querySelectorAll('[data-dropdown-option]').forEach((item) => {
                const active = item === option;
                item.classList.toggle('active', active);
                if (active) item.setAttribute('aria-current', 'true');
                else item.removeAttribute('aria-current');
            });
            input.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            input.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        });
    </script>
    @endscript
</div>