@extends('admin.layout')

@section('title', 'Administración de reembolsos')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-2">Administración de reembolsos</h1>
    <p class="muted mb-0">Consulta de solicitudes y seguimiento de devoluciones. Un reembolso solo se marca COMPLETADO después de verificarlo con el proveedor de pagos.</p>
</div>

<div class="panel rounded-3 overflow-hidden">
    <div class="p-3 p-md-4 border-bottom border-secondary">
        <form method="GET" action="{{ route('admin.reembolsos.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-lg-7">
                <label for="buscar" class="form-label">Buscar reembolso</label>
                <input id="buscar" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="150" class="form-control" placeholder="ID reembolso/pago/compra, comprador, correo o referencia">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    @foreach (['SOLICITADO','PROCESANDO','COMPLETADO','RECHAZADO'] as $estado)
                    <option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Buscar</button>
                @if (!empty($filtros['buscar']) || !empty($filtros['estado']))
                <a href="{{ route('admin.reembolsos.index') }}" class="btn btn-outline-light">Limpiar</a>
                @endif
            </div>
        </form>
        <p class="muted small mt-3 mb-0">{{ $reembolsos->total() }} {{ $reembolsos->total() === 1 ? 'reembolso encontrado' : 'reembolsos encontrados' }}</p>
    </div>

    @if ($reembolsos->count())
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead><tr><th>Reembolso</th><th>Pago / compra</th><th>Comprador</th><th>Importe</th><th>Estado</th><th>Referencia externa</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
                @foreach ($reembolsos as $reembolso)
                <tr>
                    <td class="fw-semibold">#{{ $reembolso->id_reembolso }}<div class="muted small">{{ $reembolso->solicitado_en ? \Illuminate\Support\Carbon::parse($reembolso->solicitado_en)->format('d/m/Y H:i') : '—' }}</div></td>
                    <td><a href="{{ route('admin.pagos.show', $reembolso->id_pago) }}" class="link-info">Pago #{{ $reembolso->id_pago }}</a><div class="muted small">Compra #{{ $reembolso->id_compra }}</div></td>
                    <td>{{ $reembolso->comprador_nombre }}<div class="muted small">{{ $reembolso->comprador_correo }}</div></td>
                    <td class="text-nowrap">{{ $reembolso->moneda }} {{ number_format((float) $reembolso->importe, 2, ',', '.') }}</td>
                    <td><span class="badge bg-{{ match($reembolso->estado) {'COMPLETADO' => 'success', 'RECHAZADO' => 'secondary', 'PROCESANDO' => 'info', 'SOLICITADO' => 'warning', default => 'secondary'} }}">{{ $reembolso->estado }}</span></td>
                    <td>{{ $reembolso->referencia_externa ?: '—' }}</td>
                    <td class="text-end"><a href="{{ route('admin.reembolsos.show', $reembolso->id_reembolso) }}" class="btn btn-outline-light btn-sm">Ver detalle</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $reembolsos->links() }}</div>
    @else
    <div class="p-4 text-center"><h2 class="h5">No se encontraron reembolsos</h2><p class="muted mb-0">Prueba con otros criterios de búsqueda o registra una solicitud desde un pago aprobado.</p></div>
    @endif
</div>
@endsection
