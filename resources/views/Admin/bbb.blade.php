<div class="form-page-header mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h2 font-display fw-bold text-cm-primary mb-1 titulo-main">
                {{ $modo === 'crear' ? 'Registrar función' : 'Editar función' }}
            </h1>
            <p class="text-muted mb-0 small">
                La programación valida la duración de la película y el margen de limpieza de la sala para evitar solapamientos.
            </p>
        </div>
        <a href="{{ route('admin.funciones.index') }}" class="btn admin-back-btn d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none">
            <span class="material-symbols-outlined fs-6" aria-hidden="true">arrow_back</span>
            <span>Volver al catálogo</span>
        </a>
    </div>
</div>

<a href="{{ route('admin.peliculas.index') }}" class="btn admin-back-btn d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none">
    <span class="material-symbols-outlined fs-6" aria-hidden="true">arrow_back</span>
    <span>Volver al catálogo</span>
</a>

<x-slot:action>
    <a href="{{ route('admin.funciones.create') }}" class="btn btn-primary">+ Registrar función</a>
</x-slot:action>
</x-admin.encabezado-pagina>

<div class="cm-input-group position-relative">
    <span class="material-symbols-outlined cm-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
    <input
        id="productos-busqueda"
        type="search"
        aria-label="Buscar películas por título"
        maxlength="100"
        class="form-control cm-field-filter ps-5"
        wire:model.live.debounce.300ms="q"
        placeholder="Buscar por ID, nombre o descripción…"
        autocomplete="off">
</div>