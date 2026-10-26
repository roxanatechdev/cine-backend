<div>
    @php
    $perPageOptions = ['10' => '10', '25' => '25', '50' => '50', '100' => '100'];
    @endphp
    <x-admin.tabla.contenedor class="admin-table-card rounded-3">
        <x-admin.tabla.filtros class="admin-table-filters p-3 p-md-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-7">
                    <label for="personas-buscar" class="form-label">Buscar persona</label>
                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
                        <input
                            id="personas-buscar"
                            type="search"
                            aria-label="Buscar personas por nombre"
                            maxlength="100"
                            class="form-control cm-field-filter ps-5"
                            wire:model.live.debounce.300ms="q"
                            placeholder="Nombre completo o nombre de búsqueda"
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label for="personas-per-page" class="form-label">Por página</label>
                    <x-form.select-filter name="perPage" id="personas-per-page" :options="$perPageOptions" :selected="(string) $perPage"
                        wire:model.live="perPage" />
                </div>
                <div class="col-6 col-md-3 text-md-end">
                    <span class="muted small" aria-live="polite">{{ $this->personas->total() }} resultado(s)</span>
                    <button type="button" class="btn btn-outline-light btn-sm ms-2"
                        wire:click="$set('q', '')" wire:loading.attr="disabled" title="Limpiar búsqueda">
                        <span class="material-symbols-outlined align-middle" aria-hidden="true">filter_alt_off</span>Limpiar
                    </button>
                </div>
            </div>
            <div wire:loading.delay class="small muted mt-2" role="status">Actualizando personas…</div>
        </x-admin.tabla.filtros>

        @if ($this->personas->isEmpty())
        <div class="admin-table-empty">
            <h2 class="h5">No se encontraron personas</h2>
            <p class="muted mb-0">Registra una persona o modifica los términos de búsqueda.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle admin-table">
                <thead>
                    <tr>
                        <th scope="col">Foto</th>
                        @foreach ([ 'nombre_completo' => 'Nombre completo', 'nombre_busqueda' => 'Nombre de búsqueda', 'id_persona' => 'ID' ] as $columna => $etiqueta)
                        <th scope="col" aria-sort="{{ $sort === $columna ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" class="btn btn-link text-decoration-none text-reset p-0 fw-semibold"
                                wire:click="sortBy('{{ $columna }}')" aria-label="Ordenar por {{ $etiqueta }}">
                                {{ $etiqueta }}
                                <span class="material-symbols-outlined align-middle" style="font-size:16px" aria-hidden="true">{{ $sort === $columna ? ($direction === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}</span>
                            </button>
                        </th>
                        @endforeach
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->personas as $persona)
                    <tr wire:key="persona-{{ $persona->id_persona }}">
                        <td>
                            @php($fotoUrl = $this->urlFoto($persona->foto))
                            <span class="position-relative d-inline-flex align-items-center justify-content-center rounded bg-body-secondary text-secondary overflow-hidden"
                                style="width:52px;height:64px;flex-shrink:0" aria-label="{{ $fotoUrl ? 'Cargando foto de ' . $persona->nombre_completo : 'Sin foto' }}">
                                <span class="material-symbols-outlined persona-foto-placeholder" aria-hidden="true">{{ $fotoUrl ? 'account_circle' : 'person' }}</span>
                                @if ($fotoUrl)
                                <img src="{{ $fotoUrl }}" alt="Foto de {{ $persona->nombre_completo }}"
                                    loading="lazy" decoding="async" width="52" height="64"
                                    class="position-absolute top-0 start-0 w-100 h-100"
                                    style="object-fit:cover;opacity:0;transition:opacity .12s ease"
                                    onload="this.style.opacity='1';this.previousElementSibling.style.display='none'"
                                    onerror="this.removeAttribute('src');this.style.display='none'">
                                @endif
                            </span>
                        </td>
                        <td>{{ $persona->nombre_completo }}</td>
                        <td>{{ $persona->nombre_busqueda ?: '—' }}</td>
                        <td>{{ $persona->id_persona }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.personas.edit', $persona) }}"
                                class="admin-action-icon" aria-label="Editar {{ $persona->nombre_completo }}" title="Editar persona">
                                <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.tabla.pie
            :paginator="$this->personas"
            label="personas"
            class="admin-table-footer p-3" />
        @endif
    </x-admin.tabla.contenedor>
</div>