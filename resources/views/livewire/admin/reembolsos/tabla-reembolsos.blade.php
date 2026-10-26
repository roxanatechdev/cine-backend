<div>
    @php
        $estadoOptions = ['' => 'Todos los estados', 'SOLICITADO' => 'Solicitado', 'PROCESANDO' => 'Procesando', 'COMPLETADO' => 'Completado', 'RECHAZADO' => 'Rechazado'];
        $hayFiltros = trim($q) !== '' || $estado !== '' || $fechaDesde !== '' || $fechaHasta !== '';
        $columnas = ['id_reembolso' => 'Reembolso', 'id_pago' => 'Pago', 'id_compra' => 'Compra', 'comprador' => 'Comprador', 'importe' => 'Importe', 'estado' => 'Estado', 'solicitado_en' => 'Solicitado', 'completado_en' => 'Completado', 'referencia_externa' => 'Referencia externa'];
    @endphp

    <x-admin.tabla.contenedor class="admin-table-card rounded-3">
        <x-admin.tabla.filtros tag="div" class="admin-table-filters px-3 py-3 p-md-4">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-xl-5">
                    <label for="reembolsos-buscar" class="form-label">Buscar reembolso</label>
                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" aria-hidden="true">search</span>
                        <input id="reembolsos-buscar" type="search" maxlength="150" class="form-control cm-field-filter ps-5" wire:model.live.debounce.300ms="q" placeholder="ID de reembolso/pago/compra, comprador, correo o referencia" autocomplete="off" aria-label="Buscar reembolsos">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="reembolsos-estado_toggle" class="form-label">Estado</label>
                    <x-form.select-filter name="estado" id="reembolsos-estado" :options="$estadoOptions" :selected="$estado" placeholder="Todos los estados" wire:model.live="estado" aria-label="Filtrar por estado del reembolso" />
                </div>
                <div class="col-6 col-sm-3 col-xl-2">
                    <label for="reembolsos-desde" class="form-label">Solicitado desde</label>
                    <input id="reembolsos-desde" type="date" class="form-control" wire:model.live="fechaDesde" aria-label="Fecha inicial de solicitud">
                </div>
                <div class="col-6 col-sm-3 col-xl-2">
                    <label for="reembolsos-hasta" class="form-label">Solicitado hasta</label>
                    <input id="reembolsos-hasta" type="date" class="form-control" wire:model.live="fechaHasta" aria-label="Fecha final de solicitud">
                </div>
                @if ($hayFiltros)
                    <div class="col-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="limpiarFiltros" wire:loading.attr="disabled" wire:target="limpiarFiltros">
                            <span class="material-symbols-outlined align-middle me-1" aria-hidden="true">filter_alt_off</span>Limpiar filtros
                        </button>
                    </div>
                @endif
            </div>
        </x-admin.tabla.filtros>

        @if ($this->reembolsos->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle admin-table mb-0">
                    <thead><tr>
                        @foreach ($columnas as $campo => $etiqueta)
                            <th scope="col" aria-sort="{{ $sort === $campo ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                <button type="button" class="btn btn-link p-0 text-decoration-none text-reset fw-semibold d-inline-flex align-items-center gap-1" wire:click="sortBy('{{ $campo }}')" wire:loading.attr="disabled" wire:target="sortBy" aria-label="Ordenar por {{ $etiqueta }}">
                                    <span>{{ $etiqueta }}</span><span class="material-symbols-outlined" style="font-size:18px" aria-hidden="true">{{ $this->indicadorOrden($campo) }}</span>
                                </button>
                            </th>
                        @endforeach
                        <th scope="col" class="text-end">Acción</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($this->reembolsos as $reembolso)
                            <tr wire:key="reembolso-{{ $reembolso->id_reembolso }}">
                                <td class="fw-semibold text-nowrap">#{{ $reembolso->id_reembolso }}</td>
                                <td><a href="{{ route('admin.pagos.show', $reembolso->id_pago) }}" class="link-info">#{{ $reembolso->id_pago }}</a></td>
                                <td><a href="{{ route('admin.compras.show', $reembolso->id_compra) }}" class="link-info">#{{ $reembolso->id_compra }}</a></td>
                                <td><span class="fw-semibold">{{ $reembolso->comprador_nombre }}</span><div class="muted small">{{ $reembolso->comprador_correo }}</div></td>
                                <td class="text-nowrap">{{ $reembolso->moneda }} {{ number_format((float) $reembolso->importe, 2, ',', '.') }}</td>
                                <td><span class="badge bg-{{ match($reembolso->estado) {'COMPLETADO' => 'success', 'RECHAZADO' => 'secondary', 'PROCESANDO' => 'info', 'SOLICITADO' => 'warning', default => 'secondary'} }}">{{ $reembolso->estado }}</span></td>
                                <td class="text-nowrap">{{ $reembolso->solicitado_en ? \Illuminate\Support\Carbon::parse($reembolso->solicitado_en)->format('d/m/Y H:i') : '—' }}</td>
                                <td class="text-nowrap">{{ $reembolso->completado_en ? \Illuminate\Support\Carbon::parse($reembolso->completado_en)->format('d/m/Y H:i') : '—' }}</td>
                                <td class="text-break">{{ $reembolso->referencia_externa ?: '—' }}</td>
                                <td class="text-end text-nowrap"><a href="{{ route('admin.reembolsos.show', $reembolso->id_reembolso) }}" class="btn admin-action-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Ver detalle" aria-label="Ver detalle del reembolso #{{ $reembolso->id_reembolso }}"><span class="material-symbols-outlined" aria-hidden="true">visibility</span></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.tabla.pie :paginator="$this->reembolsos" label="reembolsos" class="admin-table-footer p-3" />
        @else
            <div class="admin-table-empty">
                <h2 class="h5">{{ $hayFiltros ? 'No se encontraron reembolsos' : 'Todavía no hay reembolsos registrados' }}</h2>
                <p class="mb-0">{{ $hayFiltros ? 'Prueba con otros criterios o limpia los filtros para consultar el historial completo.' : 'Las solicitudes registradas desde pagos aprobados aparecerán aquí.' }}</p>
                @if ($hayFiltros)<button type="button" class="btn btn-outline-secondary mt-3" wire:click="limpiarFiltros">Limpiar filtros</button>@endif
            </div>
        @endif
    </x-admin.tabla.contenedor>

    @script
    <script>
        document.addEventListener('click', function (event) {
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
                if (active) item.setAttribute('aria-current', 'true'); else item.removeAttribute('aria-current');
            });
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    </script>
    @endscript
</div>
