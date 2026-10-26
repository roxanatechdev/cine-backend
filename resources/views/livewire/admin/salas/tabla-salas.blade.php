<div>
    @php
    $estadoOptions = [
    '' => 'Todos los estados',
    'ACTIVA' => 'Activa',
    'INACTIVA' => 'Inactiva',
    'MANTENIMIENTO' => 'Mantenimiento',
    ];
    $perPageOptions = [
    '10' => '10 por página',
    '25' => '25 por página',
    '50' => '50 por página',
    ];
    $hayFiltros = trim($q) !== '' || $estado !== '';
    @endphp

    <x-admin.tabla.contenedor class="admin-table-card">
        <x-admin.tabla.filtros tag="div" class="admin-table-filters px-3 py-3 p-md-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label for="salas-busqueda" class="form-label">Buscar sala</label>
                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
                        <input
                            id="salas-busqueda"
                            type="search"
                            aria-label="Buscar salas por nombre"
                            maxlength="100"
                            class="form-control cm-field-filter ps-5"
                            wire:model.live.debounce.300ms="q"
                            placeholder="Buscar nombre..."
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label for="salas-estado_toggle" class="form-label">Estado</label>
                    <x-form.select-filter
                        name="estado"
                        id="salas-estado"
                        :options="$estadoOptions"
                        :selected="$estado"
                        placeholder="Todos los estados"
                        wire:model.live="estado" />
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="salas-per-page_toggle" class="form-label">Registros por página</label>
                    <x-form.select-filter
                        name="perPage"
                        id="salas-per-page"
                        :options="$perPageOptions"
                        :selected="(string) $perPage"
                        placeholder="10 por página"
                        wire:model.live="perPage" />
                </div>
            </div>
            @if ($hayFiltros)
            <div class="mt-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="limpiarFiltros"><span class="material-symbols-outlined align-middle" aria-hidden="true">filter_alt_off</span>Limpiar filtros</button>
            </div>
            @endif
        </x-admin.tabla.filtros>

        @if ($this->salas->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 admin-table">
                <thead>
                    <tr>
                        @foreach ([
                        'nombre' => 'Nombre',
                        'capacidad' => 'Capacidad informativa',
                        'margen_limpieza_minutos' => 'Margen de limpieza',
                        'estado' => 'Estado',
                        ] as $campo => $etiqueta)
                        <th scope="col" aria-sort="{{ $sortField === $campo ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button"
                                class="btn btn-link p-0 text-reset text-decoration-none fw-semibold d-inline-flex align-items-center gap-1"
                                wire:click="ordenarPor('{{ $campo }}')"
                                wire:loading.attr="disabled"
                                wire:target="ordenarPor"
                                aria-label="Ordenar por {{ $etiqueta }}{{ $sortField === $campo ? ($sortDirection === 'asc' ? ', orden ascendente' : ', orden descendente') : '' }}">
                                <span>{{ $etiqueta }}</span>
                                <span class="material-symbols-outlined" style="font-size: 18px" aria-hidden="true">{{ $this->indicadorOrden($campo) }}</span>
                            </button>
                        </th>
                        @endforeach
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->salas as $sala)
                    <tr wire:key="sala-{{ $sala->id_sala }}">
                        <td class="fw-semibold">{{ $sala->nombre }}</td>
                        <td>{{ $sala->capacidad === null ? 'No especificada' : $sala->capacidad }}</td>
                        <td>{{ $sala->margen_limpieza_minutos }} min</td>
                        <td>
                            @php
                            $estadoClase = match ($sala->estado) {
                            'ACTIVA' => 'success',
                            'INACTIVA' => 'secondary',
                            'MANTENIMIENTO' => 'warning text-dark',
                            default => 'secondary',
                            };
                            @endphp
                            <span class="badge bg-{{ $estadoClase }}">{{ $sala->estado }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.salas.edit', $sala->id_sala) }}" class="btn admin-action-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar" aria-label="Editar sala {{ $sala->nombre }}">
                                <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                            </a>
                            <form method="POST" action="{{ route('admin.salas.destroy', $sala->id_sala) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta sala? Si tiene asientos o funciones asociados, no podrá eliminarse.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn admin-action-icon admin-action-icon--danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar" aria-label="Eliminar sala {{ $sala->nombre }}">
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
            :paginator="$this->salas"
            label="salas"
            pagination-view="vendor.livewire.pagination-bootstrap"
            class="admin-table-footer p-3" />
        @else
        <div class="admin-table-empty">
            <h2 class="h5">{{ $hayFiltros ? 'No se encontraron salas' : 'Todavía no hay salas registradas' }}</h2>
            <p class="muted mb-3">{{ $hayFiltros ? 'Prueba cambiar los criterios de búsqueda o limpiar los filtros.' : 'Registra la primera sala para comenzar a administrar el catálogo.' }}</p>
            @if ($hayFiltros)
            <button type="button" class="btn btn-outline-secondary" wire:click="limpiarFiltros">Limpiar filtros</button>
            @else
            <a href="{{ route('admin.salas.create') }}" class="btn btn-primary">Registrar primera sala</a>
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