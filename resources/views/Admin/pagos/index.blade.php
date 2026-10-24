@extends('admin.layout')

@section('title', 'Administración de pagos')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de pagos"
    descripcion="Consulta de intentos de pago. El estado y los importes se gestionan desde el flujo transaccional de cobro.">
    <x-slot:action>
        <a href="{{ route('admin.reembolsos.index') }}" class="btn btn-outline-light">Administrar reembolsos</a>
    </x-slot:action>
</x-admin.encabezado-pagina>

<x-admin.tabla.contenedor class="admin-table-card">
    <x-admin.tabla.filtros tag="div" class="admin-table-filters p-3 p-md-4">
        <form method="GET" action="{{ route('admin.pagos.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label for="buscar" class="form-label">Buscar pago</label>
                <input id="buscar" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="150" class="form-control" placeholder="ID de pago/compra, comprador, correo o referencia">
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    @foreach (['PENDIENTE','APROBADO','RECHAZADO'] as $estado)
                    <option value="{{ $estado }}" @selected(($filtros['estado'] ?? '' )===$estado)>{{ $estado }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <label for="metodo" class="form-label">Método</label>
                <select id="metodo" name="metodo" class="form-select">
                    <option value="">Todos</option>
                    @foreach (['QR','TARJETA'] as $metodo)
                    <option value="{{ $metodo }}" @selected(($filtros['metodo'] ?? '' )===$metodo)>{{ $metodo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Buscar</button>
                @if (!empty($filtros['buscar']) || !empty($filtros['estado']) || !empty($filtros['metodo']))
                <a href="{{ route('admin.pagos.index') }}" class="btn btn-outline-light">Limpiar</a>
                @endif
            </div>
        </form>
        <p class="admin-table-count mt-3 mb-0">{{ $pagos->total() }} {{ $pagos->total() === 1 ? 'pago encontrado' : 'pagos encontrados' }}</p>
    </x-admin.tabla.filtros>

    @if ($pagos->count())
    <div class="table-responsive">
        <table class="table table-hover align-middle admin-table">
            <thead>
                <tr>
                    <th>Pago</th>
                    <th>Compra</th>
                    <th>Comprador</th>
                    <th>Método</th>
                    <th>Importe</th>
                    <th>Estado</th>
                    <th>Referencia externa</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pagos as $pago)
                <tr>
                    <td class="fw-semibold">#{{ $pago->id_pago }}
                        <div class="muted small">{{ $pago->creado_en ? \Illuminate\Support\Carbon::parse($pago->creado_en)->format('d/m/Y H:i') : '—' }}</div>
                    </td>
                    <td><a href="{{ route('admin.compras.show', $pago->id_compra) }}" class="link-info">#{{ $pago->id_compra }}</a></td>
                    <td>{{ $pago->comprador_nombre }}
                        <div class="muted small">{{ $pago->comprador_correo }}</div>
                    </td>
                    <td>{{ $pago->metodo }}</td>
                    <td class="text-nowrap">{{ $pago->compra_moneda }} {{ number_format((float) $pago->importe, 2, ',', '.') }}</td>
                    <td><span class="badge bg-{{ match($pago->estado) {'APROBADO' => 'success', 'RECHAZADO' => 'secondary', 'PENDIENTE' => 'warning', default => 'info'} }}">{{ $pago->estado }}</span></td>
                    <td>{{ $pago->referencia_externa ?: '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.pagos.show', $pago->id_pago) }}"
                            class="admin-action-icon"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Ver detalle"
                            aria-label="Ver detalle del pago #{{ $pago->id_pago }}">
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-admin.tabla.paginacion class="admin-table-footer p-3">{{ $pagos->links() }}</x-admin.tabla.paginacion>
    @else
    <div class="admin-table-empty">
        <h2 class="h5">No se encontraron pagos</h2>
        <p class="muted mb-0">Prueba con otros criterios de búsqueda.</p>
    </div>
    @endif
</x-admin.tabla.contenedor>
@endsection