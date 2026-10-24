@extends('admin.layout')

@section('title', 'Administración de compras')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de compras"
    descripcion="Consulta el historial y gestiona transiciones de estado sin editar importes ni eliminar registros."
/>

@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<x-admin.tabla.contenedor class="admin-table-card">
    <x-admin.tabla.filtros class="admin-table-filters p-3 p-md-4">
        <form method="GET" action="{{ route('admin.compras.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-lg-6">
                <label for="buscar" class="form-label">Buscar compra o comprador</label>
                <input id="buscar" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="150" class="form-control" placeholder="ID de compra, nombre o correo">
            </div>
            <div class="col-12 col-sm-7 col-lg-4">
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    @foreach (['INICIADA','PENDIENTE_PAGO','CONFIRMADA','CANCELADA','EXPIRADA'] as $estado)
                    <option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ str_replace('_', ' ', $estado) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-5 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Buscar</button>
                @if (!empty($filtros['buscar']) || !empty($filtros['estado']))<a href="{{ route('admin.compras.index') }}" class="btn btn-outline-light">Limpiar</a>@endif
            </div>
        </form>
        <p class="admin-table-count mt-3 mb-0">{{ $compras->total() }} {{ $compras->total() === 1 ? 'compra encontrada' : 'compras encontradas' }}</p>
    </x-admin.tabla.filtros>

    @if ($compras->count())
    <div class="table-responsive">
        <table class="table table-hover align-middle admin-table">
            <thead><tr><th>ID</th><th>Comprador</th><th>Creada</th><th>Vence reserva</th><th>Total</th><th>Estado</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
                @foreach ($compras as $compra)
                <tr>
                    <td class="fw-semibold">#{{ $compra->id_compra }}</td>
                    <td>{{ $compra->comprador?->nombre ?? '—' }}<div class="muted small">{{ $compra->comprador?->correo ?? '' }}</div></td>
                    <td>{{ $compra->creada_en?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $compra->vence_reserva?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="text-nowrap">{{ $compra->moneda }} {{ number_format((float) $compra->total, 2, ',', '.') }}</td>
                    <td><span class="badge bg-{{ match($compra->estado) {'CONFIRMADA' => 'success', 'CANCELADA','EXPIRADA' => 'secondary', 'PENDIENTE_PAGO' => 'warning', default => 'info'} }}">{{ str_replace('_', ' ', $compra->estado) }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('admin.compras.show', $compra->id_compra) }}"
                            class="admin-action-icon"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Ver detalle"
                            aria-label="Ver detalle de la compra #{{ $compra->id_compra }}">
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-admin.tabla.paginacion class="admin-table-footer p-3">{{ $compras->links() }}</x-admin.tabla.paginacion>
    @else
    <div class="admin-table-empty"><h2 class="h5">No se encontraron compras</h2><p class="mb-0">Prueba con otros criterios de búsqueda.</p></div>
    @endif
</x-admin.tabla.contenedor>
@endsection
