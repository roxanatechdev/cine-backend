@extends('admin.layout')

@section('title', 'Administración de compradores')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de compradores"
    descripcion="Consulta y administra los datos de contacto y el tipo de comprador."
/>

@if (session('success'))
<div class="alert alert-success" role="alert">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<x-admin.tabla.contenedor class="admin-table-card">
    <div class="admin-table-filters compradores-toolbar p-3 p-md-4">
        <div class="compradores-toolbar__summary">
            <h2 class="h5 mb-1">Compradores registrados</h2>
            <p class="admin-table-count mb-0">{{ $compradores->total() }} {{ $compradores->total() === 1 ? 'comprador' : 'compradores' }}</p>
        </div>
        <form method="GET" action="{{ route('admin.compradores.index') }}" class="compradores-toolbar__search">
            <input type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="150" class="form-control" placeholder="Nombre, correo o teléfono" aria-label="Buscar comprador">
            <button type="submit" class="btn btn-outline-light">Buscar</button>
            @if (!empty($filtros['buscar']))<a href="{{ route('admin.compradores.index') }}" class="btn btn-outline-light">Limpiar</a>@endif
        </form>
        <div class="compradores-toolbar__action">
            <a href="{{ route('admin.compradores.create') }}" class="btn btn-primary">+ Registrar comprador</a>
        </div>
    </div>

    @if ($compradores->count())
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
            <thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Tipo</th><th>Creado en</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @foreach ($compradores as $comprador)
                <tr>
                    <td class="fw-semibold comprador-nombre">{{ $comprador->nombre }}</td>
                    <td class="comprador-correo">{{ $comprador->correo }}</td>
                    <td class="comprador-telefono">{{ $comprador->telefono ?: '—' }}</td>
                    <td><span class="badge bg-{{ $comprador->tipo_comprador === 'REGISTRADO' ? 'primary' : 'secondary' }}">{{ $comprador->tipo_comprador }}</span></td>
                    <td>{{ $comprador->creado_en?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.compradores.edit', $comprador->id_comprador) }}"
                           class="btn admin-action-icon"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           title="Editar"
                           aria-label="Editar comprador">
                            <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                        </a>
                        <form method="POST" action="{{ route('admin.compradores.destroy', $comprador->id_comprador) }}" class="d-inline" onsubmit="return confirm('¿Eliminar este comprador? Si tiene compras asociadas, no podrá eliminarse.');">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="btn admin-action-icon admin-action-icon--danger"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Eliminar"
                                    aria-label="Eliminar comprador">
                                <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-admin.tabla.paginacion class="admin-table-footer p-3">{{ $compradores->links() }}</x-admin.tabla.paginacion>
    @else
    <div class="admin-table-empty">
        <h2 class="h5">No se encontraron compradores</h2>
        <p class="muted mb-3">Registra un comprador o modifica los criterios de búsqueda.</p>
        <a href="{{ route('admin.compradores.create') }}" class="btn btn-primary">Registrar comprador</a>
    </div>
    @endif
</x-admin.tabla.contenedor>
@endsection
