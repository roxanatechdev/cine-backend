@extends('Admin.layout')

@section('title', 'Categorías de productos')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div>
        <p class="text-uppercase small muted fw-semibold mb-2">Catálogo de confitería</p>
        <h1 class="admin-page-title">Categorías de productos</h1>
        <p class="muted mb-0">Organiza los productos de CinePrime por categoría.</p>
    </div>
    <a href="{{ route('admin.categorias-producto.create') }}" class="btn btn-primary">
        + Nueva categoría
    </a>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="panel overflow-hidden">
    <div class="p-3 p-md-4 border-bottom border-secondary">
        <form method="GET" action="{{ route('admin.categorias-producto.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label for="q" class="form-label">Buscar por nombre</label>
                <input id="q" name="q" type="search" maxlength="80"
                    value="{{ $filtros['q'] ?? '' }}" class="form-control"
                    placeholder="Ej. Bebidas, snacks...">
            </div>
            <div class="col-12 col-md-3">
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="1" @selected(($filtros['estado'] ?? '') === '1')>Activas</option>
                    <option value="0" @selected(($filtros['estado'] ?? '') === '0')>Inactivas</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit">Filtrar</button>
                <a href="{{ route('admin.categorias-producto.index') }}" class="btn btn-outline-light">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-between align-items-center gap-2 px-3 px-md-4 py-3 border-bottom border-secondary">
        <div>
            <h2 class="h6 mb-1">Listado de categorías</h2>
            <p class="muted small mb-0">{{ $categorias->total() }} {{ $categorias->total() === 1 ? 'categoría encontrada' : 'categorías encontradas' }}</p>
        </div>
    </div>

    @if ($categorias->count())
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categorias as $categoria)
                <tr>
                    <td><span class="muted">#{{ $categoria->id_categoria_producto }}</span></td>
                    <td class="fw-semibold">{{ $categoria->nombre }}</td>
                    <td class="muted" style="min-width: 220px;">{{ $categoria->descripcion ?: 'Sin descripción' }}</td>
                    <td>
                        @if ($categoria->activo)
                            <span class="badge text-bg-success">Activa</span>
                        @else
                            <span class="badge text-bg-secondary">Inactiva</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                            <a href="{{ route('admin.categorias-producto.edit', $categoria) }}" class="btn btn-outline-light btn-sm">Editar</a>
                            <form method="POST" action="{{ route('admin.categorias-producto.estado', $categoria) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="activo" value="{{ $categoria->activo ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm {{ $categoria->activo ? 'btn-outline-warning' : 'btn-outline-info' }}"
                                    onclick="return confirm('¿Confirmas que deseas {{ $categoria->activo ? 'desactivar' : 'activar' }} esta categoría?')">
                                    {{ $categoria->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div id="paginacion" class="p-3 p-md-4">
        {{ $categorias->links() }}
    </div>
    @else
    <div class="p-4 p-md-5 text-center">
        <h3 class="h5 mb-2">No hay categorías para mostrar</h3>
        <p class="muted mb-3">Prueba con otro filtro o registra la primera categoría.</p>
        <a href="{{ route('admin.categorias-producto.create') }}" class="btn btn-primary">Crear categoría</a>
    </div>
    @endif
</div>
@endsection
