@extends('admin.layout')

@section('title', 'Administración de entradas')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-2">Administración de entradas</h1>
    <p class="muted mb-0">Historial de reservas y ventas. Las entradas no se crean, editan ni eliminan manualmente desde este módulo.</p>
</div>

@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif

<div class="panel rounded-3 overflow-hidden">
    <div class="p-3 p-md-4 border-bottom border-secondary">
        <form method="GET" action="{{ route('admin.entradas.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-lg-7">
                <label for="buscar" class="form-label">Buscar entrada</label>
                <input id="buscar" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="150" class="form-control" placeholder="ID de entrada/compra, comprador, correo o película">
            </div>
            <div class="col-12 col-sm-7 col-lg-3">
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    @foreach (['RESERVADA','CONFIRMADA','UTILIZADA','CANCELADA','EXPIRADA'] as $estado)
                    <option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ str_replace('_', ' ', $estado) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-5 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Buscar</button>
                @if (!empty($filtros['buscar']) || !empty($filtros['estado']))<a href="{{ route('admin.entradas.index') }}" class="btn btn-outline-light">Limpiar</a>@endif
            </div>
        </form>
        <p class="muted small mt-3 mb-0">{{ $entradas->total() }} {{ $entradas->total() === 1 ? 'entrada encontrada' : 'entradas encontradas' }}</p>
    </div>

    @if ($entradas->count())
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead><tr><th>Entrada</th><th>Compra</th><th>Comprador</th><th>Película / función</th><th>Asiento</th><th>Precio</th><th>Estado</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
                @foreach ($entradas as $entrada)
                <tr>
                    <td class="fw-semibold">#{{ $entrada->id_entrada }}<div class="muted small">{{ $entrada->reservada_en ? \Illuminate\Support\Carbon::parse($entrada->reservada_en)->format('d/m/Y H:i') : '—' }}</div></td>
                    <td><a href="{{ route('admin.compras.show', $entrada->id_compra) }}" class="link-info">#{{ $entrada->id_compra }}</a></td>
                    <td>{{ $entrada->comprador_nombre }}<div class="muted small">{{ $entrada->comprador_correo }}</div></td>
                    <td>{{ $entrada->pelicula_titulo }}<div class="muted small">{{ \Illuminate\Support\Carbon::parse($entrada->funcion_inicio)->format('d/m/Y H:i') }}</div></td>
                    <td>{{ $entrada->asiento_fila }}{{ $entrada->asiento_numero }}</td>
                    <td class="text-nowrap">{{ number_format((float) $entrada->precio_venta, 2, ',', '.') }}</td>
                    <td><span class="badge bg-{{ match($entrada->estado) {'CONFIRMADA' => 'success', 'UTILIZADA' => 'primary', 'CANCELADA','EXPIRADA' => 'secondary', 'RESERVADA' => 'warning', default => 'info'} }}">{{ str_replace('_', ' ', $entrada->estado) }}</span></td>
                    <td class="text-end"><a href="{{ route('admin.entradas.show', $entrada->id_entrada) }}" class="btn btn-outline-light btn-sm">Ver detalle</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $entradas->links() }}</div>
    @else
    <div class="p-4 text-center"><h2 class="h5">No se encontraron entradas</h2><p class="muted mb-0">Prueba con otros criterios de búsqueda.</p></div>
    @endif
</div>
@endsection
