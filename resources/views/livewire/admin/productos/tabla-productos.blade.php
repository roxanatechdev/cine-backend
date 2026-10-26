<div>
    @php
    $estadoOptions = ['' => 'Todos los estados', 'ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo', 'AGOTADO' => 'Agotado'];
    $categoriaOptions = ['' => 'Todas las categorías'];
    foreach ($this->categoriasFiltro as $categoriaItem) {
    $categoriaOptions[(string) $categoriaItem->id_categoria_producto] = $categoriaItem->nombre;
    }
    $perPageOptions = ['10' => '10', '25' => '25', '50' => '50', '100' => '100'];
    $hayFiltros = trim($q) !== '' || $estado !== '' || $categoria !== '';
    $columnasOrdenables = [
    'id_producto' => 'ID',
    'nombre' => 'Producto',
    'categoria' => 'Categoría',
    'precio_actual' => 'Precio',
    'stock' => 'Stock',
    'estado' => 'Estado',
    'es_promocion' => 'Promoción',
    ];
    $estadosProducto = ['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo', 'AGOTADO' => 'Agotado'];
    @endphp

    @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show mx-3 mt-3 mb-0" role="status" aria-live="polite">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    @endif

    <x-admin.tabla.contenedor class="admin-table-card">
        <div class="p-3 p-md-4 border-bottom admin-table-filters">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="productos-busqueda" class="form-label">Buscar producto</label>
                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
                        <input
                            id="productos-busqueda"
                            type="search"
                            aria-label="Buscar películas por título"
                            maxlength="100"
                            class="form-control cm-field-filter ps-5"
                            wire:model.live.debounce.300ms="q"
                            placeholder="Buscar por ID, nombre o descripción…"
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="productos-categoria" class="form-label">Categoría</label>
                    <x-form.select-filter name="categoria" id="productos-categoria" :options="$categoriaOptions" :selected="$categoria"
                        placeholder="Todas las categorías" wire:model.live="categoria" class="form_filter" />
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="productos-estado" class="form-label">Estado</label>
                    <x-form.select-filter name="estado" id="productos-estado" :options="$estadoOptions" :selected="$estado"
                        placeholder="Todos los estados" wire:model.live="estado" />
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="productos-per-page" class="form-label">Por página</label>
                    <x-form.select-filter name="perPage" id="productos-per-page" :options="$perPageOptions" :selected="(string) $perPage"
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

        @if ($this->productos->count())
        <div class="table-responsive productos-table-responsive">
            <table class="table table-hover align-middle mb-0 admin-table productos-admin-table">
                <colgroup>
                    <col class="col-producto-id">
                    <col class="col-producto-nombre">
                    <col class="col-producto-categoria">
                    <col class="col-producto-precio">
                    <col class="col-producto-stock">
                    <col class="col-producto-estado">
                    <col class="col-producto-promocion">
                    <col class="col-producto-acciones">
                </colgroup>
                <thead>
                    <tr>
                        @foreach ($columnasOrdenables as $campo => $etiqueta)
                        <th scope="col" aria-sort="{{ $sortField === $campo ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" class="btn btn-link p-0 text-decoration-none text-reset fw-semibold d-inline-flex align-items-center gap-1"
                                wire:click="ordenarPor('{{ $campo }}')" wire:loading.attr="disabled" wire:target="ordenarPor"
                                aria-label="Ordenar por {{ $etiqueta }}{{ $sortField === $campo ? ($sortDirection === 'asc' ? ', orden ascendente' : ', orden descendente') : '' }}">
                                <span>{{ $etiqueta }}</span>
                                <span class="material-symbols-outlined" style="font-size:18px" aria-hidden="true">{{ $this->indicadorOrden($campo) }}</span>
                            </button>
                        </th>
                        @endforeach
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->productos as $producto)
                    <tr wire:key="producto-{{ $producto->id_producto }}">
                        <td class="text-nowrap">{{ $producto->id_producto }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="producto-imagen-marco" aria-label="{{ $producto->imagen ? 'Imagen del producto' : 'Sin imagen' }}">
                                    <span class="producto-imagen-placeholder" aria-hidden="true">
                                        <span class="material-symbols-outlined">{{ $producto->imagen ? 'image' : 'inventory_2' }}</span>
                                    </span>
                                    @if ($producto->imagen)
                                    @php
                                    // Validamos si es una URL completa externa o una ruta interna del storage
                                    $imagenUrl = str_starts_with($producto->imagen, 'http://') || str_starts_with($producto->imagen, 'https://')
                                    ? $producto->imagen
                                    : (str_starts_with($producto->imagen, 'storage/') ? asset($producto->imagen) : asset('storage/' . $producto->imagen));
                                    @endphp

                                    <img src="{{ $imagenUrl }}" alt="Imagen de {{ $producto->nombre }}"
                                        class="producto-imagen-real" loading="lazy" decoding="async" width="44" height="44"
                                        onload="this.style.opacity='1';this.previousElementSibling.style.visibility='hidden'"
                                        onerror="this.onerror=null;this.remove()">
                                    @endif
                                </div>
                                <div class="fw-semibold">{{ $producto->nombre }}
                                    <div class="small muted">@if($producto->tamano){{ $producto->tamano }}@else Sin variante especificada @endif</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</td>
                        <td class="text-nowrap">
                            @if ($producto->es_promocion && $producto->precio_promocional)
                            <div class="small precio-original">{{ number_format((float) $producto->precio_actual, 2) }}</div>
                            <div class="precio-oferta">{{ number_format((float) $producto->precio_promocional, 2) }}</div>
                            @else
                            {{ number_format((float) $producto->precio_actual, 2) }}
                            @endif
                        </td>
                        <td>{{ is_null($producto->stock) ? 'No controlado' : $producto->stock }}</td>
                        <td><span class="badge {{ $producto->estado === 'ACTIVO' ? 'text-bg-success' : ($producto->estado === 'AGOTADO' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ $estadosProducto[$producto->estado] ?? $producto->estado }}</span></td>
                        <td>{{ $producto->es_promocion ? 'Sí' : 'No' }}</td>
                        <td class="text-end">
                            <div class="producto-acciones">
                                <a href="{{ route('admin.productos.edit', $producto->id_producto) }}" class="btn admin-action-icon"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Editar"
                                    aria-label="Editar producto {{ $producto->nombre }}">
                                    <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                                </a>
                                <div class="producto-estado-control">
                                    <label class="visually-hidden" for="producto-estado-{{ $producto->id_producto }}">Estado de {{ $producto->nombre }}</label>

                                    <select id="producto-estado-{{ $producto->id_producto }}" class="form-select form-select-sm producto-estado-select"
                                        wire:model.live="estadosSeleccionados.{{ $producto->id_producto }}" wire:loading.attr="disabled" wire:target="guardarEstado">
                                        @foreach ($estadosProducto as $valor => $etiqueta)
                                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>

                                    @if (($estadosSeleccionados[$producto->id_producto] ?? $producto->estado) !== $producto->estado)
                                    <button type="button" class="btn btn-outline-info btn-sm producto-guardar-estado"
                                        wire:click="guardarEstado({{ $producto->id_producto }})"
                                        wire:loading.attr="disabled" wire:target="guardarEstado({{ $producto->id_producto }})"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="Guardar nuevo estado"
                                        aria-label="Guardar nuevo estado de {{ $producto->nombre }}">
                                        <span class="material-symbols-outlined" aria-hidden="true">save</span>
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.tabla.pie :paginator="$this->productos" label="productos" class="admin-table-footer p-3" />
        @else
        <div class="admin-table-empty">
            <span class="material-symbols-outlined mb-2" aria-hidden="true" style="font-size:2rem">{{ $hayFiltros ? 'search_off' : 'inventory_2' }}</span>
            <h2 class="h5">{{ $hayFiltros ? 'No se encontraron productos' : 'Todavía no hay productos registrados' }}</h2>
            <p class="muted mb-3">{{ $hayFiltros ? 'Prueba cambiar los criterios de búsqueda o limpiar los filtros.' : 'Registra el primer producto para comenzar el catálogo de confitería.' }}</p>
            @if ($hayFiltros)
            <button type="button" class="btn btn-outline-secondary" wire:click="limpiarFiltros">Limpiar filtros</button>
            @else
            <a href="{{ route('admin.productos.create') }}" class="btn btn-primary">Registrar primer producto</a>
            @endif
        </div>
        @endif
    </x-admin.tabla.contenedor>

    <style>
        .productos-table-responsive {
            max-width: 100%;
        }

        .productos-admin-table {
            width: 100%;
            min-width: 1040px;
            table-layout: fixed;
        }

        .productos-admin-table .col-producto-id {
            width: 4%;
        }

        .productos-admin-table .col-producto-nombre {
            width: 30%;
        }

        .productos-admin-table .col-producto-categoria {
            width: 12%;
        }

        .productos-admin-table .col-producto-precio {
            width: 8%;
        }

        .productos-admin-table .col-producto-stock {
            width: 10%;
        }

        .productos-admin-table .col-producto-estado {
            width: 10%;
        }

        .productos-admin-table .col-producto-promocion {
            width: 8%;
        }

        .productos-admin-table .col-producto-acciones {
            width: 188px;
        }

        .productos-admin-table th,
        .productos-admin-table td {
            overflow-wrap: anywhere;
        }

        .productos-admin-table td:nth-child(1),
        .productos-admin-table td:nth-child(4),
        .productos-admin-table td:nth-child(5),
        .productos-admin-table td:nth-child(7) {
            white-space: nowrap;
        }

        .productos-admin-table td:nth-child(2) {
            overflow-wrap: normal;
        }

        .producto-imagen-marco {
            position: relative;
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            overflow: hidden;
            border-radius: 8px;
            background: var(--bs-tertiary-bg, #282933);
        }

        .producto-imagen-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--bs-secondary-color, #aaa);
        }

        .producto-imagen-real {
            position: absolute;
            inset: 0;
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity .12s ease-in;
        }

        .producto-acciones {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-end;
            gap: .35rem;
            min-width: 0;
        }

        .producto-estado-control {
            display: flex;
            align-items: center;
            gap: .3rem;
            min-width: 0;
            flex: 1 1 auto;
        }

        .producto-estado-select {
            min-width: 0;
            width: 100%;
            padding: .25rem 1.5rem .25rem .5rem;
            font-size: .875rem;
        }

        .producto-guardar-estado {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 34px;
            width: 34px;
            height: 31px;
            padding: .2rem;
        }

        .producto-guardar-estado .material-symbols-outlined {
            font-size: 18px;
        }

        .producto-guardar-estado:disabled {
            opacity: .45;
        }

        .productos-admin-table td:last-child {
            overflow-wrap: normal;
        }

        .productos-admin-table td:last-child .admin-action-icon {
            align-self: flex-end;
        }

        .producto-acciones>.admin-action-icon {
            flex: 0 0 30px;
            width: 30px;
            padding: .25rem;
        }

        .producto-acciones>.admin-action-icon .material-symbols-outlined {
            font-size: 19px;
        }

        .producto-guardar-estado {
            flex: 0 0 30px;
            width: 30px;
            height: 30px;
        }

        @media (max-width: 1199.98px) {
            .productos-admin-table {
                min-width: 1040px;
            }
        }
    </style>
</div>