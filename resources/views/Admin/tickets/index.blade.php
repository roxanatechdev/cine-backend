@extends('admin.layout')

@section('title', 'Administración de tickets')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div><h1 class="h3 mb-2">Administración de tickets</h1><p class="muted mb-0">Consulta de tickets emitidos y su relación con compras y entradas. Los estados se modifican desde el flujo de validación o de compra.</p></div>
    <a href="{{ route('admin.compras.index') }}" class="btn btn-outline-light">Ver compras</a>
</div>

<div class="panel rounded-3 overflow-hidden">
    <div class="p-3 p-md-4 border-bottom border-secondary">
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-lg-7"><label for="buscar" class="form-label">Buscar ticket</label><input id="buscar" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="150" class="form-control" placeholder="ID de ticket/compra, código, comprador o correo"></div>
            <div class="col-12 col-sm-6 col-lg-2"><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos</option>@foreach (['EMITIDO','UTILIZADO','ANULADO'] as $estado)<option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ $estado }}</option>@endforeach</select></div>
            <div class="col-12 col-sm-6 col-lg-3 d-flex gap-2"><button type="submit" class="btn btn-primary flex-grow-1">Buscar</button>@if (!empty($filtros['buscar']) || !empty($filtros['estado']))<a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-light">Limpiar</a>@endif</div>
        </form>
        <p class="muted small mt-3 mb-0">{{ $tickets->total() }} {{ $tickets->total() === 1 ? 'ticket encontrado' : 'tickets encontrados' }}</p>
    </div>
    @if ($tickets->count())
    <div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>Ticket</th><th>Código de validación</th><th>Compra</th><th>Comprador</th><th>Estado</th><th>Emitido</th><th class="text-end">Acción</th></tr></thead><tbody>
        @foreach ($tickets as $ticket)<tr>
            <td class="fw-semibold">#{{ $ticket->id_ticket }}</td><td><code>{{ $ticket->codigo_validacion }}</code></td>
            <td><a href="{{ route('admin.compras.show', $ticket->id_compra) }}" class="link-info">#{{ $ticket->id_compra }}</a><div class="muted small">{{ str_replace('_', ' ', $ticket->compra_estado) }}</div></td>
            <td>{{ $ticket->comprador_nombre }}<div class="muted small">{{ $ticket->comprador_correo }}</div></td>
            <td><span class="badge bg-{{ match($ticket->estado) {'EMITIDO' => 'success', 'UTILIZADO' => 'info', 'ANULADO' => 'secondary', default => 'secondary'} }}">{{ $ticket->estado }}</span></td>
            <td>{{ $ticket->emitido_en ? \Illuminate\Support\Carbon::parse($ticket->emitido_en)->format('d/m/Y H:i') : '—' }}</td>
            <td class="text-end"><a href="{{ route('admin.tickets.show', $ticket->id_ticket) }}" class="btn btn-outline-light btn-sm">Ver detalle</a></td>
        </tr>@endforeach
    </tbody></table></div><div class="p-3">{{ $tickets->links() }}</div>
    @else<div class="p-4 text-center"><h2 class="h5">No se encontraron tickets</h2><p class="muted mb-0">Prueba con otros criterios de búsqueda.</p></div>@endif
</div>
@endsection
