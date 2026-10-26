<div>
    @php
    $perPageOptions = [
    '10' => '10',
    '25' => '25',
    '50' => '50',
    ];
    $hayFiltros = trim($q) !== '';
    $columnasOrdenables = [
    'codigo' => 'Código ISO',
    'nombre' => 'Nombre',
    'peliculas_count' => 'Películas asociadas',
    ];
    @endphp

    <x-admin.tabla.contenedor class="admin-table-card">

        <div class="p-3 p-md-4 border-bottom admin-table-filters">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-9">
                    <label for="paises-busqueda" class="form-label">Buscar país</label>
                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
                        <input
                            id="paises-busqueda"
                            type="search"
                            aria-label="Buscar paises por título"
                            maxlength="100"
                            class="form-control cm-field-filter ps-5"
                            wire:model.live.debounce.300ms="q"
                            placeholder="Buscar por ISO o nombre…"
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="paises-per-page_toggle" class="form-label">Por página</label>
                    <x-form.select-filter name="perPage" id="paises-per-page" :options="$perPageOptions" :selected="(string) $perPage"
                        wire:model.live="perPage" />
                </div>
            </div>
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mt-3">
                <div>
                    @if ($hayFiltros)
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="limpiarFiltros">
                        <span class="material-symbols-outlined align-middle" aria-hidden="true">filter_alt_off</span>
                        Limpiar filtros
                    </button>
                    @endif
                </div>
            </div>
        </div>

        @if ($this->paises->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 admin-table">
                <thead>
                    <tr>
                        @foreach ($columnasOrdenables as $campo => $etiqueta)
                        <th scope="col" aria-sort="{{ $sortField === $campo ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" class="btn btn-link p-0 text-decoration-none text-reset fw-semibold d-inline-flex align-items-center gap-1"
                                wire:click="ordenarPor('{{ $campo }}')" wire:loading.attr="disabled" wire:target="ordenarPor"
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
                    @foreach ($this->paises as $pais)
                    <tr wire:key="pais-{{ $pais->codigo }}">
                        <td class="text-nowrap"><code>{{ $pais->codigo }}</code></td>
                        <td class="fw-semibold">{{ $pais->nombre }}</td>
                        <td>{{ $pais->peliculas_count }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.paises.edit', $pais->codigo) }}" class="btn admin-action-icon"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Editar"
                                aria-label="Editar país {{ $pais->nombre }}">
                                <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.tabla.pie :paginator="$this->paises" label="países" class="admin-table-footer p-3" />
        @else
        <div class="admin-table-empty">
            <span class="material-symbols-outlined mb-2" aria-hidden="true" style="font-size: 2rem">{{ $hayFiltros ? 'search_off' : 'public' }}</span>
            <h2 class="h5">{{ $hayFiltros ? 'No se encontraron países' : 'Todavía no hay países registrados' }}</h2>
            <p class="muted mb-3">{{ $hayFiltros ? 'Prueba cambiar los criterios de búsqueda o limpiar los filtros.' : 'Registra el primer país para comenzar a asociar su procedencia a las películas.' }}</p>
            @if ($hayFiltros)
            <button type="button" class="btn btn-outline-secondary" wire:click="limpiarFiltros">Limpiar filtros</button>
            @else
            <a href="{{ route('admin.paises.create') }}" class="btn btn-primary">Registrar primer país</a>
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