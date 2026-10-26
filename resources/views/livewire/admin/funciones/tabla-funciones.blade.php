<div>
    @php
    $estadoOptions = [
    '' => 'Todos los estados',
    'PROGRAMADA' => 'Programada',
    'EN_CURSO' => 'En curso',
    'FINALIZADA' => 'Finalizada',
    'CANCELADA' => 'Cancelada',
    ];
    $salaOptions = $this->salas
    ->mapWithKeys(fn ($sala) => [(string) $sala->id_sala => $sala->nombre])
    ->prepend('Todas las salas', '')
    ->all();
    $perPageOptions = [
    '10' => '10',
    '20' => '20',
    '50' => '50',
    '100' => '100',
    ];
    @endphp

    <x-admin.tabla.contenedor class="admin-table-card">
        <div class="p-3 p-md-4 border-bottom admin-function-filters">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-3">
                    <label for="funciones-busqueda" class="form-label">Película</label>
                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
                        <input
                            id="funciones-busqueda"
                            type="search"
                            aria-label="Buscar películas por título"
                            maxlength="100"
                            class="form-control cm-field-filter ps-5"
                            wire:model.live.debounce.300ms="q"
                            placeholder="Buscar nombre"
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="funciones-estado_toggle" class="form-label">Estado</label>
                    <x-form.select-filter
                        name="estado"
                        id="funciones-estado"
                        :options="$estadoOptions"
                        :selected="$estado"
                        placeholder="Todos los estados"
                        wire:model.live="estado" />
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="funciones-sala_toggle" class="form-label">Sala</label>
                    <x-form.select-filter
                        name="idSala"
                        id="funciones-sala"
                        :options="$salaOptions"
                        :selected="$idSala"
                        placeholder="Todas las salas"
                        wire:model.live="idSala" />
                </div>
                <div class="col-6 col-lg-2">
                    <label for="funciones-desde" class="form-label">Desde</label>
                    <input id="funciones-desde" type="date" class="form-control" wire:model.live="fechaDesde">
                </div>
                <div class="col-6 col-lg-2">
                    <label for="funciones-hasta" class="form-label">Hasta</label>
                    <input id="funciones-hasta" type="date" class="form-control" wire:model.live="fechaHasta">
                </div>

            </div>
            <div class="d-flex flex-row flex-sm-row justify-content-between align-items-sm-center gap-2 mt-3">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" wire:click="limpiarFiltros"
                        @if ($q==='' && $estado==='' && $idSala==='' && $fechaDesde==='' && $fechaHasta==='' ) disabled @endif>
                        <span class="material-symbols-outlined align-middle" aria-hidden="true">filter_alt_off</span>
                        Limpiar
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label for="funciones-per-page_toggle" class="form-label mb-0 small">Mostrar</label>
                    <div style="width: 82px">
                        <x-form.select-filter
                            name="perPage"
                            id="funciones-per-page"
                            :options="$perPageOptions"
                            :selected="(string) $perPage"
                            wire:model.live="perPage" />
                    </div>
                    <span class="small text-body-secondary d-none d-sm-block">por página</span>
                </div>
            </div>
        </div>

        @if ($this->funciones->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle admin-table">
                <thead>
                    <tr>
                        @php
                        $columnasOrdenables = [
                        'inicio' => 'Inicio',
                        'pelicula' => 'Película',
                        'sala' => 'Sala',
                        'formato' => 'Formato',
                        'precio_base' => 'Precio base',
                        'estado' => 'Estado',
                        ];
                        @endphp
                        @foreach ($columnasOrdenables as $campo => $etiqueta)
                        <th scope="col" aria-sort="{{ $sortField === $campo ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button"
                                class="btn btn-link p-0 text-decoration-none text-reset fw-semibold d-inline-flex align-items-center gap-1"
                                wire:click="ordenarPor('{{ $campo }}')"
                                wire:loading.attr="disabled"
                                wire:target="ordenarPor"
                                aria-label="Ordenar por {{ $etiqueta }}{{ $sortField === $campo ? ($sortDirection === 'asc' ? ', orden ascendente' : ', orden descendente') : '' }}">
                                <span>{{ $etiqueta }}</span>
                                <span class="material-symbols-outlined" style="font-size: 18px" aria-hidden="true">{{ $this->indicadorOrden($campo) }}</span>
                            </button>
                        </th>
                        @endforeach
                        <th class="text-end" scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->funciones as $funcion)
                    <tr wire:key="funcion-{{ $funcion->id_funcion }}">
                        <td class="text-nowrap">{{ $funcion->inicio?->format('d/m/Y H:i') }}</td>
                        <td class="fw-semibold">{{ $funcion->pelicula?->titulo ?? 'Película no disponible' }}</td>
                        <td>{{ $funcion->sala?->nombre ?? 'Sala no disponible' }}</td>
                        <td>{{ $funcion->formato }}</td>
                        <td>${{ number_format((float) $funcion->precio_base, 2, ',', '.') }}</td>
                        <td><span class="badge bg-{{ $funcion->estado === 'PROGRAMADA' ? 'primary' : ($funcion->estado === 'EN_CURSO' ? 'success' : ($funcion->estado === 'CANCELADA' ? 'danger' : 'secondary')) }}">{{ str_replace('_', ' ', $funcion->estado) }}</span></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.funciones.edit', $funcion->id_funcion) }}" class="btn admin-action-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar" aria-label="Editar función">
                                <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                            </a>
                            <form method="POST" action="{{ route('admin.funciones.destroy', $funcion->id_funcion) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta función? Si tiene asientos o entradas asociados, no podrá eliminarse.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn admin-action-icon admin-action-icon--danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar" aria-label="Eliminar función">
                                    <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.tabla.pie
            :paginator="$this->funciones"
            label="funciones"
            class="admin-table-footer p-3" />
        @else
        <div class="admin-table-empty">
            <h2 class="h5">{{ $q !== '' || $estado !== '' || $idSala !== '' || $fechaDesde !== '' || $fechaHasta !== '' ? 'No se encontraron funciones' : 'Todavía no hay funciones registradas' }}</h2>
            <p class="muted mb-3">{{ $q !== '' || $estado !== '' || $idSala !== '' || $fechaDesde !== '' || $fechaHasta !== '' ? 'Prueba cambiar los filtros o limpiarlos para ver más resultados.' : 'Registra una función seleccionando una película activa y una sala activa.' }}</p>
            @if ($q !== '' || $estado !== '' || $idSala !== '' || $fechaDesde !== '' || $fechaHasta !== '')
            <button type="button" class="btn btn-outline-secondary" wire:click="limpiarFiltros">Limpiar filtros</button>
            @else
            <a href="{{ route('admin.funciones.create') }}" class="btn btn-primary">Registrar primera función</a>
            @endif
        </div>
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
                if (active) {
                    item.setAttribute('aria-current', 'true');
                } else {
                    item.removeAttribute('aria-current');
                }
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