@extends('admin.layout')

@section('title', 'Entrada #' . $entrada->id_entrada)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div>
        <a href="{{ route('admin.entradas.index') }}" class="link-info text-decoration-none">← Volver a entradas</a>
        <h1 class="h3 mt-2 mb-1">Entrada #{{ $entrada->id_entrada }}</h1>
        <p class="muted mb-0">Registro de historial en modo consulta.</p>
    </div>
    <span class="badge fs-6 bg-{{ match($entrada->estado) {'CONFIRMADA' => 'success', 'UTILIZADA' => 'primary', 'CANCELADA','EXPIRADA' => 'secondary', 'RESERVADA' => 'warning', default => 'info'} }}">{{ str_replace('_', ' ', $entrada->estado) }}</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
        <section class="panel p-3 p-md-4 h-100">
            <h2 class="h5 mb-3">Datos de la entrada</h2>
            <dl class="row mb-0">
                <dt class="col-sm-5">ID de entrada</dt><dd class="col-sm-7">#{{ $entrada->id_entrada }}</dd>
                <dt class="col-sm-5">Precio histórico</dt><dd class="col-sm-7">{{ $entrada->precio_venta }}</dd>
                <dt class="col-sm-5">Reservada en</dt><dd class="col-sm-7">{{ $entrada->reservada_en ? \Illuminate\Support\Carbon::parse($entrada->reservada_en)->format('d/m/Y H:i:s') : '—' }}</dd>
                <dt class="col-sm-5">Confirmada en</dt><dd class="col-sm-7">{{ $entrada->confirmada_en ? \Illuminate\Support\Carbon::parse($entrada->confirmada_en)->format('d/m/Y H:i:s') : '—' }}</dd>
                <dt class="col-sm-5">Utilizada en</dt><dd class="col-sm-7">{{ $entrada->utilizada_en ? \Illuminate\Support\Carbon::parse($entrada->utilizada_en)->format('d/m/Y H:i:s') : '—' }}</dd>
                <dt class="col-sm-5">Cerrada en</dt><dd class="col-sm-7">{{ $entrada->cerrada_en ? \Illuminate\Support\Carbon::parse($entrada->cerrada_en)->format('d/m/Y H:i:s') : '—' }}</dd>
            </dl>
        </section>
    </div>
    <div class="col-12 col-xl-6">
        <section class="panel p-3 p-md-4 h-100">
            <h2 class="h5 mb-3">Compra y comprador</h2>
            <dl class="row mb-0">
                <dt class="col-sm-5">Compra</dt><dd class="col-sm-7"><a class="link-info" href="{{ route('admin.compras.show', $entrada->id_compra) }}">#{{ $entrada->id_compra }}</a></dd>
                <dt class="col-sm-5">Estado de compra</dt><dd class="col-sm-7">{{ str_replace('_', ' ', $entrada->compra_estado) }}</dd>
                <dt class="col-sm-5">Total de compra</dt><dd class="col-sm-7">{{ $entrada->compra_moneda }} {{ number_format((float) $entrada->compra_total, 2, ',', '.') }}</dd>
                <dt class="col-sm-5">Comprador</dt><dd class="col-sm-7">{{ $entrada->comprador_nombre }}</dd>
                <dt class="col-sm-5">Correo</dt><dd class="col-sm-7">{{ $entrada->comprador_correo }}</dd>
                <dt class="col-sm-5">Teléfono</dt><dd class="col-sm-7">{{ $entrada->comprador_telefono ?: '—' }}</dd>
            </dl>
        </section>
    </div>
</div>

<section class="panel p-3 p-md-4 mb-4">
    <h2 class="h5 mb-3">Función y asiento</h2>
    <dl class="row mb-0">
        <dt class="col-sm-3">Película</dt><dd class="col-sm-9">{{ $entrada->pelicula_titulo }}</dd>
        <dt class="col-sm-3">Fecha y hora</dt><dd class="col-sm-9">{{ \Illuminate\Support\Carbon::parse($entrada->funcion_inicio)->format('d/m/Y H:i') }}</dd>
        <dt class="col-sm-3">Formato</dt><dd class="col-sm-9">{{ $entrada->funcion_formato }}</dd>
        <dt class="col-sm-3">Estado de función</dt><dd class="col-sm-9">{{ str_replace('_', ' ', $entrada->funcion_estado) }}</dd>
        <dt class="col-sm-3">Sala</dt><dd class="col-sm-9">{{ $entrada->sala_nombre }}</dd>
        <dt class="col-sm-3">Asiento</dt><dd class="col-sm-9">{{ $entrada->asiento_fila }}{{ $entrada->asiento_numero }} ({{ $entrada->asiento_tipo }})</dd>
    </dl>
</section>

<section class="panel p-3 p-md-4">
    <h2 class="h5 mb-3">Ticket asociado a la compra</h2>
    @if ($ticket)
        <p class="mb-1"><strong>Estado:</strong> {{ $ticket->estado }}</p>
        <p class="mb-1"><strong>Emitido:</strong> {{ $ticket->emitido_en ? \Illuminate\Support\Carbon::parse($ticket->emitido_en)->format('d/m/Y H:i:s') : '—' }}</p>
        <p class="mb-0"><strong>Utilizado:</strong> {{ $ticket->utilizado_en ? \Illuminate\Support\Carbon::parse($ticket->utilizado_en)->format('d/m/Y H:i:s') : '—' }}</p>
    @else
        <p class="muted mb-0">La compra todavía no tiene un ticket asociado.</p>
    @endif
</section>

<div class="alert alert-info mt-4 mb-0" role="note">Los estados de entrada se actualizan mediante los flujos de compra y validación de tickets. No se permite modificarlos de forma aislada para preservar la ocupación del asiento y el historial transaccional.</div>
@endsection
