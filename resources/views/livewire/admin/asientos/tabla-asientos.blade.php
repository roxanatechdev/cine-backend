<div>
    @php
        $tipoOptions = ['' => 'Todos los tipos', 'ESTANDAR' => 'Estándar', 'PREFERENCIAL' => 'Preferencial', 'ACCESIBLE' => 'Accesible'];
        $activoOptions = ['' => 'Activos e inactivos', '1' => 'Activo', '0' => 'Inactivo'];
        $hayFiltros = trim($q) !== '' || $idSala !== '' || $tipo !== '' || $activo !== '';
        $columnas = ['sala' => 'Sala', 'fila' => 'Fila', 'numero' => 'Número', 'tipo' => 'Tipo', 'activo' => 'Activo'];
    @endphp

    <x-admin.tabla.contenedor class="admin-table-card rounded-3">
        <x-admin.tabla.filtros tag="div" class="admin-table-filters px-3 py-3 p-md-4">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-xl-4">
                    <label for="asientos-buscar" class="form-label">Buscar asiento</label>
                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" aria-hidden="true">search</span>
                        <input id="asientos-buscar" type="search" maxlength="150" class="form-control cm-field-filter ps-5" wire:model.live.debounce.300ms="q" placeholder="Sala, fila, número o ID de asiento" autocomplete="off" aria-label="Buscar asientos">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="asientos-sala_toggle" class="form-label">Sala</label>
                    <x-form.select-filter name="idSala" id="asientos-sala" :options="$this->opcionesSalas" :selected="$idSala" placeholder="Todas las salas" wire:model.live="idSala" aria-label="Filtrar por sala" />
                </div>
                <div class="col-6 col-sm-3 col-xl-2">
                    <label for="asientos-tipo_toggle" class="form-label">Tipo</label>
                    <x-form.select-filter name="tipo" id="asientos-tipo" :options="$tipoOptions" :selected="$tipo" placeholder="Todos los tipos" wire:model.live="tipo" aria-label="Filtrar por tipo de asiento" />
                </div>
                <div class="col-6 col-sm-3 col-xl-3">
                    <label for="asientos-activo_toggle" class="form-label">Disponibilidad administrativa</label>
                    <x-form.select-filter name="activo" id="asientos-activo" :options="$activoOptions" :selected="$activo" placeholder="Activos e inactivos" wire:model.live="activo" aria-label="Filtrar por estado activo" />
                </div>
                @if ($hayFiltros)
                    <div class="col-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="limpiarFiltros" wire:loading.attr="disabled" wire:target="limpiarFiltros"><span class="material-symbols-outlined align-middle me-1" aria-hidden="true">filter_alt_off</span>Limpiar filtros</button>
                    </div>
                @endif
            </div>
        </x-admin.tabla.filtros>

        @if ($this->asientos->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle admin-table mb-0">
                    <thead><tr>
                        @foreach ($columnas as $campo => $etiqueta)
                            <th scope="col" aria-sort="{{ $sort === $campo ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                <button type="button" class="btn btn-link p-0 text-decoration-none text-reset fw-semibold d-inline-flex align-items-center gap-1" wire:click="sortBy('{{ $campo }}')" wire:loading.attr="disabled" wire:target="sortBy" aria-label="Ordenar por {{ $etiqueta }}"><span>{{ $etiqueta }}</span><span class="material-symbols-outlined" style="font-size:18px" aria-hidden="true">{{ $this->indicadorOrden($campo) }}</span></button>
                            </th>
                        @endforeach
                        <th scope="col" class="text-end">Acciones</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($this->asientos as $asiento)
                            <tr wire:key="asiento-{{ $asiento->id_asiento }}">
                                <td class="fw-semibold">{{ $asiento->sala?->nombre ?? 'Sala no disponible' }}</td>
                                <td class="text-nowrap">{{ $asiento->fila }}</td>
                                <td class="text-nowrap">{{ $asiento->numero }}</td>
                                <td>{{ $tipoOptions[$asiento->tipo] ?? $asiento->tipo }}</td>
                                <td><span class="badge bg-{{ $asiento->activo ? 'success' : 'secondary' }}">{{ $asiento->activo ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.asientos.edit', $asiento->id_asiento) }}" class="btn admin-action-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar" aria-label="Editar asiento {{ $asiento->fila }}-{{ $asiento->numero }} de {{ $asiento->sala?->nombre ?? 'sala no disponible' }}"><span class="material-symbols-outlined" aria-hidden="true">edit</span></a>
                                    <form method="POST" action="{{ route('admin.asientos.destroy', $asiento->id_asiento) }}" class="d-inline" onsubmit="return confirm('¿Eliminar este asiento? Si está asociado a alguna función, no podrá eliminarse.');">@csrf @method('DELETE')<button type="submit" class="btn admin-action-icon admin-action-icon--danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar" aria-label="Eliminar asiento {{ $asiento->fila }}-{{ $asiento->numero }} de {{ $asiento->sala?->nombre ?? 'sala no disponible' }}"><span class="material-symbols-outlined" aria-hidden="true">delete</span></button></form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.tabla.pie :paginator="$this->asientos" label="asientos" class="admin-table-footer p-3" />
        @else
            <div class="admin-table-empty">
                <h2 class="h5">{{ $hayFiltros ? 'No se encontraron asientos' : 'Todavía no hay asientos registrados' }}</h2>
                <p class="mb-3">{{ $hayFiltros ? 'Prueba cambiar los criterios de búsqueda o limpiar los filtros.' : 'Registra un asiento y asígnalo a una sala para comenzar a administrar su disponibilidad.' }}</p>
                @if ($hayFiltros)
                    <button type="button" class="btn btn-outline-secondary" wire:click="limpiarFiltros">Limpiar filtros</button>
                @else
                    <a href="{{ route('admin.asientos.create') }}" class="btn btn-primary">Registrar primer asiento</a>
                @endif
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
