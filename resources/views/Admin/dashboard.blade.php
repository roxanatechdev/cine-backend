@extends('admin.layout')
@section('title', 'Dashboard')
@section('content')

<!-- Encabezado del Dashboard -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <div>
        <div class="admin-eyebrow text-uppercase text-cm-brand-red fw-bold tracking-wider mb-1" style="font-size: 0.72rem; letter-spacing: 0.08em;">
            Resumen Operativo
        </div>
        <h1 class="admin-page-heading h3 fw-bold text-white mb-0 font-display tracking-wide" style="font-size: 2rem;">PANEL DE CONTROL</h1>
        <p class="text-cm-variant small mb-0 mt-1">Monitoreo en tiempo real de operaciones, taquilla y dulcería del complejo.</p>
    </div>

    <!-- Fecha Actual -->
    <div class="align-self-start align-self-sm-center px-3 py-2 rounded-3 bg-cm-surface-low border border-secondary border-opacity-10 text-white small d-flex align-items-center gap-2 shadow-sm">
        <span class="material-symbols-outlined fs-5 text-cm-brand-red" aria-hidden="true">today</span>
        <span class="fw-semibold">{{ now()->translatedFormat('l, d \d\e F Y') }}</span>
    </div>
</div>

<!-- Grilla de KPIs / Métricas (Tarjetas completas como enlace) -->
<div class="row g-2 g-sm-3 mb-4">

    <!-- KPI 1: Películas Activas -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ route('admin.peliculas.index') }}" class="admin-kpi-card text-decoration-none p-3 p-sm-4 rounded-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden border-0 d-block">
            <div class="d-flex justify-content-between align-items-center mb-2 mb-sm-3">
                <span class="admin-eyebrow text-uppercase text-cm-variant fw-bold fs-9 tracking-wider">Catálogo</span>
                <div class="kpi-icon-wrapper rounded-2 bg-cm-primary-container bg-opacity-20 text-cm-brand-red p-2 d-flex align-items-center justify-content-center">
                    <span class="material-symbols-outlined fs-5 icon-animated" aria-hidden="true">movie</span>
                </div>
            </div>
            <div>
                <div class="kpi-value text-white fw-bold font-display lh-1 mb-1" style="font-size: 2.25rem;">
                    {{ number_format($metricas['peliculas_activas'], 0, ',', '.') }}
                </div>
                <div class="kpi-label text-cm-variant fs-8 text-wrap">Películas en cartelera activa</div>
            </div>
        </a>
    </div>

    <!-- KPI 2: Próximas Funciones -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ route('admin.funciones.index') }}" class="admin-kpi-card text-decoration-none p-3 p-sm-4 rounded-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden border-0 d-block">
            <div class="d-flex justify-content-between align-items-center mb-2 mb-sm-3">
                <span class="admin-eyebrow text-uppercase text-cm-variant fw-bold fs-9 tracking-wider">Programación</span>
                <div class="kpi-icon-wrapper rounded-2 bg-cm-primary-container bg-opacity-20 text-cm-brand-red p-2 d-flex align-items-center justify-content-center">
                    <span class="material-symbols-outlined fs-5 icon-animated" aria-hidden="true">event</span>
                </div>
            </div>
            <div>
                <div class="kpi-value text-white fw-bold font-display lh-1 mb-1" style="font-size: 2.25rem;">
                    {{ number_format($metricas['funciones_proximas'], 0, ',', '.') }}
                </div>
                <div class="kpi-label text-cm-variant fs-8 text-wrap">Próximas funciones programadas</div>
            </div>
        </a>
    </div>

    <!-- KPI 3: Compras Confirmadas -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ route('admin.compras.index') }}" class="admin-kpi-card text-decoration-none p-3 p-sm-4 rounded-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden border-0 d-block">
            <div class="d-flex justify-content-between align-items-center mb-2 mb-sm-3">
                <span class="admin-eyebrow text-uppercase text-cm-variant fw-bold fs-9 tracking-wider">Ventas</span>
                <div class="kpi-icon-wrapper rounded-2 bg-cm-primary-container bg-opacity-20 text-cm-brand-red p-2 d-flex align-items-center justify-content-center">
                    <span class="material-symbols-outlined fs-5 icon-animated" aria-hidden="true">shopping_bag</span>
                </div>
            </div>
            <div>
                <div class="kpi-value text-white fw-bold font-display lh-1 mb-1" style="font-size: 2.25rem;">
                    {{ number_format($metricas['compras_confirmadas'], 0, ',', '.') }}
                </div>
                <div class="kpi-label text-cm-variant fs-8 text-wrap">Compras confirmadas</div>
            </div>
        </a>
    </div>

    <!-- KPI 4: Pagos Aprobados -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ route('admin.pagos.index') }}" class="admin-kpi-card text-decoration-none p-3 p-sm-4 rounded-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden border-0 d-block">
            <div class="d-flex justify-content-between align-items-center mb-2 mb-sm-3">
                <span class="admin-eyebrow text-uppercase text-cm-variant fw-bold fs-9 tracking-wider">Pagos</span>
                <div class="kpi-icon-wrapper rounded-2 bg-cm-primary-container bg-opacity-20 text-cm-brand-red p-2 d-flex align-items-center justify-content-center">
                    <span class="material-symbols-outlined fs-5 icon-animated" aria-hidden="true">payments</span>
                </div>
            </div>
            <div>
                <div class="kpi-value text-white fw-bold font-display lh-1 mb-1" style="font-size: 2.25rem;">
                    {{ number_format($metricas['pagos_aprobados'], 0, ',', '.') }}
                </div>
                <div class="kpi-label text-cm-variant fs-8 text-wrap">Transacciones de pago aprobadas</div>
            </div>
        </a>
    </div>

</div>

<div class="row g-4">
    <!-- SECCIÓN: PRÓXIMAS FUNCIONES -->
    <div class="col-12 py-md-4 table-custom" id="prox-funciones">
        <section class="admin-panel h-100 overflow-hidden border-0 shadow-sm rounded-3 px-2 px-lg-4 py-3" style="background-color: var(--cm-surface-container, #1d1f29) !important;">
            <!-- Cabecera de la sección -->
            <div class="d-flex justify-content-between align-items-center gap-3 py-3 px-2 px-lg-0">
                <div>
                    <h2 class="admin-section-title h5 text-white fw-bold mb-1 font-display tracking-wide" style="font-size: 1.35rem; letter-spacing: 1.5px;">
                        PRÓXIMAS FUNCIONES
                    </h2>
                    <p class="admin-section-subtitle small text-cm-variant mb-0">Proyecciones programadas según fecha de inicio.</p>
                </div>
                <a href="{{ route('admin.funciones.index') }}" class="btn btn-sm btn-outline-danger px-3 py-1 rounded-2 fs-8 fw-semibold text-decoration-none transition-all">
                    Ver todas
                </a>
            </div>

            @if($funciones->isEmpty())
            <div class="p-5 text-center">
                <span class="material-symbols-outlined text-cm-variant mb-2 opacity-50" style="font-size:48px" aria-hidden="true">event_busy</span>
                <p class="mb-1 fw-semibold text-white">No hay próximas funciones</p>
                <p class="small text-cm-variant mb-0">Cuando existan funciones futuras, aparecerán en esta lista.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table admin-table align-middle mb-0 text-white">
                    <thead>
                        <tr class="text-uppercase text-cm-variant fs-8 tracking-wider" style="background-color: #0c0e17 !important;">
                            <th class="ps-3 py-3 fw-bold">Película</th>
                            <th class="py-3 fw-bold">Sala</th>
                            <th class="py-3 fw-bold">Inicio</th>
                            <th class="pe-3 pe-md-5 py-3 text-end fw-bold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="border-0">
                        @foreach($funciones as $funcion)
                        <tr class="border-bottom border-secondary border-opacity-10 admin-table-row transition-all">
                            <!-- Película con Minicarátula / Placeholder -->
                            <td class="ps-3 py-2">
                                <div class="d-flex align-items-center gap-3">
                                    @php
                                    $multimedia = $funcion->pelicula?->multimedia?->first();
                                    $imagenPelicula = null;

                                    if ($multimedia && filled($multimedia->url)) {
                                    $urlMultimedia = trim($multimedia->url);
                                    if (filter_var($urlMultimedia, FILTER_VALIDATE_URL)) {
                                    $imagenPelicula = $urlMultimedia;
                                    } else {
                                    $ruta = ltrim($urlMultimedia, '/');
                                    $imagenPelicula = str_starts_with($ruta, 'storage/') ? asset($ruta) : asset('storage/' . $ruta);
                                    }
                                    }
                                    @endphp

                                    <div class="movie-poster-wrapper rounded overflow-hidden flex-shrink-0 bg-cm-surface-low border border-secondary border-opacity-10 position-relative" style="width: 38px; height: 54px;">
                                        @if($imagenPelicula)
                                        <img src="{{ $imagenPelicula }}"
                                            alt="{{ $multimedia->texto_alternativo ?: $funcion->pelicula?->titulo }}"
                                            class="w-100 h-100 object-fit-cover"
                                            loading="lazy"
                                            onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                                        <div class="poster-placeholder w-100 h-100 d-none d-flex align-items-center justify-content-center text-cm-variant bg-dark">
                                            <i class="bi bi-film fs-6"></i>
                                        </div>
                                        @else
                                        <div class="poster-placeholder w-100 h-100 d-flex align-items-center justify-content-center text-cm-variant bg-dark">
                                            <i class="bi bi-film fs-6"></i>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <span class="d-block text-white text-truncate fs-6-5 fw-medium mb-1">
                                            {{ $funcion->pelicula?->titulo ?? 'Película no disponible' }}
                                        </span>
                                        <small class="text-cm-variant d-block fs-8">
                                            {{ $funcion->pelicula?->duracion_minutos ? $funcion->pelicula->duracion_minutos . ' min' : 'CinePrime' }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <!-- Sala -->
                            <td class="py-2 fs-7 text-cm-on-surface fw-medium">
                                <span class="text-nowrap">{{ $funcion->sala?->nombre ?? 'Sala no disponible' }}</span>
                            </td>

                            <!-- Fecha y Hora de Inicio -->
                            <td class="py-2 text-nowrap fs-7">
                                <div class="fw-semibold text-white">
                                    {{ $funcion->inicio?->format('d/m/Y') ?? '—' }}
                                </div>
                                <small class="text-cm-brand-red fw-bold fs-7">
                                    {{ $funcion->inicio?->format('H:i') ?? '' }} hrs
                                </small>
                            </td>

                            <!-- Badge de Estado -->
                            <td class="pe-3 pe-md-4 py-2 text-end">
                                @php
                                $estadoClass = match($funcion->estado) {
                                'EN_CURSO' => 'bg-info bg-opacity-15 text-info border-info border-opacity-25',
                                'PROGRAMADA' => 'bg-success bg-opacity-15 border-success border-opacity-25',
                                'CANCELADA' => 'bg-danger bg-opacity-15 text-danger border-danger border-opacity-25',
                                default => 'bg-secondary bg-opacity-15 text-cm-variant border-secondary border-opacity-25'
                                };
                                @endphp
                                <span class="badge border rounded-pill px-2 py-1 fs-9 fw-semibold tracking-wider text-uppercase {{ $estadoClass }}">
                                    {{ str_replace('_', ' ', $funcion->estado) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </section>
    </div>

    <!-- SECCIÓN: COMPRAS RECIENTES -->
    <div class="col-12 table-custom" id="compras-recientes">
        <section class="admin-panel h-100 overflow-hidden border-0 shadow-sm rounded-3 px-2 px-lg-4 py-3" style="background-color: var(--cm-surface-container, #1d1f29) !important;">
            <!-- Cabecera de la sección -->
            <div class="d-flex justify-content-between align-items-center gap-3 py-3 px-2 px-lg-0">
                <div>
                    <h2 class="admin-section-title h5 text-white fw-bold mb-1 font-display tracking-wide" style="font-size: 1.35rem; letter-spacing: 1.5px;">
                        COMPRAS RECIENTES
                    </h2>
                    <p class="admin-section-subtitle small text-cm-variant mb-0">Últimos registros del historial comercial.</p>
                </div>
                <a href="{{ route('admin.compras.index') }}" class="btn btn-sm btn-outline-info px-3 py-1 rounded-2 fs-8 fw-semibold text-decoration-none transition-all">
                    Ver todas
                </a>
            </div>

            @if($comprasRecientes->isEmpty())
            <div class="p-5 text-center">
                <span class="material-symbols-outlined text-cm-variant mb-2 opacity-50" style="font-size:48px" aria-hidden="true">receipt_long</span>
                <p class="mb-1 fw-semibold text-white">Todavía no hay compras</p>
                <p class="small text-cm-variant mb-0">Las compras registradas se mostrarán en esta lista.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table admin-table align-middle mb-0 text-white">
                    <thead>
                        <tr class="text-uppercase text-cm-variant fs-8 tracking-wider" style="background-color: #0c0e17 !important;">
                            <th class="ps-3 ps-md-2 py-3 fw-bold" style="max-width: 110px;">N.° Orden</th>
                            <th class="py-3 fw-bold">Cliente</th>
                            <th class="py-3 fw-bold">Fecha y Hora</th>
                            <th class="py-3 fw-bold">Total</th>
                            <th class="pe-3 pe-md-4 py-3 text-end fw-bold" style="max-width: 210px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="border-0">
                        @foreach($comprasRecientes as $compra)
                        <tr class="border-bottom border-secondary border-opacity-10 admin-table-row transition-all">

                            <!-- N.° de Orden / ID -->
                            <td class="ps-3 ps-md-2 py-3" style="max-width: 90px;">
                                <a href="{{ route('admin.compras.show', $compra) }}" class="fw-bold text-info text-decoration-none font-monospace fs-6 hover-underline">
                                    #{{ $compra->id_compra }}
                                </a>
                            </td>

                            <!-- Cliente (Nombre y Correo) -->
                            <td class="py-3">
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-white fs-6-5 mb-0">
                                        {{ $compra->comprador?->nombre ?? 'Sin cliente registrado' }}
                                    </span>
                                    <small class="text-cm-variant fs-8">
                                        {{ $compra->comprador?->correo ?? '—' }}
                                    </small>
                                </div>
                            </td>

                            <!-- Fecha y Hora -->
                            <td class="py-3 text-nowrap fs-6-5">
                                <div class="fw-semibold text-white">
                                    {{ $compra->creada_en?->format('d/m/Y') ?? '—' }}
                                </div>
                                <small class="text-cm-variant fs-8">
                                    {{ $compra->creada_en?->format('H:i') ?? '' }} hrs
                                </small>
                            </td>

                            <!-- Total -->
                            <td class="py-3 text-nowrap fs-6-5 fw-bold text-white">
                                <span class="text-cm-variant font-monospace fs-8 me-1">{{ $compra->moneda ?? 'BOB' }}</span>
                                {{ number_format((float) $compra->total, 2, ',', '.') }}
                            </td>

                            <!-- Estado (5 variaciones completas) -->
                            <td class="pe-3 pe-md-4 py-3 text-end" style="max-width: 210px;">
                                @php
                                $badgeStyle = match($compra->estado) {
                                'CONFIRMADA' => 'bg-success bg-opacity-15 border-success border-opacity-25',
                                'INICIADA' => 'bg-info bg-opacity-15 border-info border-opacity-25',
                                'PENDIENTE_PAGO' => 'bg-warning bg-opacity-15 border-warning border-opacity-25',
                                'CANCELADA' => 'bg-danger bg-opacity-15 border-danger border-opacity-25',
                                'EXPIRADA' => 'bg-secondary bg-opacity-15 border-secondary border-opacity-25',
                                default => 'bg-secondary bg-opacity-15 border-secondary border-opacity-25'
                                };
                                @endphp
                                <span class="badge border rounded-pill px-2-5 py-1 fs-9 fw-semibold tracking-wider text-uppercase {{ $badgeStyle }}">
                                    {{ str_replace('_', ' ', $compra->estado) }}
                                </span>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </section>
    </div>
</div>
@endsection