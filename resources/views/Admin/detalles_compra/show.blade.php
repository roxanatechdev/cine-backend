@extends('admin.layout')

@section('title', 'Detalle de compra #' . $detalle->id_detalle_compra)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.detalles-compra.index') }}" class="link-info text-decoration-none">← Volver a detalles de compra</a>
    <h1 class="h3 mt-2 mb-1">Detalle de compra #{{ $detalle->id_detalle_compra }}</h1>
    <p class="muted mb-0">Registro de solo lectura. El precio unitario corresponde al momento de la compra y puede diferir del precio actual del catálogo.</p>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7"><section class="panel p-3 p-md-4 h-100"><h2 class="h5 mb-3">Línea de producto</h2>
        <dl class="row mb-0">
            <dt class="col-sm-5">Producto</dt><dd class="col-sm-7"><a class="link-info" href="{{ route('admin.productos.edit', $detalle->id_producto) }}">{{ $detalle->producto_nombre }}</a> (#{{ $detalle->id_producto }})</dd>
            <dt class="col-sm-5">Categoría actual</dt><dd class="col-sm-7">{{ $detalle->categoria_nombre ?? 'Sin categoría' }}</dd>
            <dt class="col-sm-5">Estado actual del producto</dt><dd class="col-sm-7">{{ $detalle->estado_producto }}</dd>
            <dt class="col-sm-5">Cantidad comprada</dt><dd class="col-sm-7">{{ $detalle->cantidad }}</dd>
            <dt class="col-sm-5">Precio unitario histórico</dt><dd class="col-sm-7">{{ $detalle->moneda }} {{ number_format((float) $detalle->precio_unitario, 2, ',', '.') }}</dd>
            <dt class="col-sm-5">Subtotal de la línea</dt><dd class="col-sm-7 fw-bold">{{ $detalle->moneda }} {{ number_format($detalle->subtotal, 2, ',', '.') }}</dd>
            <dt class="col-sm-5">Precio actual de catálogo</dt><dd class="col-sm-7">{{ $detalle->moneda }} {{ number_format((float) $detalle->precio_actual_producto, 2, ',', '.') }}</dd>
            <dt class="col-sm-5">Descripción actual</dt><dd class="col-sm-7">{{ $detalle->producto_descripcion ?: 'Sin descripción' }}</dd>
        </dl>
    </section></div>
    <div class="col-12 col-xl-5"><section class="panel p-3 p-md-4 h-100"><h2 class="h5 mb-3">Compra asociada</h2>
        <dl class="row mb-3">
            <dt class="col-sm-5">Compra</dt><dd class="col-sm-7"><a class="link-info" href="{{ route('admin.compras.show', $detalle->id_compra) }}">#{{ $detalle->id_compra }}</a></dd>
            <dt class="col-sm-5">Estado</dt><dd class="col-sm-7"><span class="badge bg-secondary">{{ str_replace('_', ' ', $detalle->estado_compra) }}</span></dd>
            <dt class="col-sm-5">Fecha de compra</dt><dd class="col-sm-7">{{ $detalle->creada_en ? \Illuminate\Support\Carbon::parse($detalle->creada_en)->format('d/m/Y H:i:s') : '—' }}</dd>
            <dt class="col-sm-5">Total de compra</dt><dd class="col-sm-7">{{ $detalle->moneda }} {{ number_format((float) $detalle->total_compra, 2, ',', '.') }}</dd>
        </dl>
        <h3 class="h6">Comprador</h3>
        <p class="mb-1">{{ $detalle->comprador_nombre ?? '—' }}</p><p class="muted mb-0">{{ $detalle->comprador_correo ?? '' }}</p>
    </section></div>
</div>
@endsection
