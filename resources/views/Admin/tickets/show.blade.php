@extends('admin.layout')

@section('title', 'Ticket #' . $ticket->id_ticket)

@section('content')
@php
    $claseEstadoTicket = match ($ticket->estado) {
        'EMITIDO' => 'success',
        'UTILIZADO' => 'info',
        'ANULADO' => 'default',
        default => '',
    };

    $claseEstadoCompra = match ($ticket->compra_estado) {
        'CONFIRMADA' => 'success',
        'PENDIENTE_PAGO', 'INICIADA' => 'warning',
        'CANCELADA', 'EXPIRADA' => 'danger',
        default => '',
    };

    $formatearFecha = static fn ($fecha, string $formato = 'd/m/Y H:i:s'): string =>
        $fecha ? \Illuminate\Support\Carbon::parse($fecha)->format($formato) : '—';
@endphp

<x-admin.encabezado-pagina
    :titulo="'Ticket #' . $ticket->id_ticket"
    descripcion="Consulta los datos del ticket, la compra relacionada y la trazabilidad de sus entradas y pagos.">
    <x-slot:action>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="admin-pill {{ $claseEstadoTicket }}">
                {{ str_replace('_', ' ', $ticket->estado) }}
            </span>
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-light">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                Volver a tickets
            </a>
        </div>
    </x-slot:action>
</x-admin.encabezado-pagina>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
        <section class="form-cm p-3 p-md-4 h-100">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="admin-section-title">Datos del ticket</h2>
                    <p class="admin-section-subtitle mb-0">Identificación y estado de uso.</p>
                </div>
                <i class="bi bi-ticket-perforated fs-4 text-info" aria-hidden="true"></i>
            </div>

            <dl class="row g-2 mb-0">
                <dt class="col-sm-5 text-secondary">ID del ticket</dt>
                <dd class="col-sm-7 fw-semibold">#{{ $ticket->id_ticket }}</dd>

                <dt class="col-sm-5 text-secondary">Código de validación</dt>
                <dd class="col-sm-7"><code class="text-break">{{ $ticket->codigo_validacion }}</code></dd>

                <dt class="col-sm-5 text-secondary">Estado</dt>
                <dd class="col-sm-7">
                    <span class="admin-pill {{ $claseEstadoTicket }}">{{ str_replace('_', ' ', $ticket->estado) }}</span>
                </dd>

                <dt class="col-sm-5 text-secondary">Emitido en</dt>
                <dd class="col-sm-7">{{ $formatearFecha($ticket->emitido_en) }}</dd>

                <dt class="col-sm-5 text-secondary">Utilizado en</dt>
                <dd class="col-sm-7">{{ $formatearFecha($ticket->utilizado_en) }}</dd>
            </dl>
        </section>
    </div>

    <div class="col-12 col-xl-6">
        <section class="form-cm p-3 p-md-4 h-100">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="admin-section-title">Compra y comprador</h2>
                    <p class="admin-section-subtitle mb-0">Información de la transacción que originó el ticket.</p>
                </div>
                <i class="bi bi-receipt fs-4 text-info" aria-hidden="true"></i>
            </div>

            <dl class="row g-2 mb-0">
                <dt class="col-sm-5 text-secondary">Compra</dt>
                <dd class="col-sm-7">
                    <a href="{{ route('admin.compras.show', $ticket->id_compra) }}" class="link-info">
                        Ver compra #{{ $ticket->id_compra }}
                    </a>
                </dd>

                <dt class="col-sm-5 text-secondary">Estado de compra</dt>
                <dd class="col-sm-7">
                    <span class="admin-pill {{ $claseEstadoCompra }}">
                        {{ str_replace('_', ' ', $ticket->compra_estado) }}
                    </span>
                </dd>

                <dt class="col-sm-5 text-secondary">Total</dt>
                <dd class="col-sm-7 fw-bold fs-5">
                    {{ $ticket->moneda }} {{ number_format((float) $ticket->compra_total, 2, ',', '.') }}
                </dd>

                <dt class="col-sm-5 text-secondary">Creada en</dt>
                <dd class="col-sm-7">{{ $formatearFecha($ticket->compra_creada_en) }}</dd>

                <dt class="col-sm-5 text-secondary">Comprador</dt>
                <dd class="col-sm-7">
                    <span class="fw-semibold">{{ $ticket->comprador_nombre }}</span>
                    <div class="small muted text-break">
                        {{ $ticket->comprador_correo }}
                        <span aria-hidden="true">·</span>
                        {{ $ticket->comprador_telefono ?: 'Sin teléfono' }}
                    </div>
                </dd>
            </dl>
        </section>
    </div>
</div>

<x-admin.tabla.contenedor class="admin-table-card mb-4">
    <div class="p-3 border-bottom border-secondary border-opacity-50">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="admin-section-title">Entradas asociadas</h2>
                <p class="admin-section-subtitle mb-0">Películas, funciones y localidades incluidas en la compra.</p>
            </div>
            <span class="admin-pill">{{ $entradas->count() }} {{ $entradas->count() === 1 ? 'entrada' : 'entradas' }}</span>
        </div>
    </div>

    @if ($entradas->count())
        <div class="table-responsive">
            <table class="table admin-table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">Entrada</th>
                        <th scope="col">Película</th>
                        <th scope="col">Función</th>
                        <th scope="col">Asiento</th>
                        <th scope="col">Precio</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entradas as $entrada)
                        <tr>
                            <td>
                                <a href="{{ route('admin.entradas.show', $entrada->id_entrada) }}" class="link-info">
                                    #{{ $entrada->id_entrada }}
                                </a>
                            </td>
                            <td class="text-break">{{ $entrada->titulo ?: '—' }}</td>
                            <td>{{ $formatearFecha($entrada->inicio, 'd/m/Y H:i') }}</td>
                            <td>{{ $entrada->fila && $entrada->numero ? $entrada->fila . $entrada->numero : '—' }}</td>
                            <td class="text-nowrap">{{ $ticket->moneda }} {{ number_format((float) $entrada->precio_venta, 2, ',', '.') }}</td>
                            <td>{{ str_replace('_', ' ', $entrada->entrada_estado) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="admin-table-empty">
            <i class="bi bi-ticket-perforated fs-3 d-block mb-2" aria-hidden="true"></i>
            <h2 class="h6">No hay entradas asociadas</h2>
            <p class="mb-0">Esta compra no tiene entradas registradas.</p>
        </div>
    @endif
</x-admin.tabla.contenedor>

<x-admin.tabla.contenedor class="admin-table-card">
    <div class="p-3 border-bottom border-secondary border-opacity-50">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="admin-section-title">Pagos asociados</h2>
                <p class="admin-section-subtitle mb-0">Intentos de pago registrados para esta compra.</p>
            </div>
            <span class="admin-pill">{{ $pagos->count() }} {{ $pagos->count() === 1 ? 'pago' : 'pagos' }}</span>
        </div>
    </div>

    @if ($pagos->count())
        <div class="table-responsive">
            <table class="table admin-table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">Pago</th>
                        <th scope="col">Método</th>
                        <th scope="col">Importe</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Referencia</th>
                        <th scope="col" class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pagos as $pago)
                        <tr>
                            <td>#{{ $pago->id_pago }}</td>
                            <td>{{ $pago->metodo }}</td>
                            <td class="text-nowrap">{{ $ticket->moneda }} {{ number_format((float) $pago->importe, 2, ',', '.') }}</td>
                            <td>{{ str_replace('_', ' ', $pago->estado) }}</td>
                            <td class="text-break">{{ $pago->referencia_externa ?: '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.pagos.show', $pago->id_pago) }}"
                                    class="btn btn-outline-light btn-sm"
                                    aria-label="Ver pago #{{ $pago->id_pago }}">
                                    <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                    Ver pago
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="admin-table-empty">
            <i class="bi bi-credit-card fs-3 d-block mb-2" aria-hidden="true"></i>
            <h2 class="h6">No hay pagos asociados</h2>
            <p class="mb-0">No se encontraron intentos de pago para esta compra.</p>
        </div>
    @endif
</x-admin.tabla.contenedor>
@endsection
