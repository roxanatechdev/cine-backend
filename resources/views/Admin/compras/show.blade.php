@extends('admin.layout')

@section('title', 'Compra #' . $compra->id_compra)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div><a href="{{ route('admin.compras.index') }}" class="link-info text-decoration-none">← Volver a compras</a><h1 class="h3 mt-2 mb-1">Compra #{{ $compra->id_compra }}</h1><p class="muted mb-0">Consulta de historial y estado; los importes son de solo lectura.</p></div>
    <span class="badge fs-6 bg-{{ match($compra->estado) {'CONFIRMADA' => 'success', 'CANCELADA','EXPIRADA' => 'secondary', 'PENDIENTE_PAGO' => 'warning', default => 'info'} }}">{{ str_replace('_', ' ', $compra->estado) }}</span>
</div>

@if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-7"><section class="panel p-3 p-md-4 h-100"><h2 class="h5 mb-3">Resumen</h2>
        <dl class="row mb-0">
            <dt class="col-sm-4">Comprador</dt><dd class="col-sm-8">{{ $compra->comprador?->nombre ?? '—' }}<div class="muted small">{{ $compra->comprador?->correo ?? '' }} · {{ $compra->comprador?->telefono ?? 'Sin teléfono' }}</div></dd>
            <dt class="col-sm-4">Creada</dt><dd class="col-sm-8">{{ $compra->creada_en?->format('d/m/Y H:i:s') ?? '—' }}</dd>
            <dt class="col-sm-4">Vencimiento de reserva</dt><dd class="col-sm-8">{{ $compra->vence_reserva?->format('d/m/Y H:i:s') ?? 'Sin vencimiento' }}</dd>
            <dt class="col-sm-4">Total</dt><dd class="col-sm-8 fw-bold">{{ $compra->moneda }} {{ number_format((float) $compra->total, 2, ',', '.') }}</dd>
            <dt class="col-sm-4">Cancelada en</dt><dd class="col-sm-8">{{ $compra->cancelada_en?->format('d/m/Y H:i:s') ?? '—' }}</dd>
            <dt class="col-sm-4">Motivo de cancelación</dt><dd class="col-sm-8">{{ $compra->motivo_cancelacion ?: '—' }}</dd>
        </dl>
    </section></div>
    <div class="col-12 col-xl-5"><section class="panel p-3 p-md-4 h-100"><h2 class="h5 mb-3">Cambio de estado</h2>
        @php
            $destinos = match($compra->estado) {
                'INICIADA' => ['CANCELADA', 'EXPIRADA'],
                'PENDIENTE_PAGO' => ['CONFIRMADA', 'CANCELADA', 'EXPIRADA'],
                'CONFIRMADA' => ['CANCELADA'],
                default => [],
            };
        @endphp
        @if (count($destinos))
        <form method="POST" action="{{ route('admin.compras.estado', $compra->id_compra) }}" onsubmit="return confirm('¿Confirmas este cambio? Se actualizarán también las entradas y el ticket cuando corresponda.');">
            @csrf @method('PATCH')
            <label for="estado" class="form-label">Nuevo estado</label>
            <select id="estado" name="estado" class="form-select mb-3" required>
                <option value="">Selecciona...</option>
                @foreach ($destinos as $destino)<option value="{{ $destino }}" @selected(old('estado') === $destino)>{{ str_replace('_', ' ', $destino) }}</option>@endforeach
            </select>
            <label for="motivo_cancelacion" class="form-label">Motivo de cancelación (opcional)</label>
            <textarea id="motivo_cancelacion" name="motivo_cancelacion" maxlength="255" rows="2" class="form-control mb-3" placeholder="Motivo para auditoría">{{ old('motivo_cancelacion') }}</textarea>
            <button type="submit" class="btn btn-primary w-100">Aplicar cambio</button>
        </form>
        @else
        <p class="muted mb-0">Este estado es terminal desde la administración. No se permite reabrir la compra.</p>
        @endif
        <hr class="border-secondary"><p class="small muted mb-0">Confirmar requiere un pago aprobado por el total exacto. Expirar requiere vencimiento efectivo. Cancelar no procesa reembolsos; estos se gestionan por separado.</p>
    </section></div>
</div>

<section class="panel rounded-3 overflow-hidden mb-4"><div class="p-3 border-bottom border-secondary"><h2 class="h5 mb-0">Entradas ({{ $entradas->count() }})</h2></div>
    @if ($entradas->count())<div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>ID</th><th>Película</th><th>Función</th><th>Asiento</th><th>Precio</th><th>Estado</th></tr></thead><tbody>
        @foreach ($entradas as $entrada)<tr><td><a class="link-info" href="{{ route('admin.entradas.show', $entrada->id_entrada) }}">#{{ $entrada->id_entrada }}</a></td><td>{{ $entrada->titulo }}</td><td>{{ \Illuminate\Support\Carbon::parse($entrada->inicio)->format('d/m/Y H:i') }}</td><td>{{ $entrada->fila }}{{ $entrada->numero }}</td><td>{{ number_format((float) $entrada->precio_venta, 2, ',', '.') }}</td><td>{{ str_replace('_', ' ', $entrada->estado_entrada) }}</td></tr>@endforeach
    </tbody></table></div>@else<div class="p-3 muted">Esta compra no tiene entradas asociadas.</div>@endif
</section>

<section class="panel rounded-3 overflow-hidden mb-4"><div class="p-3 border-bottom border-secondary"><h2 class="h5 mb-0">Productos ({{ $productos->count() }})</h2></div>
    @if ($productos->count())<div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio unitario histórico</th><th>Subtotal</th></tr></thead><tbody>
        @foreach ($productos as $producto)<tr><td><a class="link-info" href="{{ route('admin.detalles-compra.show', $producto->id_detalle_compra) }}">{{ $producto->nombre }}</a></td><td>{{ $producto->cantidad }}</td><td>{{ number_format((float) $producto->precio_unitario, 2, ',', '.') }}</td><td>{{ number_format((float) $producto->precio_unitario * (int) $producto->cantidad, 2, ',', '.') }}</td></tr>@endforeach
    </tbody></table></div>@else<div class="p-3 muted">Esta compra no tiene productos asociados.</div>@endif
</section>

<section class="panel rounded-3 overflow-hidden mb-4"><div class="p-3 border-bottom border-secondary"><h2 class="h5 mb-0">Pagos ({{ $pagos->count() }})</h2></div>
    @if ($pagos->count())<div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>ID</th><th>Método</th><th>Importe</th><th>Estado</th><th>Referencia</th><th>Creado</th><th>Aprobado</th></tr></thead><tbody>
        @foreach ($pagos as $pago)<tr><td><a class="link-info" href="{{ route('admin.pagos.show', $pago->id_pago) }}">#{{ $pago->id_pago }}</a></td><td>{{ $pago->metodo }}</td><td>{{ number_format((float) $pago->importe, 2, ',', '.') }}</td><td>{{ $pago->estado }}</td><td>{{ $pago->referencia_externa ?: '—' }}</td><td>{{ $pago->creado_en ? \Illuminate\Support\Carbon::parse($pago->creado_en)->format('d/m/Y H:i') : '—' }}</td><td>{{ $pago->aprobado_en ? \Illuminate\Support\Carbon::parse($pago->aprobado_en)->format('d/m/Y H:i') : '—' }}</td></tr>@endforeach
    </tbody></table></div>@else<div class="p-3 muted">No hay intentos de pago asociados.</div>@endif
</section>

<section class="panel rounded-3 overflow-hidden mb-4"><div class="p-3 border-bottom border-secondary"><h2 class="h5 mb-0">Ticket</h2></div>
    @if ($ticket)<div class="p-3"><p class="mb-1"><strong>Código:</strong> <code>{{ $ticket->codigo_validacion }}</code></p><p class="mb-1"><strong>Estado:</strong> {{ $ticket->estado }} · <strong>Emitido:</strong> {{ \Illuminate\Support\Carbon::parse($ticket->emitido_en)->format('d/m/Y H:i') }}</p><a class="link-info" href="{{ route('admin.tickets.show', $ticket->id_ticket) }}">Ver detalle del ticket #{{ $ticket->id_ticket }}</a></div>@else<div class="p-3 muted">No existe ticket asociado.</div>@endif
</section>

<section class="panel rounded-3 overflow-hidden mb-4"><div class="p-3 border-bottom border-secondary"><h2 class="h5 mb-0">Reembolsos ({{ $reembolsos->count() }})</h2></div>
    @if ($reembolsos->count())<div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>ID</th><th>Pago</th><th>Importe</th><th>Estado</th><th>Motivo</th><th>Solicitado</th></tr></thead><tbody>
        @foreach ($reembolsos as $reembolso)<tr><td>#{{ $reembolso->id_reembolso }}</td><td>#{{ $reembolso->id_pago }}</td><td>{{ number_format((float) $reembolso->importe, 2, ',', '.') }}</td><td>{{ $reembolso->estado }}</td><td>{{ $reembolso->motivo ?: '—' }}</td><td>{{ \Illuminate\Support\Carbon::parse($reembolso->solicitado_en)->format('d/m/Y H:i') }}</td></tr>@endforeach
    </tbody></table></div>@else<div class="p-3 muted">No hay reembolsos asociados.</div>@endif
</section>
@endsection
