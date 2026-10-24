@extends('Admin.layout')

@section('title', 'Idiomas')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="admin-page-title">Catálogo de idiomas</h1>
        <p class="muted mb-0">Códigos de idioma BCP 47 utilizados por las películas.</p>
    </div>
    <a href="{{ route('admin.idiomas.create') }}" class="btn btn-primary">+ Registrar idioma</a>
</div>

<div class="panel mb-4">
    <form method="GET" action="{{ route('admin.idiomas.index') }}" class="p-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-7">
                <label for="buscar" class="form-label">Buscar por código o nombre</label>
                <input id="buscar" name="buscar" value="{{ $buscar }}" class="form-control" maxlength="100" placeholder="Ej.: es, pt-BR, Español">
            </div>
            <div class="col-12 col-md-3">
                <label for="activo" class="form-label">Estado</label>
                <select id="activo" name="activo" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" @selected($activo === '1')>Activo</option>
                    <option value="0" @selected($activo === '0')>Inactivo</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-outline-info flex-grow-1" type="submit">Buscar</button>
                <a class="btn btn-outline-light" href="{{ route('admin.idiomas.index') }}">Limpiar</a>
            </div>
        </div>
    </form>
</div>

<div class="panel overflow-hidden">
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead><tr><th>Código</th><th>Nombre</th><th>Películas</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse ($idiomas as $idioma)
                <tr>
                    <td><code>{{ $idioma->codigo }}</code></td>
                    <td>{{ $idioma->nombre }}</td>
                    <td>{{ $idioma->peliculas_originales_count }}</td>
                    <td><span class="badge {{ $idioma->activo ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $idioma->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td class="text-end"><a href="{{ route('admin.idiomas.edit', $idioma->codigo) }}" class="btn btn-outline-light btn-sm">Editar</a></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center muted py-4">No hay idiomas que coincidan con la búsqueda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($idiomas->hasPages())
    <div class="p-3">{{ $idiomas->links() }}</div>
    @endif
</div>
@endsection
