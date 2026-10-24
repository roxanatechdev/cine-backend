@extends('admin.layout')

@section('title', 'Administración de funciones')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de funciones"
    descripcion="Programa proyecciones y administra sus formatos, precios y estados."
/>

@if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<x-admin.tabla.contenedor class="admin-table-card">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 p-3 p-md-4 admin-table-filters">
        <div>
            <h2 class="h5 mb-1">Funciones registradas</h2>
            <p class="admin-table-count mb-0">{{ $funciones->total() }} {{ $funciones->total() === 1 ? 'función registrada' : 'funciones registradas' }}</p>
        </div>
        <a href="{{ route('admin.funciones.create') }}" class="btn btn-primary">+ Registrar función</a>
    </div>

    @if ($funciones->count())
    <div class="table-responsive">
        <table class="table table-hover align-middle admin-table">
            <thead><tr><th>Inicio</th><th>Película</th><th>Sala</th><th>Formato</th><th>Precio base</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @foreach ($funciones as $funcion)
                <tr>
                    <td class="text-nowrap">{{ $funcion->inicio?->format('d/m/Y H:i') }}</td>
                    <td class="fw-semibold">{{ $funcion->pelicula?->titulo ?? 'Película no disponible' }}</td>
                    <td>{{ $funcion->sala?->nombre ?? 'Sala no disponible' }}</td>
                    <td>{{ $funcion->formato }}</td>
                    <td>${{ number_format((float) $funcion->precio_base, 2, ',', '.') }}</td>
                    <td><span class="badge bg-{{ $funcion->estado === 'PROGRAMADA' ? 'primary' : ($funcion->estado === 'EN_CURSO' ? 'success' : ($funcion->estado === 'CANCELADA' ? 'danger' : 'secondary')) }}">{{ str_replace('_', ' ', $funcion->estado) }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.funciones.edit', $funcion->id_funcion) }}"
                           class="btn admin-action-icon"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           title="Editar"
                           aria-label="Editar función">
                            <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                        </a>
                        <form method="POST" action="{{ route('admin.funciones.destroy', $funcion->id_funcion) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta función? Si tiene asientos o entradas asociados, no podrá eliminarse.');">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="btn admin-action-icon admin-action-icon--danger"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Eliminar"
                                    aria-label="Eliminar función">
                                <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-admin.tabla.paginacion class="admin-table-footer p-3">{{ $funciones->links() }}</x-admin.tabla.paginacion>
    @else
    <div class="admin-table-empty">
        <h2 class="h5">Todavía no hay funciones registradas</h2>
        <p class="muted mb-3">Registra una función seleccionando una película activa y una sala activa.</p>
        <a href="{{ route('admin.funciones.create') }}" class="btn btn-primary">Registrar primera función</a>
    </div>
    @endif
</x-admin.tabla.contenedor>
@endsection
