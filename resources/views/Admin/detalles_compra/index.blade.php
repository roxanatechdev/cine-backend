@extends('admin.layout')

@section('title', 'Detalles de compra')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-2">Detalles de compra</h1>
    <p class="muted mb-0">Consulta de las líneas de productos incluidas en las compras. La cantidad y el precio unitario son históricos y no se editan aquí.</p>
</div>

<div class="panel rounded-3 overflow-hidden">
    <div class="p-3 border-bottom border-secondary"><h2 class="h5 mb-1">Historial de productos comprados</h2><p class="muted small mb-0">{{ $detalles->total() }} líneas registradas</p></div>
    <form method="GET" action="{{ route('admin.detalles-compra.index') }}" class="p-3 border-bottom border-secondary">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6"><label for="buscar" class="form-label">Buscar</label><input id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" class="form-control" maxlength="150" placeholder="ID de línea, compra, producto, comprador o correo"></div>
            <div class="col-12 col-md-3"><label for="estado_compra" class="form-label">Estado de compra</label><select id="estado_compra" name="estado_compra" class="form-select"><option value="">Todos</option>@foreach (['INICIADA','PENDIENTE_PAGO','CONFIRMADA','CANCELADA','EXPIRADA'] as $estado)<option value="{{ $estado }}" @selected(($filtros['estado_compra'] ?? '') === $estado)>{{ str_replace('_', ' ', $estado) }}</option>@endforeach</select></div>
            <div class="col-12 col-md-3"><label for="producto" class="form-label">ID de producto</label><input id="producto" name="producto" type="number" min="1" value="{{ $filtros['producto'] ?? '' }}" class="form-control"></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-outline-light" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="{{ route('admin.detalles-compra.index') }}">Limpiar</a></div>
        </div>
    </form>
    @if ($detalles->count())
    <div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>ID línea</th><th>Compra</th><th>Producto</th><th>Comprador</th><th>Cantidad</th><th>Precio unitario histórico</th><th>Subtotal</th><th>Estado compra</th><th></th></tr></thead><tbody>
        @foreach ($detalles as $detalle)<tr>
            <td>#{{ $detalle->id_detalle_compra }}</td>
            <td><a class="link-info" href="{{ route('admin.compras.show', $detalle->id_compra) }}">#{{ $detalle->id_compra }}</a><div class="muted small">{{ $detalle->creada_en ? \Illuminate\Support\Carbon::parse($detalle->creada_en)->format('d/m/Y H:i') : '—' }}</div></td>
            <td><div class="fw-semibold">{{ $detalle->producto_nombre }}</div><div class="muted small">Producto #{{ $detalle->id_producto }}</div></td>
            <td>{{ $detalle->comprador_nombre ?? '—' }}<div class="muted small">{{ $detalle->comprador_correo ?? '' }}</div></td>
            <td>{{ $detalle->cantidad }}</td>
            <td>{{ $detalle->moneda }} {{ number_format((float) $detalle->precio_unitario, 2, ',', '.') }}</td>
            <td>{{ $detalle->moneda }} {{ number_format((float) $detalle->cantidad * (float) $detalle->precio_unitario, 2, ',', '.') }}</td>
            <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $detalle->estado_compra) }}</span></td>
            <td><a class="btn btn-outline-light btn-sm" href="{{ route('admin.detalles-compra.show', $detalle->id_detalle_compra) }}">Detalle</a></td>
        </tr>@endforeach
    </tbody></table></div>
    <div class="p-3">{{ $detalles->links() }}</div>
    @else
    <div class="p-4 text-center"><p class="mb-1">No se encontraron líneas de compra.</p><p class="muted small mb-0">Prueba otros filtros o consulta una compra existente.</p></div>
    @endif
</div>
@endsection
