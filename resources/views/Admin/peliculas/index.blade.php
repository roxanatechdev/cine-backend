@extends('admin.layout')

@section('title', 'Gestión de películas')

@section('content')
{{-- Encabezado del catálogo --}}
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <span class="text-info small fw-semibold text-uppercase">
            CinePrime / Administración
        </span>

        <h1 class="admin-page-title mt-2">
            Gestión de películas
        </h1>

        <p class="muted mb-0">
            Administra, busca y consulta las películas del catálogo.
        </p>
    </div>

    <div>
        <a href="{{ route('admin.peliculas.create') }}"
            class="btn btn-primary px-3">
            <span aria-hidden="true">+</span>
            Nueva película
        </a>
    </div>
</div>

@if ($errors->any())
<div class="alert alert-danger mb-4" role="alert">
    <div class="fw-semibold mb-1">
        No se pudieron aplicar los filtros.
    </div>
    <p class="mb-0">
        Revisa los valores seleccionados e inténtalo nuevamente.
    </p>
</div>
@endif

{{-- Búsqueda y filtros --}}
<section class="panel p-3 p-lg-4 mb-4"
    aria-labelledby="filtros-titulo">

    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="text-info" aria-hidden="true">
            <icon name="sliders-horizontal" size="20" />
        </span>

        <h2 id="filtros-titulo" class="h5 mb-0">
            Buscar y filtrar
        </h2>
    </div>

    <form method="GET"
        action="{{ route('admin.peliculas.index') }}">

        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label for="q" class="form-label">
                    Título de la película
                </label>

                <input
                    id="q"
                    name="q"
                    type="search"
                    maxlength="150"
                    class="form-control"
                    value="{{ request('q') }}"
                    placeholder="Ej. El Reino de Cristal"
                    autocomplete="off">
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <label for="estado" class="form-label">
                    Estado
                </label>

                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos los estados</option>

                    <option value="BORRADOR"
                        @selected(request('estado')==='BORRADOR' )>
                        Borradores
                    </option>

                    <option value="ACTIVA"
                        @selected(request('estado')==='ACTIVA' )>
                        Activas
                    </option>

                    <option value="INACTIVA"
                        @selected(request('estado')==='INACTIVA' )>
                        Inactivas
                    </option>
                </select>

            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <label for="clasificacion" class="form-label">
                    Clasificación
                </label>

                <select id="clasificacion"
                    name="clasificacion"
                    class="form-select">
                    <option value="">Todas</option>

                    @foreach (['G', 'PG', 'PG-13', 'R', 'NC-17'] as $clasificacion)
                    <option value="{{ $clasificacion }}"
                        @selected(request('clasificacion')===$clasificacion)>
                        {{ $clasificacion }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-lg-2 d-flex gap-2">
                <button type="submit"
                    class="btn btn-primary flex-grow-1">
                    Buscar
                </button>

                <a href="{{ route('admin.peliculas.index') }}"
                    class="btn btn-outline-light">
                    Limpiar
                </a>
            </div>
        </div>
    </form>
</section>

{{-- Resultados --}}
<section class="panel p-3 p-lg-4"
    aria-labelledby="catalogo-titulo">

    <div class="d-flex flex-column flex-sm-row
                justify-content-between align-items-sm-center
                gap-2 mb-4">

        <div>
            <h2 id="catalogo-titulo" class="h5 mb-1">
                Catálogo
            </h2>

            <p class="muted small mb-0">
                Consulta los datos y administra el estado de cada película.
            </p>
        </div>

        <span class="badge text-bg-secondary align-self-start align-self-sm-center">
            {{ $peliculas->total() }}
            {{ $peliculas->total() === 1 ? 'resultado' : 'resultados' }}
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Película</th>
                    <th scope="col">Género</th>
                    <th scope="col">Clasificación</th>
                    <th scope="col">Estreno</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($peliculas as $pelicula)
                <tr>
                    <td>
                        <span class="muted small">
                            #{{ $pelicula->id_pelicula }}
                        </span>
                    </td>

                    <td>
                        <div class="fw-semibold">
                            {{ $pelicula->titulo }}
                        </div>

                        <small class="muted">
                            @if ($pelicula->duracion_minutos)
                            {{ $pelicula->duracion_minutos }} min
                            @else
                            Duración no indicada
                            @endif
                        </small>
                    </td>

                    <td>
                        {{ $pelicula->generoPrincipal?->nombre ?? '—' }}
                    </td>

                    <td>
                        @if ($pelicula->clasificacion)
                        <span class="badge text-bg-dark border border-secondary">
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

                    <td class="text-end">
                        <a
                            href="{{ route('admin.peliculas.edit', $pelicula) }}"
                            class="btn btn-outline-info btn-sm">
                            Editar
                        </a>
                        <a href="{{ route('admin.peliculas.creditos.index', $pelicula) }}"
                            class="btn btn-outline-info btn-sm">
                            Créditos
                        </a>
                        <a href="{{ route('admin.peliculas.paises.index', $pelicula) }}" class="btn btn-outline-info btn-sm">
                            Países de producción
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="mb-3 text-info"
                            aria-hidden="true">
                            <icon name="film" size="36" />
                        </div>

                        <h3 class="h6 fw-semibold mb-2">
                            No se encontraron películas
                        </h3>

                        <p class="muted mb-3">
                            Prueba con otro título o modifica los filtros.
                        </p>

                        <a
                            href="{{ route('admin.peliculas.index') }}"
                            class="btn btn-outline-light btn-sm">
                            Limpiar filtros
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Resumen y navegación de páginas --}}
    @if ($peliculas->total() > 0)
    <div class="d-flex flex-column flex-md-row
                    justify-content-between align-items-md-center
                    gap-3 pt-3 mt-3 border-top border-secondary">

        <p class="muted small mb-0">
            Mostrando
            <strong class="text-light">
                {{ $peliculas->firstItem() }}
            </strong>
            a
            <strong class="text-light">
                {{ $peliculas->lastItem() }}
            </strong>
            de
            <strong class="text-light">
                {{ $peliculas->total() }}
            </strong>
            películas
        </p>

        @if ($peliculas->hasPages())
        <nav aria-label="Paginación del catálogo" id="paginacion">
            {{ $peliculas->withQueryString()->links() }}
        </nav>
        @endif
    </div>
    @endif
</section>
@endsection