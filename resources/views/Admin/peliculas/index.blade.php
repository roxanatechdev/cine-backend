@extends('admin.layout')

@section('title', 'Gestión de películas')

@section('content')
{{-- Encabezado del catálogo --}}
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-2">
    <div>
        {{-- Breadcrumb con estilo limpio y acento en la sección activa --}}
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 text-uppercase fw-semibold tracking-wider" style="font-size: 0.75rem;">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">CinePrime</a>
                </li>
                <li class="breadcrumb-item text-danger active" aria-current="page">
                    Administración / Películas
                </li>
            </ol>
        </nav>

        {{-- Título Principal con tipografía del sistema --}}
        <h1 class="h2 text-white fw-bold mb-1 tracking-tight">
            Gestión de Películas
        </h1>

        {{-- Subtítulo descriptivo y profesional --}}
        <p class="text-text-white-50 small mb-0 max-w-2xl">
            Controla el ciclo de vida del catálogo, gestiona fichas técnicas, estado de publicación y sincronización de formatos para cartelera.
        </p>
    </div>

    {{-- Grupo de Acciones --}}
    <div class="d-flex align-items-center gap-2 align-self-stretch align-self-md-auto">
        {{-- Acción Secundaria: Exportar CSV --}}
        <a href="{{ route('admin.peliculas.export', request()->only(['q', 'estado', 'clasificacion'])) }}"
            class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-2 px-1 px-md-3 py-2 text-nowrap flex-grow-1 flex-md-grow-0"
            title="Exportar listado a CSV">
            <span class="material-symbols-outlined fs-5" aria-hidden="true">download</span>
            <span>Exportar CSV</span>
        </a>

        {{-- Acción Primaria CTA: Nueva Película --}}
        <a href="{{ route('admin.peliculas.create') }}"
            class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 px-1 px-md-3 py-2 text-nowrap flex-grow-1 flex-md-grow-0 fw-semibold"
            style="background-color: var(--cm-primary-container, #e50914); border-color: var(--cm-primary-container, #e50914);">
            <span class="material-symbols-outlined fs-5" aria-hidden="true">add</span>
            <span>Nueva película</span>
        </a>
    </div>
</div>

{{-- Indicadores principales del catálogo --}}
<section class="mb-4" aria-label="Indicadores del catálogo de películas">
    <div class="row g-2 g-sm-3">
        {{-- KPI 1: Películas Activas (Tono Verde) --}}
        <div class="col-6 col-md-6 col-xl-3">
            <article class="kpi-card p-3 p-lg-4 rounded-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <p class="kpi-title small mb-1 text-uppercase fw-semibold">Películas activas</p>
                        <p class="kpi-value fw-bold text-white mb-0">{{ number_format($kpis['activas']) }}</p>
                    </div>
                    <div class="kpi-icon-box rounded-2 p-2 bg-emerald-glow text-emerald">
                        <span class="material-symbols-outlined" style="font-size: 20px;">movie</span>
                    </div>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-1 text-emerald small mb-2" style="font-size: 0.78rem;">
                        <span class="fw-semibold">Publicadas en el catálogo</span>
                    </div>
                </div>
            </article>
        </div>

        {{-- KPI 2: Borradores Pendientes (Tono Ámbar/Naranja) --}}
        <div class="col-6 col-md-6 col-xl-3">
            <article class="kpi-card p-3 p-lg-4 rounded-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <p class="kpi-title small mb-1 text-uppercase fw-semibold">Borradores</p>
                        <p class="kpi-value fw-bold text-white mb-0">{{ number_format($kpis['borradores']) }}</p>
                    </div>
                    <div class="kpi-icon-box rounded-2 p-2 bg-amber-glow text-amber">
                        <span class="material-symbols-outlined" style="font-size: 20px;">edit_document</span>
                    </div>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-1 text-amber small mb-2" style="font-size: 0.78rem;">
                        <span class="fw-semibold">Aún no publicados</span>
                    </div>
                </div>
            </article>
        </div>

        {{-- KPI 3: Próximos Estrenos (Tono Celeste) --}}
        <div class="col-6 col-md-6 col-xl-3">
            <article class="kpi-card p-3 p-lg-4 rounded-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <p class="kpi-title small mb-1 text-uppercase fw-semibold">Próximos estrenos</p>
                        <p class="kpi-value fw-bold text-white mb-0">{{ number_format($kpis['proximos_estrenos']) }}</p>
                    </div>
                    <div class="kpi-icon-box rounded-2 p-2 bg-sky-glow text-sky">
                        <span class="material-symbols-outlined" style="font-size: 20px;">upcoming</span>
                    </div>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-1 text-sky small mb-2" style="font-size: 0.78rem;">
                        <span class="fw-semibold">Activas desde hoy</span>
                    </div>
                </div>
            </article>
        </div>

        {{-- KPI 4: Duración Promedio (Tono Celeste / Cyan) --}}
        <div class="col-6 col-md-6 col-xl-3">
            <article class="kpi-card p-3 p-lg-4 rounded-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <p class="kpi-title small mb-1 text-uppercase fw-semibold">Duración promedio</p>
                        <p class="kpi-value fw-bold text-white mb-0">
                            {{ $kpis['duracion_promedio'] > 0 ? number_format($kpis['duracion_promedio']) : '—' }}
                            @if ($kpis['duracion_promedio'] > 0)<span class="fs-6 text-muted fw-normal">min</span>@endif
                        </p>
                    </div>
                    <div class="kpi-icon-box rounded-2 p-2 bg-sky-glow text-sky">
                        <span class="material-symbols-outlined" style="font-size: 20px;">schedule</span>
                    </div>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-1 text-muted small mb-2" style="font-size: 0.78rem;">
                        <span>Excluye sin duración</span>
                    </div>
                </div>
            </article>
        </div>

    </div>
</section>

{{-- La clasificación funciona mejor como distribución que como KPI aislado. --}}
<section class="custom-card p-3 p-lg-4 mb-4 rounded-3" aria-labelledby="clasificacion-resumen-titulo">
    <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mb-3">
        <div>
            <h2 id="clasificacion-resumen-titulo" class="h5 mb-1">Distribución por clasificación</h2>
            <p class="muted small mb-0">Composición global del catálogo, independiente de los filtros.</p>
        </div>
        <span class="badge text-bg-secondary align-self-start">{{ number_format($totalClasificaciones ?? 0) }} películas</span>
    </div>

    @if (isset($totalClasificaciones) && $totalClasificaciones > 0 && !empty($clasificaciones))
    <div class="row g-3">
        @foreach ($clasificaciones as $item)
        @php
        // Soporte seguro tanto si $item es un objeto como un array
        $totalItem = is_object($item) ? ($item->total ?? 0) : ($item['total'] ?? 0);
        $nombreItem = is_object($item) ? ($item->nombre ?? 'Sin nombre') : ($item['nombre'] ?? 'Sin nombre');

        // Cálculo seguro del porcentaje
        $porcentaje = $totalClasificaciones > 0 ? round(($totalItem / $totalClasificaciones) * 100) : 0;
        @endphp
        <div class="col-12 col-md-6">
            <div class="d-flex justify-content-between gap-3 mb-1">
                <span class="small fw-semibold">{{ $nombreItem }}</span>
                <span class="fw-semibold text-small">{{ $totalItem }}<span class=" text-white-50 small"> · {{ $porcentaje }}%</span></span>
            </div>
            <div class="progress" role="progressbar" aria-label="{{ $nombreItem }}"
                aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100" style="height: 8px;">
                <div class="progress-bar" style="width: {{ $porcentaje }}%;"></div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p class="muted small mb-0">Todavía no hay películas registradas.</p>
    @endif
</section>



@if ($errors->any())
<div class="alert alert-danger mb-4" role="alert">
    <div class="fw-semibold mb-1">
        No se pudieron aplicar los filtros.
    </div>
    <p class="mb-0">
        Revisa los valores seleccionados e inténtalo nuevamente.
    </p>
</div>
@endif

<livewire:admin.peliculas.tabla-peliculas />

@endsection