<div>
    @php
    $hayFiltros = trim($q) !== '' || $tipo !== '';
    @endphp

    <x-admin.tabla.contenedor class="admin-table-card rounded-3">
        <div class="p-3 p-md-3 border-bottom admin-table-filters">
            <div class="row g-2 align-items-end">

                <!-- 1. Buscar Cliente -->
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="compradores-buscar" class="form-label">Buscar Cliente</label>
                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
                        <input
                            id="compradores-buscar"
                            type="search"
                            aria-label="Buscar comprador"
                            maxlength="150"
                            class="form-control cm-field-filter ps-5"
                            wire:model.live.debounce.300ms="q"
                            placeholder="Nombre, correo, teléfono o ID…"
                            autocomplete="off">
                    </div>
                </div>

                <!-- 2. Tipo de Cliente -->
                <div class="col-12 col-sm-5 col-lg-3">
                    <label for="compradores-tipo" class="form-label">Tipo de Cliente</label>
                    <x-form.select-filter
                        name="tipo"
                        id="compradores-tipo"
                        :options="['' => 'Todos los tipos', 'INVITADO' => 'Invitado', 'REGISTRADO' => 'Registrado']"
                        :selected="$tipo"
                        placeholder="Todos los tipos"
                        wire:model.live="tipo"
                        aria-label="Filtrar por tipo de comprador" />
                </div>

                <!-- 3. Por página -->
                <div class="col-12 col-sm-5 col-lg-3">
                    <label for="compradores-per-page" class="form-label">Por página</label>
                    <x-form.select-filter
                        name="perPage"
                        id="compradores-per-page"
                        :options="[10 => '10 por página', 25 => '25 por página', 50 => '50 por página', 100 => '100 por página']"
                        :selected="(string) $perPage"
                        placeholder="Registros por página"
                        wire:model.live="perPage"
                        aria-label="Registros por página" />
                </div>

                <!-- 4. Botón Limpiar -->
                <div class="col-12 col-sm-2 col-lg-2">
                    <button type="button" class="btn btn-outline-secondary w-100 p-1" wire:click="limpiarFiltros">
                        <span class="material-symbols-outlined align-middle" aria-hidden="true">filter_alt_off</span>
                        <span class="d-inline d-sm-none d-lg-inline">Limpiar</span>
                    </button>
                </div>

            </div>
        </div>

        @if ($this->compradores->isEmpty())
        <div class="admin-table-empty">
            <h2 class="h5">No se encontraron compradores</h2>
            <p class="muted mb-3">Registra un comprador o modifica los criterios de búsqueda.</p>
            <a href="{{ route('admin.compradores.create') }}" class="btn btn-primary">Registrar comprador</a>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle admin-table admin-table--compradores">
                <colgroup>
                    <col class="comprador-col-nombre">
                    <col class="comprador-col-correo">
                    <col class="comprador-col-telefono">
                    <col class="comprador-col-tipo">
                    <col class="comprador-col-creado">
                    <col class="comprador-col-acciones">
                </colgroup>
                <thead>
                    <tr>
                        @foreach (['nombre' => 'Nombre', 'correo' => 'Correo', 'telefono' => 'Teléfono', 'tipo_comprador' => 'Tipo', 'creado_en' => 'Creado en'] as $columna => $etiqueta)
                        <th scope="col" aria-sort="{{ $sort === $columna ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" class="btn btn-link text-decoration-none text-reset p-0 fw-semibold"
                                wire:click="sortBy('{{ $columna }}')" aria-label="Ordenar por {{ $etiqueta }}">
                                {{ $etiqueta }} <span class="material-symbols-outlined align-middle" style="font-size:16px" aria-hidden="true">{{ $sort === $columna ? ($direction === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}</span>
                            </button>
                        </th>
                        @endforeach
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->compradores as $comprador)
                    <tr wire:key="comprador-{{ $comprador->id_comprador }}">
                        <td class="fw-semibold comprador-nombre">{{ $comprador->nombre }}</td>
                        <td class="comprador-correo">{{ $comprador->correo }}</td>
                        <td class="comprador-telefono">{{ $comprador->telefono ?: '—' }}</td>
                        <td><span class="badge bg-{{ $comprador->tipo_comprador === 'REGISTRADO' ? 'primary' : 'secondary' }}">{{ $comprador->tipo_comprador === 'REGISTRADO' ? 'Registrado' : 'Invitado' }}</span></td>
                        <td>{{ $comprador->creado_en?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.compradores.edit', $comprador->id_comprador) }}" class="btn admin-action-icon"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Editar" aria-label="Editar comprador {{ $comprador->nombre }}">
                                <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                            </a>
                            <form method="POST" action="{{ route('admin.compradores.destroy', $comprador->id_comprador) }}" class="d-inline"
                                onsubmit="return confirm('¿Eliminar este comprador? Si tiene compras asociadas, no podrá eliminarse.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn admin-action-icon admin-action-icon--danger" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Eliminar" aria-label="Eliminar comprador {{ $comprador->nombre }}">
                                    <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.tabla.pie :paginator="$this->compradores" label="compradores" class="admin-table-footer p-3" />
        @endif
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
    </x-admin.tabla.contenedor>

</div>