@extends('admin.layout')

@section('title', 'Pago #' . $pago->id_pago)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div><a href="{{ route('admin.pagos.index') }}" class="link-info text-decoration-none">← Volver a pagos</a><h1 class="h3 mt-2 mb-1">Pago #{{ $pago->id_pago }}</h1><p class="muted mb-0">Detalle de solo lectura del intento de pago y su historial asociado.</p></div>
    <div class="d-flex flex-wrap align-items-center gap-2"><span class="badge fs-6 bg-{{ match($pago->estado) {'APROBADO' => 'success', 'RECHAZADO' => 'secondary', 'PENDIENTE' => 'warning', default => 'info'} }}">{{ $pago->estado }}</span>@if ($pago->estado === 'APROBADO')<a href="{{ route('admin.reembolsos.create', $pago->id_pago) }}" class="btn btn-outline-light">Solicitar reembolso</a>@endif</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6"><section class="panel p-3 p-md-4 h-100"><h2 class="h5 mb-3">Datos del pago</h2>
        <dl class="row mb-0">
            <dt class="col-sm-5">ID del pago</dt><dd class="col-sm-7">#{{ $pago->id_pago }}</dd>
            <dt class="col-sm-5">Método</dt><dd class="col-sm-7">{{ $pago->metodo }}</dd>
            <dt class="col-sm-5">Importe</dt><dd class="col-sm-7 fw-bold">{{ $pago->compra_moneda }} {{ number_format((float) $pago->importe, 2, ',', '.') }}</dd>
            <dt class="col-sm-5">Referencia externa</dt><dd class="col-sm-7">{{ $pago->referencia_externa ?: '—' }}</dd>
            <dt class="col-sm-5">Creado en</dt><dd class="col-sm-7">{{ $pago->creado_en ? \Illuminate\Support\Carbon::parse($pago->creado_en)->format('d/m/Y H:i:s') : '—' }}</dd>
            <dt class="col-sm-5">Aprobado en</dt><dd class="col-sm-7">{{ $pago->aprobado_en ? \Illuminate\Support\Carbon::parse($pago->aprobado_en)->format('d/m/Y H:i:s') : '—' }}</dd>
        </dl>
    </section></div>
    <div class="col-12 col-xl-6"><section class="panel p-3 p-md-4 h-100"><h2 class="h5 mb-3">Compra asociada</h2>
        <dl class="row mb-0">
            <dt class="col-sm-5">Compra</dt><dd class="col-sm-7"><a href="{{ route('admin.compras.show', $pago->id_compra) }}" class="link-info">Ver compra #{{ $pago->id_compra }}</a></dd>
            <dt class="col-sm-5">Estado de compra</dt><dd class="col-sm-7">{{ str_replace('_', ' ', $pago->compra_estado) }}</dd>
            <dt class="col-sm-5">Total de compra</dt><dd class="col-sm-7">{{ $pago->compra_moneda }} {{ number_format((float) $pago->compra_total, 2, ',', '.') }}</dd>
            <dt class="col-sm-5">Comprador</dt><dd class="col-sm-7">{{ $pago->comprador_nombre }}<div class="muted small">{{ $pago->comprador_correo }} · {{ $pago->comprador_telefono ?: 'Sin teléfono' }}</div></dd>
            <dt class="col-sm-5">Reserva vence</dt><dd class="col-sm-7">{{ $pago->vence_reserva ? \Illuminate\Support\Carbon::parse($pago->vence_reserva)->format('d/m/Y H:i:s') : 'Sin vencimiento' }}</dd>
        </dl>
        <hr class="border-secondary"><p class="small muted mb-0">El cambio de estado del pago no se realiza desde esta pantalla: aprobar un pago debe validar importe, vencimiento y duplicidad, y coordinar la confirmación de compra, entradas y ticket.</p>
    </section></div>
</div>

<section class="panel rounded-3 overflow-hidden mb-4"><div class="p-3 border-bottom border-secondary"><h2 class="h5 mb-0">Otros intentos de pago de esta compra ({{ $otrosPagos->count() }})</h2></div>
    @if ($otrosPagos->count())<div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>ID</th><th>Método</th><th>Importe</th><th>Estado</th><th>Creado</th><th></th></tr></thead><tbody>
        @foreach ($otrosPagos as $otro)<tr><td>#{{ $otro->id_pago }}</td><td>{{ $otro->metodo }}</td><td>{{ $pago->compra_moneda }} {{ number_format((float) $otro->importe, 2, ',', '.') }}</td><td>{{ $otro->estado }}</td><td>{{ $otro->creado_en ? \Illuminate\Support\Carbon::parse($otro->creado_en)->format('d/m/Y H:i') : '—' }}</td><td class="text-end"><a href="{{ route('admin.pagos.show', $otro->id_pago) }}" class="btn btn-outline-light btn-sm">Ver</a></td></tr>@endforeach
    </tbody></table></div>@else<div class="p-3 muted">No hay otros intentos de pago asociados a esta compra.</div>@endif
</section>

<section class="panel rounded-3 overflow-hidden"><div class="p-3 border-bottom border-secondary"><h2 class="h5 mb-0">Reembolsos asociados ({{ $reembolsos->count() }})</h2></div>
    @if ($reembolsos->count())<div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>ID</th><th>Importe</th><th>Estado</th><th>Motivo</th><th>Solicitado</th><th>Completado</th><th></th></tr></thead><tbody>
        @foreach ($reembolsos as $reembolso)<tr><td>#{{ $reembolso->id_reembolso }}</td><td>{{ $pago->compra_moneda }} {{ number_format((float) $reembolso->importe, 2, ',', '.') }}</td><td>{{ $reembolso->estado }}</td><td>{{ $reembolso->motivo ?: '—' }}</td><td>{{ $reembolso->solicitado_en ? \Illuminate\Support\Carbon::parse($reembolso->solicitado_en)->format('d/m/Y H:i') : '—' }}</td><td>{{ $reembolso->completado_en ? \Illuminate\Support\Carbon::parse($reembolso->completado_en)->format('d/m/Y H:i') : '—' }}</td><td><a href="{{ route('admin.reembolsos.show', $reembolso->id_reembolso) }}" class="btn btn-outline-light btn-sm">Ver</a></td></tr>@endforeach
    </tbody></table></div>@else<div class="p-3 muted">No hay reembolsos asociados a este pago.</div>@endif
</section>
@endsection
