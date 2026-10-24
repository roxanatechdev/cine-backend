<x-admin.tabla.contenedor class="custom-card rounded-3">

    @if (session()->has('success'))
    <div
        class="alert alert-success d-flex align-items-center gap-2 mb-3"
        role="status"
        aria-live="polite">
        <span class="material-symbols-outlined" aria-hidden="true">
            check_circle
        </span>

        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if (session()->has('error'))
    <div
        class="alert alert-danger d-flex align-items-center gap-2 mb-3"
        role="alert">
        <span class="material-symbols-outlined" aria-hidden="true">
            error
        </span>

        <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Búsqueda y Filtros Avanzados (Minimalista & Responsive) --}}
    <x-admin.tabla.filtros class="p-3 p-md-4" aria-labelledby="filtros-titulo">

        {{-- Encabezado: Título + Enlace Limpiar --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined text-danger fs-5" aria-hidden="true">tune</span>
                <h2 id="filtros-titulo" class="h6 text-white mb-0 fw-bold text-uppercase tracking-wider">
                    Catálogo de películas
                </h2>
            </div>

            {{-- Botón / Enlace Limpiar --}}
            <button
                type="button"
                class="btn btn-link text-cm-variant text-decoration-none p-0 d-inline-flex align-items-center gap-1 fs-7 fw-semibold cm-clear-link"
                wire:click="limpiarFiltros"
                wire:loading.attr="disabled"
                wire:target="limpiarFiltros"
                title="Limpiar filtros">
                <span class="material-symbols-outlined fs-6" wire:loading.remove wire:target="limpiarFiltros">restart_alt</span>
                <span class="spinner-border spinner-border-sm text-danger" role="status" aria-hidden="true" wire:loading wire:target="limpiarFiltros"></span>
                <span>Limpiar filtros</span>
            </button>
        </div>

        {{-- Formulario de Filtros --}}
        <div>
            <div class="row g-2 g-xxl-3 align-items-center">

                {{-- 1. Buscador Principal --}}
                <div class="col-12 col-md-5 col-lg-5">
                    <div class="cm-input-group position-relative">
                        <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
                        <input
                            id="q"
                            type="search"
                            aria-label="Buscar películas por título"
                            maxlength="150"
                            class="form-control cm-field ps-5"
                            wire:model.live.debounce.300ms="q"
                            placeholder="Ej. El Reino de Cristal..."
                            autocomplete="off">
                    </div>
                </div>

                {{-- 2. Estado (Custom Dropdown) --}}
                <div class="col-6 col-md-3 col-lg-3">
                    <div class="dropdown">
                        <button
                            class="btn cm-field btn-cm-dropdown w-100 text-start d-flex justify-content-between align-items-center"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <span class="text-truncate">
                                @switch($estado)
                                @case('BORRADOR') Borradores @break
                                @case('ACTIVA') Activas @break
                                @case('INACTIVA') Inactivas @break
                                @default Todos los estados
                                @endswitch
                            </span>
                            <span class="material-symbols-outlined dropdown-icon-arrow fs-6 text-cm-variant ms-1">expand_more</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark cm-dropdown-menu w-100 shadow-lg">
                            <li>
                                <button type="button" class="dropdown-item @if($estado === '') active @endif" wire:click="$set('estado', '')">
                                    Todos los estados
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item @if($estado === 'BORRADOR') active @endif" wire:click="$set('estado', 'BORRADOR')">
                                    Borradores
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item @if($estado === 'ACTIVA') active @endif" wire:click="$set('estado', 'ACTIVA')">
                                    Activas
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item @if($estado === 'INACTIVA') active @endif" wire:click="$set('estado', 'INACTIVA')">
                                    Inactivas
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- 3. Clasificación (Custom Dropdown) --}}
                <div class="col-6 col-md-2 col-lg-2">
                    <div class="dropdown">
                        <button
                            class="btn cm-field btn-cm-dropdown w-100 text-start d-flex justify-content-between align-items-center"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <span class="text-truncate">{{ $clasificacion ? $clasificacion : 'Clasificación' }}</span>
                            <span class="material-symbols-outlined dropdown-icon-arrow fs-6 text-cm-variant ms-1">expand_more</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark cm-dropdown-menu w-100 shadow-lg">
                            <li>
                                <button type="button" class="dropdown-item @if($clasificacion === '') active @endif" wire:click="$set('clasificacion', '')">
                                    Todas
                                </button>
                            </li>
                            @foreach (['G', 'PG', 'PG-13', 'R', 'NC-17'] as $opcion)
                            <li>
                                <button type="button" class="dropdown-item @if($clasificacion === $opcion) active @endif" wire:click="$set('clasificacion', '{{ $opcion }}')">
                                    {{ $opcion }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- 4. Selector de Filas por Página --}}
                <div class="col-12 col-md-2 col-lg-2">
                    <div class="d-flex align-items-center gap-1 justify-content-start justify-content-md-end w-100" id="row-pages">
                        <span class="text-secondary small text-nowrap">Filas</span>
                        <div class="dropdown">
                            <button class="btn bg-none btn-sm border border-opacity-25 dropdown-toggle text-white d-flex align-items-center justify-content-between px-3"
                                type="button"
                                id="perPageDropdownBottom"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                style="min-width: 70px;">
                                {{ $perPage ?? 10 }}
                            </button>

                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg border-0" aria-labelledby="perPageDropdownBottom" style="background-color: var(--cm-surface-container-high, #282933);">
                                @foreach ([10, 25, 50, 100] as $cantidad)
                                <li>
                                    <button type="button"
                                        class="dropdown-item d-flex align-items-center justify-content-between {{ ($perPage ?? 10) == $cantidad ? 'bg-danger text-white' : 'text-white' }}"
                                        wire:click="$set('perPage', {{ $cantidad }})">
                                        <span>{{ $cantidad }}</span>
                                        @if (($perPage ?? 10) == $cantidad)
                                        <span class="material-symbols-outlined fs-6">check</span>
                                        @endif
                                    </button>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </x-admin.tabla.filtros>

    <section class="custom-card table-custom table-custom-2 pb-3 pb-lg-4 rounded-3"
        aria-labelledby="catalogo-peliculas" id="tabla-peliculas">

        <div class="table-responsive" id="catalogo-peliculas">
            <table class="table table-dark table-hover align-middle table-responsive-wrapper">
                <caption class="visually-hidden">Catálogo de películas con opciones de ordenamiento y acciones administrativas</caption>

                <thead>
                    <tr class="border-bottom border-secondary border-opacity-25 text-secondary small text-uppercase">
                        <th scope="col">
                            <button type="button" aria-label="Ordenar por ID" wire:click="sortBy('id_pelicula')" class="btn btn-link text-reset text-decoration-none text-nowrap p-0 fw-semibold d-inline-flex align-items-center gap-1 fs-6-5">
                                ID @include('admin.peliculas.partials.sort-icon', ['campo' => 'id_pelicula', 'currentSort' => $sort, 'currentDirection' => $direction])
                            </button>
                        </th>
                        <th scope="col">
                            <button type="button" aria-label="Ordenar por película" wire:click="sortBy('titulo')" class="btn btn-link text-reset text-decoration-none text-nowrap p-0 fw-semibold d-inline-flex align-items-center gap-1 fs-6-5">
                                Película @include('admin.peliculas.partials.sort-icon', ['campo' => 'titulo', 'currentSort' => $sort, 'currentDirection' => $direction])
                            </button>
                        </th>
                        <th scope="col">
                            <button type="button" aria-label="Ordenar por género" wire:click="sortBy('genero')" class="btn btn-link text-reset text-decoration-none text-nowrap p-0 fw-semibold d-inline-flex align-items-center gap-1 fs-6-5">
                                Género @include('admin.peliculas.partials.sort-icon', ['campo' => 'genero', 'currentSort' => $sort, 'currentDirection' => $direction])
                            </button>
                        </th>
                        <th scope="col">
                            <button type="button" aria-label="Ordenar por clasificación" wire:click="sortBy('clasificacion')" class="btn btn-link text-reset text-decoration-none text-nowrap p-0 fw-semibold d-inline-flex align-items-center gap-1 fs-6-5">
                                Clasificación @include('admin.peliculas.partials.sort-icon', ['campo' => 'clasificacion', 'currentSort' => $sort, 'currentDirection' => $direction])
                            </button>
                        </th>
                        <th scope="col">
                            <button type="button" aria-label="Ordenar por fecha de estreno" wire:click="sortBy('fecha_estreno')" class="btn btn-link text-reset text-decoration-none text-nowrap p-0 fw-semibold d-inline-flex align-items-center gap-1 fs-6-5">
                                Estreno @include('admin.peliculas.partials.sort-icon', ['campo' => 'fecha_estreno', 'currentSort' => $sort, 'currentDirection' => $direction])
                            </button>
                        </th>
                        <th scope="col">
                            <button type="button" aria-label="Ordenar por estado" wire:click="sortBy('estado')" class="btn btn-link text-reset text-decoration-none text-nowrap p-0 fw-semibold d-inline-flex align-items-center gap-1 fs-6-5">
                                Estado @include('admin.peliculas.partials.sort-icon', ['campo' => 'estado', 'currentSort' => $sort, 'currentDirection' => $direction])
                            </button>
                        </th>
                        <th scope="col" class="text-end fw-semibold fs-6-5">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($this->peliculas as $pelicula)
                    <tr wire:key="pelicula-{{ $pelicula->id_pelicula }}">
                        <td>
                            <span class="small color-id">
                                #{{ $pelicula->id_pelicula }}
                            </span>
                        </td>

                        <td class="celda-pelicula">
                            @php
                            // Preferir un póster activo; si no existe, usar una imagen activa.
                            $multimediaCatalogo = $pelicula->multimedia->first();
                            $imagenCatalogo = null;

                            if ($multimediaCatalogo && filled($multimediaCatalogo->url)) {
                            $urlCatalogo = trim($multimediaCatalogo->url);

                            if (filter_var($urlCatalogo, FILTER_VALIDATE_URL)) {
                            // Recurso externo: conservar su URL.
                            $imagenCatalogo = $urlCatalogo;
                            } else {
                            // Recurso local: construir la URL con el host actual.
                            $rutaCatalogo = ltrim($urlCatalogo, '/');
                            $imagenCatalogo = str_starts_with($rutaCatalogo, 'storage/')
                            ? asset($rutaCatalogo)
                            : asset('storage/' . $rutaCatalogo);
                            }
                            }
                            @endphp<div class="pelicula-identidad">
                                <div class="poster-catalogo">
                                    <div class="poster-placeholder" aria-hidden="true">
                                        <span class="material-symbols-outlined">movie</span>
                                    </div>

                                    @if ($imagenCatalogo)
                                    <img src="{{ $imagenCatalogo }}" alt="Póster de {{ $pelicula->titulo }}"
                                        class="poster-imagen"
                                        loading="lazy"
                                        decoding="async"
                                        onerror=" this.onerror = null; this.remove(); this.parentElement.classList.add('poster-fallido');">
                                    @endif
                                </div>

                                <div class="pelicula-detalles">
                                    <div class="pelicula-titulo multilinea">
                                        {{ $pelicula->titulo }}
                                    </div>

                                    <div class="pelicula-duracion">
                                        {{ $pelicula->duracion_minutos ?? '—' }} min
                                    </div>
                                </div>
                            </div>
                        </td>


                        <td>
                            {{ $pelicula->generoPrincipal?->nombre ?? '—' }}
                        </td>

                        <td>
                            @if ($pelicula->clasificacion)
                            <span class="badge text-bg-dark border border-variant">
                                {{ $pelicula->clasificacion }}
                            </span>
                            @else
                            <span class="muted">—</span>
                            @endif
                        </td>

                        <td>
                            {{ $pelicula->fecha_estreno?->format('d/m/Y') ?? '—' }}
                        </td>

                        <td>

                            @if ($pelicula->estado === 'ACTIVA')
                            <span class="badge text-bg-success">Activa</span>
                            @elseif ($pelicula->estado === 'BORRADOR')
                            <span class="badge text-bg-warning">Borrador</span>
                            @else
                            <span class="badge text-bg-secondary">Inactiva</span>
                            @endif

                        </td>

                        <td class="text-end align-middle">
                            <div class="d-flex align-items-center justify-content-end gap-1">

                                {{-- 1. Acción rápida: Editar información general --}}
                                <a href="{{ route('admin.peliculas.edit', $pelicula) }}"
                                    class="btn btn-sm  p-1 px-2 d-inline-flex align-items-center"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Editar información general">
                                    <span class="material-symbols-outlined fs-5">edit</span>
                                </a>

                                {{-- 2. Menú Desplegable (3 puntos) para subsecciones y estado --}}
                                <div class="dropdown">
                                    <button class="btn btn-sm p-1 px-2 d-inline-flex align-items-center"
                                        type="button"
                                        id="actionDropdown{{ $pelicula->id_pelicula }}"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                        title="Más acciones">
                                        <span class="material-symbols-outlined fs-5">more_vert</span>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg border-0 py-2"
                                        aria-labelledby="actionDropdown{{ $pelicula->id_pelicula }}"
                                        style="background-color: var(--cm-surface-container-high, #282933); min-width: 210px;">

                                        {{-- Gestión de Créditos --}}
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-white"
                                                href="{{ route('admin.peliculas.creditos.index', $pelicula) }}">
                                                <span class="material-symbols-outlined fs-5 text-secondary">group</span>
                                                <span>Créditos y reparto</span>
                                            </a>
                                        </li>

                                        {{-- Países de Producción --}}
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-white"
                                                href="{{ route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'produccion']) }}">
                                                <span class="material-symbols-outlined fs-5 text-secondary">public</span>
                                                <span>Países de producción</span>
                                            </a>
                                        </li>

                                        {{-- Multimedia --}}
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-white"
                                                href="{{ route('admin.peliculas.multimedia.index', $pelicula) }}">
                                                <span class="material-symbols-outlined fs-5 text-secondary">perm_media</span>
                                                <span>Recursos multimedia</span>
                                            </a>
                                        </li>

                                        <li>
                                            <hr class="dropdown-divider border-secondary border-opacity-25 my-1">
                                        </li>

                                        {{-- Acciones según el estado actual de la película --}}
                                        @if ($pelicula->estado === 'ACTIVA')
                                        <li>
                                            <button
                                                type="button"
                                                class="dropdown-item d-flex align-items-center gap-2 py-2 text-warning"
                                                wire:click="desactivarPelicula({{ $pelicula->id_pelicula }})"
                                                wire:loading.attr="disabled"
                                                wire:target="desactivarPelicula({{ $pelicula->id_pelicula }})"
                                                wire:confirm="¿Seguro que deseas desactivar la película «{{ $pelicula->titulo }}»?">
                                                <span class="material-symbols-outlined fs-5">
                                                    block
                                                </span>
                                                <span>Desactivar película</span>
                                            </button>
                                        </li>
                                        @elseif (in_array($pelicula->estado, ['BORRADOR', 'INACTIVA'], true))
                                        <li>
                                            <button
                                                type="button"
                                                class="dropdown-item d-flex align-items-center gap-2 py-2 text-success"
                                                wire:click="activarPelicula({{ $pelicula->id_pelicula }})"
                                                wire:loading.attr="disabled"
                                                wire:target="activarPelicula({{ $pelicula->id_pelicula }})"
                                                wire:confirm="¿Deseas activar la película «{{ $pelicula->titulo }}»? Se comprobarán los requisitos de publicación.">
                                                <span class="material-symbols-outlined fs-5">
                                                    {{ $pelicula->estado === 'BORRADOR' ? 'publish' : 'check_circle' }}
                                                </span>
                                                <span>
                                                    {{ $pelicula->estado === 'BORRADOR' ? 'Publicar película' : 'Reactivar película' }}
                                                </span>
                                            </button>
                                        </li>
                                        @endif
                                    </ul>
                                </div>

                            </div>
                        </td>
                    </tr>
                    @empty
                    <x-admin.tabla.vacio colspan="7" class="text-center py-5">
                        <div class="mb-3 text-info"
                            aria-hidden="true">
                            🎬
                        </div>

                        <h3 class="h6 fw-semibold mb-2">
                            No se encontraron películas
                        </h3>

                        <p class="muted mb-3">
                            Prueba con otro título o modifica los filtros.
                        </p>

                        <button
                            type="button"
                            class="btn btn-outline-light d-inline-flex align-items-center justify-content-center gap-2"
                            wire:click="limpiarFiltros"
                            wire:loading.attr="disabled"
                            wire:target="limpiarFiltros">

                            <!-- Icono de carga que aparece solo cuando está cargando -->
                            <span wire:loading wire:target="limpiarFiltros" class="spinner-border spinner-border-sm" aria-hidden="true"></span>

                            <!-- Texto dinámico: Cambia automáticamente gracias a wire:loading.remove -->
                            <span wire:loading.remove wire:target="limpiarFiltros">Limpiar filtros</span>
                            <span wire:loading wire:target="limpiarFiltros">Limpiando...</span>
                        </button>
                    </x-admin.tabla.vacio>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Resumen y navegación de páginas --}}
        @if ($this->peliculas->total() > 0)
        <div class="footer-table d-flex flex-column flex-md-row
                justify-content-between align-items-center
                gap-3 pt-3 border-top border-secondary border-opacity-50 px-3">

            <!-- 1. Contador de resultados -->
            <div class="text-secondary small">
                Mostrando
                <strong class="text-white">{{ $this->peliculas->firstItem() ?? 0 }}</strong>
                a
                <strong class="text-white">{{ $this->peliculas->lastItem() ?? 0 }}</strong>
                de
                <strong class="text-white">{{ $this->peliculas->total() }}</strong>
                películas
            </div>

            @if ($this->peliculas->hasPages())
            <x-admin.tabla.paginacion id="paginacion" class="catalogo-paginacion">
                {{ $this->peliculas->links('vendor.livewire.pagination-bootstrap') }}
            </x-admin.tabla.paginacion>
            @endif

        </div>
        @endif
    </section>
</x-admin.tabla.contenedor>