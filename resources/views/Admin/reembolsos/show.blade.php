@extends('admin.layout')

@section('title', 'Reembolso #' . $reembolso->id_reembolso)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div><a href="{{ route('admin.reembolsos.index') }}" class="link-info text-decoration-none">← Volver a reembolsos</a><h1 class="h3 mt-2 mb-1">Reembolso #{{ $reembolso->id_reembolso }}</h1><p class="muted mb-0">Consulta de la solicitud y transiciones de estado controladas.</p></div>
    <span class="badge fs-6 bg-{{ match($reembolso->estado) {'COMPLETADO' => 'success', 'RECHAZADO' => 'secondary', 'PROCESANDO' => 'info', 'SOLICITADO' => 'warning', default => 'secondary'} }}">{{ $reembolso->estado }}</span>
</div>

@if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6"><section class="panel p-3 p-md-4 h-100"><h2 class="h5 mb-3">Datos del reembolso</h2>
        <dl class="row mb-0">
            <dt class="col-sm-5">ID del reembolso</dt><dd class="col-sm-7">#{{ $reembolso->id_reembolso }}</dd>
            <dt class="col-sm-5">Importe</dt><dd class="col-sm-7 fw-bold">{{ $reembolso->moneda }} {{ number_format((float) $reembolso->importe, 2, ',', '.') }}</dd>
            <dt class="col-sm-5">Estado</dt><dd class="col-sm-7">{{ $reembolso->estado }}</dd>
            <dt class="col-sm-5">Motivo</dt><dd class="col-sm-7">{{ $reembolso->motivo ?: '—' }}</dd>
            <dt class="col-sm-5">Solicitado en</dt><dd class="col-sm-7">{{ $reembolso->solicitado_en ? \Illuminate\Support\Carbon::parse($reembolso->solicitado_en)->format('d/m/Y H:i:s') : '—' }}</dd>
            <dt class="col-sm-5">Completado en</dt><dd class="col-sm-7">{{ $reembolso->completado_en ? \Illuminate\Support\Carbon::parse($reembolso->completado_en)->format('d/m/Y H:i:s') : '—' }}</dd>
            <dt class="col-sm-5">Referencia externa</dt><dd class="col-sm-7">{{ $reembolso->referencia_externa ?: '—' }}</dd>
        </dl>
    </section></div>
    <div class="col-12 col-xl-6"><section class="panel p-3 p-md-4 h-100"><h2 class="h5 mb-3">Pago y compra asociados</h2>
        <dl class="row mb-0">
            <dt class="col-sm-5">Pago</dt><dd class="col-sm-7"><a class="link-info" href="{{ route('admin.pagos.show', $reembolso->id_pago) }}">Ver pago #{{ $reembolso->id_pago }}</a> — {{ $reembolso->pago_estado }}</dd>
            <dt class="col-sm-5">Importe del pago</dt><dd class="col-sm-7">{{ $reembolso->moneda }} {{ number_format((float) $reembolso->pago_importe, 2, ',', '.') }}</dd>
            <dt class="col-sm-5">Saldo reembolsable</dt><dd class="col-sm-7">{{ $reembolso->moneda }} {{ number_format((float) $disponible, 2, ',', '.') }}</dd>
            <dt class="col-sm-5">Compra</dt><dd class="col-sm-7"><a class="link-info" href="{{ route('admin.compras.show', $reembolso->id_compra) }}">Ver compra #{{ $reembolso->id_compra }}</a> — {{ str_replace('_', ' ', $reembolso->compra_estado) }}</dd>
            <dt class="col-sm-5">Comprador</dt><dd class="col-sm-7">{{ $reembolso->comprador_nombre }}<div class="muted small">{{ $reembolso->comprador_correo }}</div></dd>
        </dl>
    </section></div>
</div>

@if (count($transiciones))
<section class="panel p-3 p-md-4">
    <h2 class="h5 mb-2">Actualizar estado</h2>
    <p class="muted">Flujo permitido: SOLICITADO → PROCESANDO o RECHAZADO; PROCESANDO → COMPLETADO o RECHAZADO. Los estados finales no se pueden reabrir.</p>
    <form method="POST" action="{{ route('admin.reembolsos.estado', $reembolso->id_reembolso) }}" class="row g-3 align-items-end" onsubmit="return confirm('Confirma el cambio de estado. COMPLETADO solo debe registrarse tras verificar la devolución en el proveedor.');">
        @csrf
        @method('PATCH')
        <div class="col-12 col-md-4">
            <label for="estado" class="form-label">Nuevo estado</label>
            <select id="estado" name="estado" class="form-select" required>
                <option value="">Seleccionar…</option>
                @foreach ($transiciones as $estado)
                <option value="{{ $estado }}" @selected(old('estado') === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-5">
            <label for="referencia_externa" class="form-label">Referencia externa de devolución</label>
            <input id="referencia_externa" name="referencia_externa" type="text" maxlength="150" value="{{ old('referencia_externa') }}" class="form-control" placeholder="Obligatoria para COMPLETADO">
        </div>
        <div class="col-12 col-md-3"><button type="submit" class="btn btn-primary w-100">Guardar estado</button></div>
    </form>
</section>
@else
<section class="panel p-3 p-md-4"><h2 class="h5 mb-2">Estado final</h2><p class="muted mb-0">Este reembolso está {{ $reembolso->estado }} y no admite más transiciones desde el panel.</p></section>
@endif
@endsection
