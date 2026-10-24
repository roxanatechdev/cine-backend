@extends('admin.layout')

@section('title', 'Géneros cinematográficos')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-2">Géneros cinematográficos</h1>
    <p class="muted mb-0">Administra el catálogo de géneros que se asignan a las películas.</p>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="panel rounded-3 overflow-hidden">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 p-3 p-md-4 border-bottom border-secondary">
        <div>
            <h2 class="h5 mb-1">Catálogo de géneros</h2>
            <p class="muted small mb-0">{{ $generos->total() }} {{ $generos->total() === 1 ? 'género registrado' : 'géneros registrados' }}</p>
        </div>
        <a href="{{ route('admin.generos.create') }}" class="btn btn-primary">+ Registrar género</a>
    </div>

    <form method="GET" action="{{ route('admin.generos.index') }}" class="p-3 border-bottom border-secondary">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-7">
                <label for="buscar" class="form-label">Buscar</label>
                <input id="buscar" name="buscar" value="{{ $buscar }}" class="form-control" maxlength="255" placeholder="ID, nombre o descripción">
            </div>
            <div class="col-12 col-md-3">
                <label for="activo" class="form-label">Estado</label>
                <select id="activo" name="activo" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" @selected($activo === '1')>Activos</option>
                    <option value="0" @selected($activo === '0')>Inactivos</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-outline-light flex-grow-1" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.generos.index') }}">Limpiar</a>
            </div>
        </div>
    </form>

    @if ($generos->count())
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Películas</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @foreach ($generos as $genero)
                <tr>
                    <td>{{ $genero->id_genero }}</td>
                    <td class="fw-semibold">{{ $genero->nombre }}</td>
                    <td>{{ $genero->descripcion ?: '—' }}</td>
                    <td>{{ $genero->peliculas_count }}</td>
                    <td><span class="badge {{ $genero->activo ? 'bg-success' : 'bg-secondary' }}">{{ $genero->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td class="text-end"><a href="{{ route('admin.generos.edit', $genero->id_genero) }}" class="btn btn-outline-light btn-sm">Editar</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $generos->links() }}</div>
    @else
    <div class="p-4 text-center"><p class="mb-1">No se encontraron géneros.</p><p class="muted small mb-0">Prueba otra búsqueda o registra un género nuevo.</p></div>
    @endif
</div>
@endsection
