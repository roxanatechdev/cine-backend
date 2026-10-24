@extends('admin.layout')

@section('title', 'Solicitar reembolso')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.pagos.show', $pago->id_pago) }}" class="link-info text-decoration-none">← Volver al pago #{{ $pago->id_pago }}</a>
    <h1 class="h3 mt-2 mb-1">Solicitar reembolso</h1>
    <p class="muted mb-0">Esta acción registra una solicitud; no ejecuta automáticamente la devolución en el proveedor de pagos.</p>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
    <div class="col-12 col-xl-5">
        <section class="panel p-3 p-md-4 h-100">
            <h2 class="h5 mb-3">Pago asociado</h2>
            <dl class="row mb-0">
                <dt class="col-sm-5">Pago</dt><dd class="col-sm-7">#{{ $pago->id_pago }} — {{ $pago->estado }}</dd>
                <dt class="col-sm-5">Compra</dt><dd class="col-sm-7"><a class="link-info" href="{{ route('admin.compras.show', $pago->id_compra) }}">#{{ $pago->id_compra }}</a> — {{ str_replace('_', ' ', $pago->compra_estado) }}</dd>
                <dt class="col-sm-5">Comprador</dt><dd class="col-sm-7">{{ $pago->comprador_nombre }}<div class="muted small">{{ $pago->comprador_correo }}</div></dd>
                <dt class="col-sm-5">Importe pagado</dt><dd class="col-sm-7">{{ $pago->moneda }} {{ number_format((float) $pago->importe, 2, ',', '.') }}</dd>
                <dt class="col-sm-5">Saldo reembolsable</dt><dd class="col-sm-7 fw-bold">{{ $pago->moneda }} {{ number_format((float) $disponible, 2, ',', '.') }}</dd>
            </dl>
        </section>
    </div>
    <div class="col-12 col-xl-7">
        <section class="panel p-3 p-md-4">
            <h2 class="h5 mb-3">Datos de la solicitud</h2>
            <form method="POST" action="{{ route('admin.reembolsos.store', $pago->id_pago) }}">
                @csrf
                <div class="mb-3">
                    <label for="importe" class="form-label">Importe a reembolsar ({{ $pago->moneda }})</label>
                    <input id="importe" name="importe" type="number" min="0.01" max="{{ $disponible }}" step="0.01" required value="{{ old('importe') }}" class="form-control" @if ((float) $disponible <= 0) disabled @endif>
                    <div class="form-text">No puede superar el saldo disponible de {{ $pago->moneda }} {{ number_format((float) $disponible, 2, ',', '.') }}.</div>
                </div>
                <div class="mb-3">
                    <label for="motivo" class="form-label">Motivo (opcional)</label>
                    <textarea id="motivo" name="motivo" maxlength="255" rows="3" class="form-control" @if ((float) $disponible <= 0) disabled @endif>{{ old('motivo') }}</textarea>
                </div>
                @if ((float) $disponible <= 0)
                    <div class="alert alert-warning">Este pago no tiene saldo reembolsable disponible.</div>
                @endif
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" @if ((float) $disponible <= 0) disabled @endif>Registrar solicitud</button>
                    <a href="{{ route('admin.pagos.show', $pago->id_pago) }}" class="btn btn-outline-light">Cancelar</a>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
