@extends('admin.layout')

@section('title', 'Ticket #' . $ticket->id_ticket)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div><a href="{{ route('admin.tickets.index') }}" class="link-info text-decoration-none">← Volver a tickets</a><h1 class="h3 mt-2 mb-1">Ticket #{{ $ticket->id_ticket }}</h1><p class="muted mb-0">Detalle de solo lectura del ticket y su trazabilidad.</p></div>
    <span class="badge fs-6 bg-{{ match($ticket->estado) {'EMITIDO' => 'success', 'UTILIZADO' => 'info', 'ANULADO' => 'secondary', default => 'secondary'} }}">{{ $ticket->estado }}</span>
</div>
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6"><section class="panel p-3 p-md-4 h-100"><h2 class="h5 mb-3">Datos del ticket</h2><dl class="row mb-0">
        <dt class="col-sm-5">ID del ticket</dt><dd class="col-sm-7">#{{ $ticket->id_ticket }}</dd>
        <dt class="col-sm-5">Código de validación</dt><dd class="col-sm-7"><code>{{ $ticket->codigo_validacion }}</code></dd>
        <dt class="col-sm-5">Estado</dt><dd class="col-sm-7">{{ $ticket->estado }}</dd>
        <dt class="col-sm-5">Emitido en</dt><dd class="col-sm-7">{{ $ticket->emitido_en ? \Illuminate\Support\Carbon::parse($ticket->emitido_en)->format('d/m/Y H:i:s') : '—' }}</dd>
        <dt class="col-sm-5">Utilizado en</dt><dd class="col-sm-7">{{ $ticket->utilizado_en ? \Illuminate\Support\Carbon::parse($ticket->utilizado_en)->format('d/m/Y H:i:s') : '—' }}</dd>
    </dl></section></div>
    <div class="col-12 col-xl-6"><section class="panel p-3 p-md-4 h-100"><h2 class="h5 mb-3">Compra y comprador</h2><dl class="row mb-0">
        <dt class="col-sm-5">Compra</dt><dd class="col-sm-7"><a class="link-info" href="{{ route('admin.compras.show', $ticket->id_compra) }}">Ver compra #{{ $ticket->id_compra }}</a></dd>
        <dt class="col-sm-5">Estado de compra</dt><dd class="col-sm-7">{{ str_replace('_', ' ', $ticket->compra_estado) }}</dd>
        <dt class="col-sm-5">Total</dt><dd class="col-sm-7 fw-bold">{{ $ticket->moneda }} {{ number_format((float) $ticket->compra_total, 2, ',', '.') }}</dd>
        <dt class="col-sm-5">Creada en</dt><dd class="col-sm-7">{{ $ticket->compra_creada_en ? \Illuminate\Support\Carbon::parse($ticket->compra_creada_en)->format('d/m/Y H:i:s') : '—' }}</dd>
        <dt class="col-sm-5">Comprador</dt><dd class="col-sm-7">{{ $ticket->comprador_nombre }}<div class="muted small">{{ $ticket->comprador_correo }} · {{ $ticket->comprador_telefono ?: 'Sin teléfono' }}</div></dd>
    </dl></section></div>
</div>
<section class="panel rounded-3 overflow-hidden mb-4"><div class="p-3 border-bottom border-secondary"><h2 class="h5 mb-0">Entradas asociadas ({{ $entradas->count() }})</h2></div>
    @if ($entradas->count())<div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>Entrada</th><th>Película</th><th>Función</th><th>Asiento</th><th>Precio</th><th>Estado</th></tr></thead><tbody>
        @foreach ($entradas as $entrada)<tr><td><a class="link-info" href="{{ route('admin.entradas.show', $entrada->id_entrada) }}">#{{ $entrada->id_entrada }}</a></td><td>{{ $entrada->titulo ?: '—' }}</td><td>{{ $entrada->inicio ? \Illuminate\Support\Carbon::parse($entrada->inicio)->format('d/m/Y H:i') : '—' }}</td><td>{{ $entrada->fila && $entrada->numero ? $entrada->fila . $entrada->numero : '—' }}</td><td>{{ $ticket->moneda }} {{ number_format((float) $entrada->precio_venta, 2, ',', '.') }}</td><td>{{ $entrada->entrada_estado }}</td></tr>@endforeach
    </tbody></table></div>@else<div class="p-3 muted">Esta compra no tiene entradas asociadas.</div>@endif
</section>
<section class="panel rounded-3 overflow-hidden"><div class="p-3 border-bottom border-secondary"><h2 class="h5 mb-0">Pagos asociados ({{ $pagos->count() }})</h2></div>
    @if ($pagos->count())<div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>Pago</th><th>Método</th><th>Importe</th><th>Estado</th><th>Referencia</th><th></th></tr></thead><tbody>
        @foreach ($pagos as $pago)<tr><td>#{{ $pago->id_pago }}</td><td>{{ $pago->metodo }}</td><td>{{ $ticket->moneda }} {{ number_format((float) $pago->importe, 2, ',', '.') }}</td><td>{{ $pago->estado }}</td><td>{{ $pago->referencia_externa ?: '—' }}</td><td><a class="btn btn-outline-light btn-sm" href="{{ route('admin.pagos.show', $pago->id_pago) }}">Ver pago</a></td></tr>@endforeach
    </tbody></table></div>@else<div class="p-3 muted">No hay pagos asociados a esta compra.</div>@endif
</section>
@endsection
