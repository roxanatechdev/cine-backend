@extends('admin.layout')

@section('title', 'Compra #' . $compra->id_compra)

@section('content')
@php
    $claseEstado = static fn (string $estado): string => match ($estado) {
        'CONFIRMADA', 'APROBADO', 'COMPLETADO', 'EMITIDO' => 'success',
        'PENDIENTE_PAGO', 'PENDIENTE', 'RESERVADA', 'SOLICITADO', 'PROCESANDO' => 'warning',
        'RECHAZADO' => 'danger',
        'UTILIZADA' => 'info',
        'INICIADA' => 'info',
        default => '',
    };

    $destinos = match ($compra->estado) {
        'INICIADA' => ['CANCELADA', 'EXPIRADA'],
        'PENDIENTE_PAGO' => ['CONFIRMADA', 'CANCELADA', 'EXPIRADA'],
        'CONFIRMADA' => ['CANCELADA'],
        default => [],
    };

    $opcionesEstado = collect($destinos)
        ->mapWithKeys(fn ($destino) => [$destino => str_replace('_', ' ', $destino)])
        ->all();
@endphp

<x-admin.encabezado-pagina
    :titulo="'Compra #' . $compra->id_compra"
    descripcion="Consulta el resumen, los elementos asociados y el historial de la transacción. Los importes son de solo lectura.">
    <x-slot:action>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="admin-pill {{ $claseEstado($compra->estado) }}">
                {{ str_replace('_', ' ', $compra->estado) }}
            </span>
            <a href="{{ route('admin.compras.index') }}" class="btn btn-outline-light">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                Volver a compras
            </a>
        </div>
    </x-slot:action>
</x-admin.encabezado-pagina>

@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif

<x-form.errors class="mb-3" />

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-7">
        <section class="form-cm p-3 p-md-4 h-100 rounded-3">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="admin-section-title">Resumen de la compra</h2>
                    <p class="admin-section-subtitle mb-0">Información general y datos del comprador.</p>
                </div>
                <i class="bi bi-receipt-cutoff fs-4 text-info" aria-hidden="true"></i>
            </div>

            <dl class="row g-2 mb-0">
                <dt class="col-sm-4 text-secondary">Comprador</dt>
                <dd class="col-sm-8">
                    <span class="fw-semibold">{{ $compra->comprador?->nombre ?? '—' }}</span>
                    <div class="small muted text-break">
                        {{ $compra->comprador?->correo ?? 'Sin correo' }}
                        <span aria-hidden="true">·</span>
                        {{ $compra->comprador?->telefono ?? 'Sin teléfono' }}
                    </div>
                </dd>

                <dt class="col-sm-4 text-secondary">Creada</dt>
                <dd class="col-sm-8">{{ $compra->creada_en?->format('d/m/Y H:i:s') ?? '—' }}</dd>

                <dt class="col-sm-4 text-secondary">Vencimiento de reserva</dt>
                <dd class="col-sm-8">{{ $compra->vence_reserva?->format('d/m/Y H:i:s') ?? 'Sin vencimiento' }}</dd>

                <dt class="col-sm-4 text-secondary">Total</dt>
                <dd class="col-sm-8 fw-bold fs-5">
                    {{ $compra->moneda }} {{ number_format((float) $compra->total, 2, ',', '.') }}
                </dd>

                <dt class="col-sm-4 text-secondary">Cancelada en</dt>
                <dd class="col-sm-8">{{ $compra->cancelada_en?->format('d/m/Y H:i:s') ?? '—' }}</dd>

                <dt class="col-sm-4 text-secondary">Motivo de cancelación</dt>
                <dd class="col-sm-8 text-break">{{ $compra->motivo_cancelacion ?: '—' }}</dd>
            </dl>
        </section>
    </div>

    <div class="col-12 col-xl-5 ">
        <section class="form-cm p-3 p-md-4 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <i class="bi bi-arrow-left-right fs-4 text-info" aria-hidden="true"></i>
                <div>
                    <h2 class="admin-section-title">Cambio de estado</h2>
                    <p class="admin-section-subtitle mb-0">Acción administrativa sujeta a las reglas de la compra.</p>
                </div>
            </div>

            @if (count($destinos))
                <form method="POST"
                    action="{{ route('admin.compras.estado', $compra->id_compra) }}"
                    onsubmit="return confirm('¿Confirmas este cambio? Se actualizarán también las entradas y el ticket cuando corresponda.');">
                    @csrf
                    @method('PATCH')

                   
                        <div class="form-cm__group">
                            <label for="estado_toggle" class="form-cm__label">Nuevo estado</label>
                            <x-form.select
                                name="estado"
                                :options="$opcionesEstado"
                                placeholder="Selecciona un estado"
                                :selected="old('estado', '')" />
                        </div>
                   

                    <x-form.field
                        name="motivo_cancelacion"
                        label="Motivo de cancelación (opcional)"
                        id="motivo_cancelacion"
                        help="Se conserva como referencia de auditoría."
                        class="mb-3">
                        <x-form.textarea
                            name="motivo_cancelacion"
                            id="motivo_cancelacion"
                            :value="old('motivo_cancelacion')"
                            maxlength="255"
                            rows="2"
                            placeholder="Escribe el motivo, si corresponde." />
                    </x-form.field>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                        Aplicar cambio
                    </button>
                </form>
            @else
                <div class="admin-table-empty py-4">
                    <i class="bi bi-lock fs-3 d-block mb-2" aria-hidden="true"></i>
                    <p class="mb-0">Este estado es terminal desde la administración. No se permite reabrir la compra.</p>
                </div>
            @endif

            <hr class="border-secondary my-3">
            <p class="small muted mb-0">
                Confirmar requiere un pago aprobado por el total exacto. Expirar requiere vencimiento efectivo.
                Cancelar no procesa reembolsos; estos se gestionan por separado.
            </p>
        </section>
    </div>
</div>

{{-- Entradas asociadas --}}
<section class="admin-table-card mb-4">
    <div class="p-3 border-bottom border-secondary border-opacity-50">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="admin-section-title">Entradas</h2>
                <p class="admin-section-subtitle mb-0">Localidades y funciones incluidas en la compra.</p>
            </div>
            <span class="admin-pill">{{ $entradas->count() }} {{ $entradas->count() === 1 ? 'entrada' : 'entradas' }}</span>
        </div>
    </div>

    @if ($entradas->count())
        <div class="table-responsive">
            <table class="table admin-table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
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
                            <td class="text-break">{{ $entrada->titulo }}</td>
                            <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($entrada->inicio)->format('d/m/Y H:i') }}</td>
                            <td class="text-nowrap">{{ $entrada->fila }}{{ $entrada->numero }}</td>
                            <td class="text-nowrap">{{ $compra->moneda }} {{ number_format((float) $entrada->precio_venta, 2, ',', '.') }}</td>
                            <td><span class="admin-pill {{ $claseEstado($entrada->estado_entrada) }}">{{ str_replace('_', ' ', $entrada->estado_entrada) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="admin-table-empty">
            <i class="bi bi-ticket-perforated fs-3 d-block mb-2" aria-hidden="true"></i>
            <p class="mb-0">Esta compra no tiene entradas asociadas.</p>
        </div>
    @endif
</section>

{{-- Productos --}}
<section class="admin-table-card mb-4">
    <div class="p-3 border-bottom border-secondary border-opacity-50">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="admin-section-title">Productos</h2>
                <p class="admin-section-subtitle mb-0">Artículos de dulcería incluidos en la compra.</p>
            </div>
            <span class="admin-pill">{{ $productos->count() }} {{ $productos->count() === 1 ? 'producto' : 'productos' }}</span>
        </div>
    </div>

    @if ($productos->count())
        <div class="table-responsive">
            <table class="table admin-table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">Producto</th>
                        <th scope="col" class="text-end">Cantidad</th>
                        <th scope="col" class="text-end">Precio unitario histórico</th>
                        <th scope="col" class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productos as $producto)
                        <tr>
                            <td>
                                <a href="{{ route('admin.detalles-compra.show', $producto->id_detalle_compra) }}" class="link-info">
                                    {{ $producto->nombre }}
                                </a>
                            </td>
                            <td class="text-end">{{ $producto->cantidad }}</td>
                            <td class="text-end text-nowrap">{{ $compra->moneda }} {{ number_format((float) $producto->precio_unitario, 2, ',', '.') }}</td>
                            <td class="text-end text-nowrap fw-semibold">
                                {{ $compra->moneda }} {{ number_format((float) $producto->precio_unitario * (int) $producto->cantidad, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="admin-table-empty">
            <i class="bi bi-bag-x fs-3 d-block mb-2" aria-hidden="true"></i>
            <p class="mb-0">Esta compra no tiene productos asociados.</p>
        </div>
    @endif
</section>

{{-- Pagos --}}
<section class="admin-table-card mb-4">
    <div class="p-3 border-bottom border-secondary border-opacity-50">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="admin-section-title">Pagos</h2>
                <p class="admin-section-subtitle mb-0">Intentos de cobro registrados para esta compra.</p>
            </div>
            <span class="admin-pill">{{ $pagos->count() }} {{ $pagos->count() === 1 ? 'pago' : 'pagos' }}</span>
        </div>
    </div>

    @if ($pagos->count())
        <div class="table-responsive">
            <table class="table admin-table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Método</th>
                        <th scope="col">Importe</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Referencia</th>
                        <th scope="col">Creado</th>
                        <th scope="col">Aprobado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pagos as $pago)
                        <tr>
                            <td>
                                <a href="{{ route('admin.pagos.show', $pago->id_pago) }}" class="link-info">
                                    #{{ $pago->id_pago }}
                                </a>
                            </td>
                            <td>{{ $pago->metodo }}</td>
                            <td class="text-nowrap">{{ $compra->moneda }} {{ number_format((float) $pago->importe, 2, ',', '.') }}</td>
                            <td><span class="admin-pill {{ $claseEstado($pago->estado) }}">{{ $pago->estado }}</span></td>
                            <td class="text-break">{{ $pago->referencia_externa ?: '—' }}</td>
                            <td class="text-nowrap">{{ $pago->creado_en ? \Illuminate\Support\Carbon::parse($pago->creado_en)->format('d/m/Y H:i') : '—' }}</td>
                            <td class="text-nowrap">{{ $pago->aprobado_en ? \Illuminate\Support\Carbon::parse($pago->aprobado_en)->format('d/m/Y H:i') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="admin-table-empty">
            <i class="bi bi-credit-card fs-3 d-block mb-2" aria-hidden="true"></i>
            <p class="mb-0">No hay intentos de pago asociados.</p>
        </div>
    @endif
</section>

{{-- Ticket --}}
<section class="form-cm p-3 p-md-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="admin-section-title">Ticket</h2>
            <p class="admin-section-subtitle mb-0">Comprobante de validación emitido para la compra.</p>
        </div>
        @if ($ticket)
            <span class="admin-pill {{ $claseEstado($ticket->estado) }}">{{ $ticket->estado }}</span>
        @endif
    </div>

    @if ($ticket)
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6">
                <span class="small muted d-block mb-1">Código de validación</span>
                <code class="text-break">{{ $ticket->codigo_validacion }}</code>
            </div>
            <div class="col-12 col-md-3">
                <span class="small muted d-block mb-1">Emitido en</span>
                <span>{{ \Illuminate\Support\Carbon::parse($ticket->emitido_en)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="col-12 col-md-3">
                <a class="btn btn-outline-light btn-sm" href="{{ route('admin.tickets.show', $ticket->id_ticket) }}">
                    <i class="bi bi-eye me-1" aria-hidden="true"></i>
                    Ver ticket #{{ $ticket->id_ticket }}
                </a>
            </div>
        </div>
    @else
        <div class="admin-table-empty py-3">
            <i class="bi bi-ticket fs-3 d-block mb-2" aria-hidden="true"></i>
            <p class="mb-0">No existe ticket asociado.</p>
        </div>
    @endif
</section>

{{-- Reembolsos --}}
<section class="admin-table-card mb-4">
    <div class="p-3 border-bottom border-secondary border-opacity-50">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="admin-section-title">Reembolsos</h2>
                <p class="admin-section-subtitle mb-0">Seguimiento de reembolsos asociados a los pagos de esta compra.</p>
            </div>
            <span class="admin-pill">{{ $reembolsos->count() }} {{ $reembolsos->count() === 1 ? 'reembolso' : 'reembolsos' }}</span>
        </div>
    </div>

    @if ($reembolsos->count())
        <div class="table-responsive">
            <table class="table admin-table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Pago</th>
                        <th scope="col">Importe</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Motivo</th>
                        <th scope="col">Solicitado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reembolsos as $reembolso)
                        <tr>
                            <td>#{{ $reembolso->id_reembolso }}</td>
                            <td>
                                <a href="{{ route('admin.pagos.show', $reembolso->id_pago) }}" class="link-info">
                                    #{{ $reembolso->id_pago }}
                                </a>
                            </td>
                            <td class="text-nowrap">{{ $compra->moneda }} {{ number_format((float) $reembolso->importe, 2, ',', '.') }}</td>
                            <td><span class="admin-pill {{ $claseEstado($reembolso->estado) }}">{{ $reembolso->estado }}</span></td>
                            <td class="text-break">{{ $reembolso->motivo ?: '—' }}</td>
                            <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($reembolso->solicitado_en)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="admin-table-empty">
            <i class="bi bi-arrow-counterclockwise fs-3 d-block mb-2" aria-hidden="true"></i>
            <p class="mb-0">No hay reembolsos asociados.</p>
        </div>
    @endif
</section>
@endsection
