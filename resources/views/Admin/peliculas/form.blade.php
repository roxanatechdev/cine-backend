@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Nueva película' : 'Editar película')

@section('content')
@php
$pestana = $pestana ?? 'informacion';
@endphp

<div class="mb-4">
    <!-- Header Principal Principal Responsive con Acción Integrada -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-2 py-md-2 rounded-4 bg-surface-container">

        <!-- Bloque Izquierdo: Título dinámico y Subtítulo -->
        <div class="d-flex flex-column">
            <h1 class="h2 font-display fw-bold text-cm-primary mb-1 titulo-main">
                {{ $modo === 'crear' ? 'Registrar nueva película' : 'Editar película' }}
            </h1>
            <p class="text-muted mb-0 small">
                {{ $modo === 'crear'
                    ? 'Completa los datos para dar de alta una nueva producción en el catálogo de CinePrime.'
                    : 'Actualiza la información, los detalles y la disponibilidad general de esta película.' }}
            </p>
        </div>

        <!-- Bloque Derecho: Badge de Estado + Botón Volver al Catálogo -->
        <div class="d-flex align-items-center flex-wrap gap-2 gap-md-3 flex-shrink-0">
            @if ($modo === 'editar' && isset($pelicula))
            <span class="badge rounded-pill px-3 py-2 fw-semibold
                {{ match ($pelicula->estado) {
                    'ACTIVA' => 'text-bg-success',
                    'BORRADOR' => 'text-bg-warning',
                    default => 'text-bg-secondary text-dark',
                } }}">
                {{ match ($pelicula->estado) {
                    'ACTIVA' => '● Película activa',
                    'BORRADOR' => '● Película en borrador',
                    default => '● Película inactiva',
                } }}
            </span>
            @endif
            <a href="{{ route('admin.peliculas.index') }}" class="btn admin-back-btn d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none">
                <span class="material-symbols-outlined fs-6" aria-hidden="true">arrow_back</span>
                <span>Volver al catálogo</span>
            </a>
        </div>

    </div>
</div>

{{-- Navegación del editor: Pestañas planas con indicador de línea inferior --}}
<nav class="mb-4" aria-label="Secciones del editor de película">
    <div class="editor-tabs-wrapper border-bottom border-secondary border-opacity-25 pb-0">
        <div class="nav editor-underline-tabs flex-nowrap overflow-x-auto gap-2 gap-md-4" role="tablist">

            {{-- Pestaña: Información principal (Siempre disponible) --}}
            <a class="nav-link editor-tab-item {{ $pestana === 'informacion' ? 'active' : '' }}"
                wire:navigate
                href="{{ $modo === 'editar' 
                   ? route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'informacion']) 
                   : '#informacion-principal' }}"
                @if ($pestana==='informacion' ) aria-current="page" @endif>
                <span class="material-symbols-outlined tab-icon" aria-hidden="true">info</span>
                <span class="tab-label">Información principal</span>
            </a>

            @if ($modo === 'editar')
            {{-- Pestañas habilitadas en Modo Edición --}}
            <a class="nav-link editor-tab-item {{ $pestana === 'creditos' ? 'active' : '' }}"
                wire:navigate
                href="{{ route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos']) }}"
                @if ($pestana==='creditos' ) aria-current="page" @endif>
                <span class="material-symbols-outlined tab-icon" aria-hidden="true">group</span>
                <span class="tab-label">Créditos</span>
            </a>

            <a class="nav-link editor-tab-item {{ $pestana === 'multimedia' ? 'active' : '' }}"
                wire:navigate
                href="{{ route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'multimedia']) }}"
                @if ($pestana==='multimedia' ) aria-current="page" @endif>
                <span class="material-symbols-outlined tab-icon" aria-hidden="true">perm_media</span>
                <span class="tab-label">Multimedia</span>
            </a>

            <a class="nav-link editor-tab-item {{ $pestana === 'produccion' ? 'active' : '' }}"
                wire:navigate
                href="{{ route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'produccion']) }}"
                @if ($pestana==='produccion' ) aria-current="page" @endif>
                <span class="material-symbols-outlined tab-icon" aria-hidden="true">factory</span>
                <span class="tab-label">Producción</span>
            </a>
            @else
            {{-- Pestañas bloqueadas en Modo Creación --}}
            <button class="nav-link editor-tab-item disabled-tab" type="button" disabled aria-disabled="true" title="Guarda primero la película como borrador">
                <span class="material-symbols-outlined tab-icon text-muted" aria-hidden="true">lock</span>
                <span class="tab-label">Créditos</span>
            </button>
            <button class="nav-link editor-tab-item disabled-tab" type="button" disabled aria-disabled="true" title="Guarda primero la película como borrador">
                <span class="material-symbols-outlined tab-icon text-muted" aria-hidden="true">lock</span>
                <span class="tab-label">Multimedia</span>
            </button>
            <button class="nav-link editor-tab-item disabled-tab" type="button" disabled aria-disabled="true" title="Guarda primero la película como borrador">
                <span class="material-symbols-outlined tab-icon text-muted" aria-hidden="true">lock</span>
                <span class="tab-label">Producción</span>
            </button>
            @endif
        </div>
    </div>

    @if ($modo === 'crear')
    <div class="d-flex align-items-center gap-2 mt-2 text-muted small">
        <span class="material-symbols-outlined fs-6 text-warning" aria-hidden="true">info</span>
        <span>Guarda la información principal para crear el borrador y habilitar Créditos, Multimedia y Producción.</span>
    </div>
    @endif
</nav>

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <h2 class="h6 fw-bold mb-2">No se pudo guardar la película</h2>
    <p class="mb-2">Revisa los siguientes campos:</p>
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="rounded-3 overflow-hidden">
    @if ($pestana === 'informacion')
    {{-- Contenedor Principal del Formulario con la clase form-cm --}}
    <div class="form-cm">
        <form id="form-informacion-pelicula" method="POST"
            action="{{ $modo === 'crear' ? route('admin.peliculas.store') : route('admin.peliculas.update', $pelicula) }}">
            @csrf
            @if ($modo === 'editar')
            @method('PUT')
            @endif

            {{-- Sección 1: Información Principal --}}
            <section id="informacion-principal" class="form-cm__section">
                <div class="mb-4">
                    <h2 class="form-cm__title">Información Principal</h2>
                    <p class="form-cm__subtitle">Datos esenciales para identificar la obra en cartelera.</p>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-cm__group">
                            <label for="titulo" class="form-cm__label">
                                <span class="material-symbols-outlined form-cm__icon">title</span>
                                <span>Título de la película</span> <span class="required">*</span>
                            </label>
                            <input id="titulo" name="titulo" type="text" maxlength="150" required autofocus
                                class="form-cm__control @error('titulo') is-invalid @enderror"
                                value="{{ old('titulo', $pelicula->titulo) }}"
                                placeholder="Ej. El Reino de Cristal"
                                aria-describedby="titulo-ayuda">
                            <div id="titulo-ayuda" class="form-cm__text">Máximo 150 caracteres.</div>
                            @error('titulo')
                            <div class="form-cm__feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-cm__group">
                            <label for="sinopsis" class="form-cm__label">
                                <span class="material-symbols-outlined form-cm__icon">notes</span>
                                <span>Sinopsis / Argumento</span> <span class="required">*</span>
                            </label>
                            <textarea id="sinopsis" name="sinopsis" rows="4" required
                                class="form-cm__control @error('sinopsis') is-invalid @enderror"
                                placeholder="Describe brevemente la trama principal..."
                                aria-describedby="sinopsis-ayuda">{{ old('sinopsis', $pelicula->sinopsis) }}</textarea>
                            <div id="sinopsis-ayuda" class="form-cm__text">Resumen redactado para lectura rápida en cartelera.</div>
                            @error('sinopsis')
                            <div class="form-cm__feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- Sección 2: Detalles Técnicos y Clasificación --}}
            <section class="form-cm__section">
                <div class="mb-4">
                    <h2 class="form-cm__title">Detalles Técnicos</h2>
                    <p class="form-cm__subtitle">Parámetros de catalogación y restricciones.</p>
                </div>

                <div class="row g-3">
                    {{-- Género Principal (Dropdown personalizado) --}}
                    <div class="col-12 col-md-6">
                        <div class="form-cm__group">
                            <label for="id_genero_principal" class="form-cm__label">
                                <span class="material-symbols-outlined form-cm__icon">category</span>
                                <span>Género principal</span>
                            </label>

                            <x-form.select
                                name="id_genero_principal"
                                :options="['' => 'Sin género asignado'] + $generos->pluck('nombre', 'id_genero')->all()"
                                :selected="$pelicula->id_genero_principal"
                                placeholder="Sin género asignado" />

                            <div class="form-cm__text">Requerido para activar la película en cartelera.</div>
                            @error('id_genero_principal')
                            <div class="form-cm__feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Idioma Original --}}
                    <div class="col-12 col-md-6">
                        <div class="form-cm__group">
                            <label for="idioma_original_codigo" class="form-cm__label">
                                <span class="material-symbols-outlined form-cm__icon">language</span>
                                <span>Idioma original</span>
                            </label>

                            <x-form.select
                                name="idioma_original_codigo"
                                :options="['' => 'Sin idioma asignado'] + $idiomas->pluck('nombre', 'codigo')->all()"
                                :selected="$pelicula->idioma_original_codigo"
                                placeholder="Sin idioma asignado" />

                            <div class="form-cm__text">Idioma base para subtitulado y doblaje.</div>
                            @error('idioma_original_codigo')
                            <div class="form-cm__feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Clasificación --}}
                    <div class="col-12 col-md-6">
                        <div class="form-cm__group">
                            <label for="clasificacion" class="form-cm__label">
                                <span class="material-symbols-outlined form-cm__icon">verified_user</span>
                                <span>Clasificación por edades</span>
                            </label>

                            <x-form.select
                                name="clasificacion"
                                :options="[
                '' => 'Sin clasificación',
                'G' => 'G — Audiencia general',
                'PG' => 'PG — Orientación parental sugerida',
                'PG-13' => 'PG-13 — Menores de 13 con precaución',
                'R' => 'R — Restringida',
                'NC-17' => 'NC-17 — Solo adultos',
            ]"
                                :selected="$pelicula->clasificacion"
                                placeholder="Sin clasificación" />

                            @error('clasificacion')
                            <div class="form-cm__feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Duración --}}
                    <div class="col-12 col-md-6">
                        <div class="form-cm__group">
                            <label for="duracion_minutos" class="form-cm__label">
                                <span class="material-symbols-outlined form-cm__icon">schedule</span>
                                <span>Duración (minutos)</span>
                            </label>
                            <input id="duracion_minutos" name="duracion_minutos" type="number" min="1" max="65535" step="1"
                                class="form-cm__control @error('duracion_minutos') is-invalid @enderror"
                                value="{{ old('duracion_minutos', $pelicula->duracion_minutos) }}"
                                placeholder="Ej. 118">
                            @error('duracion_minutos')
                            <div class="form-cm__feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Fecha de Estreno --}}
                    <div class="col-12 col-md-6">
                        <div class="form-cm__group">
                            <label for="fecha_estreno" class="form-cm__label">
                                <span class="material-symbols-outlined form-cm__icon">calendar_today</span>
                                <span>Fecha de estreno</span>
                            </label>
                            <input id="fecha_estreno" name="fecha_estreno" type="date"
                                class="form-cm__control @error('fecha_estreno') is-invalid @enderror"
                                value="{{ old('fecha_estreno', $pelicula->fecha_estreno?->format('Y-m-d')) }}">
                            @error('fecha_estreno')
                            <div class="form-cm__feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- Sección 3: Disponibilidad (Solo en modo edición) --}}
            @if ($modo === 'editar')
            <section class="form-cm__section">
                <div class="mb-3">
                    <h2 class="form-cm__title">Disponibilidad</h2>
                    <p class="form-cm__subtitle">Control de visibilidad de la película en el sistema.</p>
                </div>

                {{-- Estado actual --}}
                <div class="form-cm__group mb-0">
                    <label for="estado" class="form-cm__label">
                        <span class="material-symbols-outlined form-cm__icon">visibility</span>
                        <span>Estado actual</span>
                    </label>

                    <x-form.select
                        name="estado"
                        :options="[
            'BORRADOR' => 'Borrador (Oculto)',
            'ACTIVA' => 'Activa (En cartelera)',
            'INACTIVA' => 'Inactiva (Archivada)',
        ]"
                        :selected="$pelicula->estado"
                        placeholder="Seleccionar estado" />

                    @error('estado')
                    <div class="form-cm__feedback">{{ $message }}</div>
                    @enderror
                </div>
            </section>
            @else
            <input type="hidden" name="estado" value="BORRADOR">
            @endif

            {{-- Pie de Formulario / Acciones --}}
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 p-3 p-md-4" style="background-color: rgba(0,0,0,0.15);">
                <p class="text-muted small mb-0">
                    <span class="text-danger">*</span> Campos obligatorios para registro inicial.
                </p>

                <div class="d-flex flex-column flex-sm-row gap-2">
                    <a href="{{ route('admin.peliculas.index') }}" class="btn btn-outline-light order-2 order-sm-1 px-4 py-2">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary order-1 order-sm-2 px-4 py-2 fw-semibold" style="background-color: var(--cm-primary); border: none;">
                        {{ $modo === 'crear' ? 'Guardar película' : 'Guardar cambios' }}
                    </button>
                </div>
            </div>
        </form>
        @push('scripts')
        <script>
            // Limpia el backdrop de Bootstrap antes de navegar con Livewire.
            document.addEventListener('livewire:navigating', () => {
                document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
                document.querySelectorAll('.modal.show').forEach((modal) => {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    modal.removeAttribute('aria-modal');
                    modal.removeAttribute('role');
                });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            });

            (() => {
                const formulario = document.getElementById(
                    'form-informacion-pelicula'
                );

                if (!formulario) return;

                formulario.addEventListener('click', (event) => {
                    const opcion = event.target.closest(
                        '[data-dropdown-option]'
                    );

                    if (!opcion || !formulario.contains(opcion)) return;

                    const menu = opcion.closest('.dropdown-menu');
                    const contenedor = menu?.closest('.dropdown');

                    if (!contenedor) return;

                    const campo = contenedor.querySelector(
                        'input[type="hidden"]'
                    );

                    const boton = contenedor.querySelector(
                        '[data-bs-toggle="dropdown"]'
                    );

                    const etiqueta = boton?.querySelector(
                        '[data-dropdown-label]'
                    );

                    if (!campo || !boton || !etiqueta) return;

                    campo.value = opcion.dataset.value ?? '';
                    etiqueta.textContent = opcion.textContent.trim();

                    menu.querySelectorAll('[data-dropdown-option]')
                        .forEach((elemento) => {
                            elemento.classList.toggle(
                                'active',
                                elemento === opcion
                            );

                            if (elemento === opcion) {
                                elemento.setAttribute(
                                    'aria-selected',
                                    'true'
                                );
                            } else {
                                elemento.removeAttribute('aria-selected');
                            }
                        });

                    campo.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));

                    if (window.bootstrap?.Dropdown) {
                        window.bootstrap.Dropdown
                            .getOrCreateInstance(boton)
                            .hide();
                    }
                });
            })();
        </script>
        @endpush
    </div>
    @elseif ($pestana === 'creditos')
    <section class="p-3 p-md-4 custom-card" aria-labelledby="creditos-titulo">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h2 id="creditos-titulo" class="h3 text-spacing text-white mb-1 font-display tracking-wide">Créditos de la película</h2>
                <p class="admin-section-subtitle small text-cm-variant mb-0">Asigna personas y define su participación en esta película.</p>
            </div>
            @if ($creditoModo === 'crear')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAsignarCredito">+ Asignar crédito</button>
            @else
            <a class="btn btn-primary" href="{{ route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos', 'credito_accion' => 'crear']) }}">+ Asignar crédito</a>
            @endif
        </div>

        @if ($creditos->count())
        <div class="table-responsive mb-4 table-custom rounded-3 table-custom-2">
            <table class="table admin-table align-middle mb-0 text-whit">
                <caption class="visually-hidden">Personas y roles acreditados en {{ $pelicula->titulo }}</caption>
                <thead>
                    <tr class="encabezados">
                        <th scope="col">Persona</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Personaje</th>
                        <th scope="col">Orden</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($creditos as $creditoFila)
                    <tr>
                        <td>{{ $creditoFila->persona?->nombre_completo ?? 'Persona no disponible' }}</td>
                        <td>{{ $creditoFila->rol }}</td>
                        <td>{{ $creditoFila->personaje ?: '—' }}</td>
                        <td>{{ $creditoFila->orden }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <a href="{{ route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos', 'credito_accion' => 'editar', 'credito_persona' => $creditoFila->id_persona, 'credito_rol' => $creditoFila->rol]) }}" aria-label="Editar crédito de {{ $creditoFila->persona?->nombre_completo }} como {{ $creditoFila->rol }}" class="btn admin-action-icon"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Editar">
                                    <span class="material-symbols-outlined" aria-hidden="true">edit</span></a>
                                <form method="POST" action="{{ route('admin.peliculas.creditos.destroy', [$pelicula, $creditoFila->id_persona, $creditoFila->rol]) }}" onsubmit="return confirm('¿Eliminar este crédito de la película?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" aria-label="Eliminar crédito de {{ $creditoFila->persona?->nombre_completo }} como {{ $creditoFila->rol }}"
                                        class="btn admin-action-icon admin-action-icon--danger"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Eliminar">
                                        <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mb-4">{{ $creditos->links() }}</div>
        @else
        <div class="p-4 text-center border border-secondary rounded-3 mb-4 ">
            <p class="mb-2">Esta película todavía no tiene créditos.</p>
            <p class="muted small mb-0">Asigna una persona para registrar su participación.</p>
        </div>
        @endif

        @if ($creditoModo === 'editar')
        <div class="form-cm mt-4" aria-labelledby="form-credito-titulo">
            <form method="POST" action="{{ route('admin.peliculas.creditos.update', [$pelicula, $credito->id_persona, $credito->rol]) }}">
                @csrf
                @method('PUT')
                <section class="form-cm__section">
                    <div class="mb-4">
                        <h3 id="form-credito-titulo" class="form-cm__title">Editar crédito</h3>
                        <p class="form-cm__subtitle">Actualiza el rol, el personaje y el orden de aparición de esta persona.</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="form-cm__group">
                                <label for="credito_buscar_persona" class="form-cm__label">Persona</label>
                                <input id="credito_buscar_persona" type="text" class="form-cm__control" value="{{ $credito->persona?->nombre_completo }}" readonly aria-describedby="credito-persona-fija-ayuda">
                                <div id="credito-persona-fija-ayuda" class="form-cm__text">La persona del crédito no se cambia desde esta edición.</div>
                                <input id="credito_id_persona" type="hidden" name="id_persona" value="{{ old('id_persona', $credito->id_persona) }}">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-cm__group">
                                <label class="form-cm__label" for="credito_rol_toggle">Rol <span class="required" aria-hidden="true">*</span></label>
                                <x-form.select name="rol" id="credito_rol" :options="array_combine(['DIRECTOR', 'ACTOR', 'GUIONISTA', 'PRODUCTOR', 'COMPOSITOR'], ['DIRECTOR', 'ACTOR', 'GUIONISTA', 'PRODUCTOR', 'COMPOSITOR'])" :selected="$credito->rol" placeholder="Selecciona un rol" />
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <x-form.field name="personaje" label="Personaje" id="credito_personaje" :show-error="false" help="Opcional. Máximo 150 caracteres.">
                                <x-form.input name="personaje" id="credito_personaje" :value="$credito->personaje ?? ''" maxlength="150" placeholder="Nombre del personaje, si corresponde" aria-describedby="credito_personaje-ayuda" />
                            </x-form.field>
                        </div>
                        <div class="col-12 col-md-6">
                            <x-form.field name="orden" label="Orden de aparición" id="credito_orden" :show-error="false" help="Número entero entre 0 y 65535.">
                                <x-form.input name="orden" id="credito_orden" type="number" :value="$credito->orden ?? 0" min="0" max="65535" step="1" aria-describedby="credito_orden-ayuda" />
                            </x-form.field>
                        </div>
                    </div>
                    <x-form.actions>
                        <a class="btn btn-outline-light" href="{{ route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos']) }}">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </x-form.actions>
                </section>
            </form>
        </div>
        @endif
    </section>

    @if ($creditoModo === 'crear')
    <div class="modal fade" id="modalAsignarCredito" tabindex="-1" aria-labelledby="modalAsignarCreditoTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content custom-card border-0 shadow-lg">

                <!-- Cabecera Rediseñada -->
                <div class="modal-header border-bottom border-secondary border-opacity-10 pb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="modal-icon-wrapper bg-danger bg-opacity-10 p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <span class="material-symbols-outlined fs-4">person_add</span>
                        </div>
                        <div>
                            <h2 class="modal-title fs-5 fw-semibold text-white mb-1" id="modalAsignarCreditoTitulo">Asignar crédito a la película</h2>
                            <p class="text-muted small mb-0">Busca una persona existente o regístrala rápidamente sin salir de esta ventana.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body pt-4">
                    <!-- Pestañas Modernas (Estilo Píldoras / Tabs flotantes) -->
                    <ul class="nav nav-pills custom-modal-tabs mb-4 p-1 rounded-3" id="pestanasCredito" role="tablist">
                        <li class="nav-item flex-fill text-center" role="presentation">
                            <button class="nav-link active w-100 py-2 d-flex align-items-center justify-content-center gap-2" id="tab-seleccionar-persona" data-bs-toggle="tab" data-bs-target="#panel-seleccionar-persona" type="button" role="tab" aria-controls="panel-seleccionar-persona" aria-selected="true">
                                <span class="material-symbols-outlined fs-5">search</span>
                                Seleccionar existente
                            </button>
                        </li>
                        <li class="nav-item flex-fill text-center" role="presentation">
                            <button class="nav-link w-100 py-2 d-flex align-items-center justify-content-center gap-2" id="tab-registrar-persona" data-bs-toggle="tab" data-bs-target="#panel-registrar-persona" type="button" role="tab" aria-controls="panel-registrar-persona" aria-selected="false">
                                <span class="material-symbols-outlined fs-5">person_add</span>
                                Registrar nueva
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Panel 1: Seleccionar Persona -->
                        <div class="tab-pane fade show active" id="panel-seleccionar-persona" role="tabpanel" aria-labelledby="tab-seleccionar-persona" tabindex="0">
                            <form id="form-credito-modal" method="POST" action="{{ route('admin.peliculas.creditos.store', $pelicula) }}">
                                @csrf
                                <div class="mb-3 position-relative">
                                    <label for="credito_buscar_persona" class="form-cm__label">Buscar persona <span class="text-danger">*</span></label>
                                    <div class="input-group-custom position-relative">
                                        <input id="credito_buscar_persona" type="search" autocomplete="off" class="form-cm__control @error('id_persona') is-invalid @enderror" placeholder="Escribe al menos 2 caracteres (nombre o alias)" aria-describedby="credito-persona-ayuda credito-persona-estado" value="">
                                    </div>
                                    <input id="credito_id_persona" type="hidden" name="id_persona" value="{{ old('id_persona', '') }}">

                                    <!-- Lista de resultados del buscador rediseñada -->
                                    <div id="credito-persona-resultados" class="custom-search-results list-group shadow-lg mt-1" role="listbox" aria-label="Coincidencias de personas" hidden></div>

                                    <div id="credito-persona-estado" class="form-cm__text mt-1" role="status" aria-live="polite"></div>
                                    @error('id_persona') <div class="form-cm__feedback" role="alert">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-cm__label" for="credito_rol">Rol <span class="text-danger">*</span></label>
                                    <x-form.select name="rol" id="credito_rol" :options="array_combine(['DIRECTOR', 'ACTOR', 'GUIONISTA', 'PRODUCTOR', 'COMPOSITOR'], ['DIRECTOR', 'ACTOR', 'GUIONISTA', 'PRODUCTOR', 'COMPOSITOR'])" :selected="old('rol', '')" placeholder="Selecciona un rol" />
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <x-form.field name="personaje" label="Personaje" id="credito_personaje" :show-error="false" help="Opcional. Máximo 150 caracteres.">
                                            <x-form.input name="personaje" id="credito_personaje" :value="''" maxlength="150" placeholder="Nombre del personaje, si corresponde" aria-describedby="credito_personaje-ayuda" />
                                        </x-form.field>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-form.field name="orden" label="Orden de aparición" id="credito_orden" :show-error="false" help="Número entero entre 0 y 65535.">
                                            <x-form.input name="orden" id="credito_orden" type="number" :value="0" min="0" max="65535" step="1" aria-describedby="credito_orden-ayuda" />
                                        </x-form.field>
                                    </div>
                                </div>

                                <x-form.actions>
                                    <button type="button" class="btn btn-outline-light px-4" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary px-4">Asignar crédito</button>
                                </x-form.actions>
                            </form>
                        </div>

                        <!-- Panel 2: Registrar Persona -->
                        <div class="tab-pane fade" id="panel-registrar-persona" role="tabpanel" aria-labelledby="tab-registrar-persona" tabindex="0">
                            <form id="form-persona-rapida" method="POST" action="{{ route('admin.peliculas.creditos.personas.crear-rapido', $pelicula) }}">
                                @csrf
                                <div class="alert alert-custom-info py-2 px-3 mb-3 small rounded-3 border-0 bg-secondary bg-opacity-10 text-muted">
                                    <span class="material-symbols-outlined align-middle fs-6 me-1 text-info">info</span>
                                    La persona y su crédito se guardarán juntos. Si ya existe en la base de datos, búscala en la pestaña anterior.
                                </div>

                                <div class="mb-3">
                                    <x-form.field name="nombre_completo" label="Nombre completo" id="persona_rapida_nombre" required>
                                        <x-form.input name="nombre_completo" id="persona_rapida_nombre" maxlength="180" required autocomplete="name" placeholder="Ej. Christopher Nolan" />
                                    </x-form.field>
                                </div>

                                <div class="mb-3">
                                    <x-form.field name="nombre_busqueda" label="Nombre alternativo / Alias" id="persona_rapida_busqueda">
                                        <x-form.input name="nombre_busqueda" id="persona_rapida_busqueda" maxlength="180" placeholder="Ej. Chris Nolan" />
                                    </x-form.field>
                                </div>

                                <div class="form-cm__group mb-3">
                                    <label class="form-cm__label" for="persona_rapida_rol_toggle">Rol en la película <span class="text-danger">*</span></label>
                                    <x-form.select name="rol" id="persona_rapida_rol" :options="array_combine(['DIRECTOR', 'ACTOR', 'GUIONISTA', 'PRODUCTOR', 'COMPOSITOR'], ['DIRECTOR', 'ACTOR', 'GUIONISTA', 'PRODUCTOR', 'COMPOSITOR'])" :selected="old('rol', '')" placeholder="Selecciona un rol" />
                                </div>

                                <div class="mb-3">
                                    <x-form.field name="url_foto" label="URL de fotografía (opcional)" id="persona_rapida_foto" help="Debe ser una URL HTTPS (JPG, PNG, WEBP o AVIF).">
                                        <x-form.input name="url_foto" id="persona_rapida_foto" type="url" maxlength="255" placeholder="https://ejemplo.com/persona.jpg" aria-describedby="persona_rapida_foto-ayuda" />
                                    </x-form.field>
                                </div>

                                <div id="persona-rapida-error" class="alert alert-danger py-2" role="alert" hidden></div>

                                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-secondary border-opacity-10">
                                    <button type="button" class="btn btn-outline-light px-4" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary px-4" id="persona-rapida-guardar">Registrar y asignar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @elseif ($pestana === 'multimedia')
    <section class="p-3 p-md-4" aria-labelledby="multimedia-editor-titulo">
        <x-admin.encabezado-pagina
            titulo="Multimedia de la película"
            descripcion="Administra pósteres, imágenes, tráileres y vídeos sin salir del editor de la película.">
            <x-slot:action>
                <a href="{{ route('admin.peliculas.multimedia.create', $pelicula) }}" class="btn btn-primary">+ Añadir recurso</a>
            </x-slot:action>
        </x-admin.encabezado-pagina>
        @include('admin.multimedia._listado')
    </section>
    @else
    <section class="p-3 p-md-4" aria-labelledby="produccion-editor-titulo">
        <x-admin.encabezado-pagina
            titulo="Países de producción"
            descripcion="Busca países existentes por nombre o código, asígnalos o registra uno nuevo sin salir del editor.">
            <x-slot:action>
                <div class="pt-3 mb-4">
                    <button type="button" id="pais-mostrar-crear" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearPais">+ Registrar país nuevo</button>
                </div>
            </x-slot:action>
        </x-admin.encabezado-pagina>
        <x-admin.tabla.contenedor class="admin-table-card">
            <x-admin.tabla.filtros tag="div" class="admin-table-filters px-3 pt-3 pb-0">
                <div class="mb-4">
                    <label for="pais_buscar" class="form-label">Buscar país</label>
                    <input id="pais_buscar" type="search" class="form-control" autocomplete="off" placeholder="Escribe al menos 2 caracteres del nombre o código" aria-describedby="pais-buscar-ayuda pais-buscar-estado">
                    <div id="pais-buscar-ayuda" class="form-text muted">Ejemplos: Chile, Argentina, CL, AR.</div>
                    <div id="pais-resultados" class="list-group mt-2 custom-search-results custom-search-results-page" role="listbox" aria-label="Resultados de países" hidden></div>
                    <div id="pais-buscar-estado" class="form-text" role="status" aria-live="polite"></div>
                </div>
            </x-admin.tabla.filtros>
            <div class="modal fade" id="modalCrearPais" tabindex="-1" aria-labelledby="modalCrearPaisTitulo" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content custom-card border-0 shadow-lg">
                        <form id="pais-crear-form" action="{{ route('admin.peliculas.paises.crear-rapido', $pelicula) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <div>
                                    <h2 class="modal-title fs-5" id="modalCrearPaisTitulo">Registrar país de producción</h2>
                                    <p class="muted small mb-0">El nuevo país se asociará automáticamente a esta película.</p>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="pais_nuevo_codigo" class="form-label">Código ISO alfa-2 <span class="text-danger">*</span></label>
                                        <input id="pais_nuevo_codigo" name="codigo" type="text" maxlength="2" minlength="2" pattern="[A-Za-z]{2}" required class="form-control" placeholder="CL" autocomplete="off">
                                        <div class="form-text muted">Dos letras; se guardará en mayúsculas.</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="pais_nuevo_nombre" class="form-label">Nombre del país <span class="text-danger">*</span></label>
                                        <input id="pais_nuevo_nombre" name="nombre" type="text" maxlength="100" required class="form-control" placeholder="Chile">
                                    </div>
                                </div>
                                <div id="pais-crear-error" class="alert alert-danger py-2 mt-3" role="alert" hidden></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" id="pais-cancelar-crear" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" id="pais-crear-guardar" class="btn btn-primary">Registrar y asignar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="border-top border-secondary">
                @if ($paisesProduccion->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">País</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paisesProduccion as $paisProduccion)
                            <tr>
                                <td>{{ $paisProduccion->codigo }}</td>
                                <td>{{ $paisProduccion->nombre }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.peliculas.paises.destroy', [$pelicula, $paisProduccion->codigo]) }}"
                                        class="d-inline"
                                        onsubmit="return confirm('¿Deseas quitar este país de la película?')">@csrf @method('DELETE')
                                        <button type="submit" class="btn admin-action-icon admin-action-icon--danger"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Eliminar"
                                            aria-label="Eliminar función">
                                            <span class="material-symbols-outlined" aria-hidden="true">delete</span></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="admin-table-empty">Esta película todavía no tiene países asignados.</p>
                @endif
            </div>
        </x-admin.tabla.contenedor>
    </section>
    @endif
</div>

@if ($pestana === 'creditos' && $creditoModo)
@push('scripts')
<script>
    (() => {
        const input = document.getElementById('credito_buscar_persona');
        const hidden = document.getElementById('credito_id_persona');
        const resultados = document.getElementById('credito-persona-resultados');
        const estado = document.getElementById('credito-persona-estado');
        if (!input || !hidden || !resultados || !estado) return;

        const urlBuscar = @json(route('admin.peliculas.creditos.personas.buscar', $pelicula));
        let temporizador = null;
        let controlador = null;

        const limpiarResultados = () => {
            resultados.replaceChildren();
            resultados.hidden = true;
        };

        const seleccionar = (persona) => {
            hidden.value = String(persona.id);
            input.value = persona.nombre;
            estado.textContent = 'Persona seleccionada: ' + persona.nombre;
            limpiarResultados();
        };

        const pintarResultados = (personas) => {
            limpiarResultados();
            if (!personas.length) {
                estado.textContent = 'No se encontraron coincidencias. Puedes registrar una persona nueva.';
                return;
            }
            personas.forEach(persona => {
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'list-group-item list-group-item-action';
                boton.setAttribute('role', 'option');
                boton.textContent = persona.nombre + (persona.nombre_busqueda ? ' · ' + persona.nombre_busqueda : '');
                boton.addEventListener('click', () => seleccionar(persona));
                resultados.appendChild(boton);
            });
            resultados.hidden = false;
            estado.textContent = personas.length + ' coincidencia(s). Selecciona una persona.';
        };

        input.addEventListener('input', () => {
            hidden.value = '';
            estado.textContent = '';
            clearTimeout(temporizador);
            if (controlador) controlador.abort();
            const termino = input.value.trim();
            limpiarResultados();
            if (termino.length < 2) {
                estado.textContent = termino.length ? 'Escribe al menos 2 caracteres.' : '';
                return;
            }
            estado.textContent = 'Buscando…';
            temporizador = setTimeout(async () => {
                controlador = new AbortController();
                try {
                    const url = new URL(urlBuscar, window.location.origin);
                    url.searchParams.set('q', termino);
                    const respuesta = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        },
                        signal: controlador.signal
                    });
                    if (!respuesta.ok) throw new Error('No se pudo buscar personas. Inténtalo de nuevo.');
                    const datos = await respuesta.json();
                    pintarResultados(datos.resultados || []);
                } catch (error) {
                    if (error.name !== 'AbortError') estado.textContent = error.message || 'Error al buscar personas.';
                }
            }, 250);
        });

        const formularioRapido = document.getElementById('form-persona-rapida');
        const modalRapido = document.getElementById('modalAsignarCredito');
        const rolPrincipal = document.getElementById('credito_rol');
        const rolRapido = document.getElementById('persona_rapida_rol');
        const nombreRapido = document.getElementById('persona_rapida_nombre');

        if (formularioRapido) {
            modalRapido?.addEventListener('show.bs.modal', () => {
                // Prellena el nombre desde la búsqueda y reutiliza el rol elegido en el formulario principal.
                if (nombreRapido && !nombreRapido.value.trim()) nombreRapido.value = input.value.trim();
                if (rolRapido && rolPrincipal?.value) rolRapido.value = rolPrincipal.value;
            });

            const actualizarEtiquetaRol = (campo) => {
                if (!campo) return;
                const contenedor = campo.closest('.form-cm__dropdown');
                const opcion = contenedor?.querySelector(`[data-dropdown-option][data-value="${CSS.escape(campo.value)}"]`);
                const etiqueta = contenedor?.querySelector('[data-dropdown-label]');
                if (opcion && etiqueta) etiqueta.textContent = opcion.textContent.trim();
                contenedor?.querySelectorAll('[data-dropdown-option]').forEach(item => item.classList.toggle('active', item === opcion));
            };

            modalRapido?.addEventListener('click', (event) => {
                const opcion = event.target.closest('[data-dropdown-option]');
                if (!opcion || !modalRapido.contains(opcion)) return;
                const contenedor = opcion.closest('.form-cm__dropdown');
                const campo = contenedor?.querySelector('input[type="hidden"]');
                const etiqueta = contenedor?.querySelector('[data-dropdown-label]');
                if (!campo || !etiqueta) return;
                campo.value = opcion.dataset.value || '';
                etiqueta.textContent = opcion.textContent.trim();
                contenedor.querySelectorAll('[data-dropdown-option]').forEach(item => item.classList.toggle('active', item === opcion));
                campo.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                window.bootstrap?.Dropdown?.getOrCreateInstance(contenedor.querySelector('[data-bs-toggle="dropdown"]'))?.hide();
            });

            rolRapido?.addEventListener('change', () => {
                if (rolPrincipal) {
                    rolPrincipal.value = rolRapido.value;
                    actualizarEtiquetaRol(rolPrincipal);
                }
            });
            rolPrincipal?.addEventListener('change', () => {
                if (rolRapido) {
                    rolRapido.value = rolPrincipal.value;
                    actualizarEtiquetaRol(rolRapido);
                }
            });

            document.getElementById('tab-registrar-persona')?.addEventListener('shown.bs.tab', () => {
                if (nombreRapido && !nombreRapido.value.trim()) nombreRapido.value = input?.value.trim() || '';
                if (rolRapido && rolPrincipal?.value) {
                    rolRapido.value = rolPrincipal.value;
                    actualizarEtiquetaRol(rolRapido);
                }
            });

            formularioRapido.addEventListener('submit', async (event) => {
                event.preventDefault();
                const boton = document.getElementById('persona-rapida-guardar');
                const error = document.getElementById('persona-rapida-error');
                error.hidden = true;
                error.textContent = '';
                boton.disabled = true;
                boton.textContent = 'Guardando…';
                const datos = new FormData(formularioRapido);
                datos.set('rol', rolRapido?.value || '');
                datos.set('personaje', document.getElementById('credito_personaje')?.value || '');
                datos.set('orden', document.getElementById('credito_orden')?.value || '0');
                try {
                    const respuesta = await fetch(formularioRapido.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: datos,
                        credentials: 'same-origin'
                    });
                    const contenido = await respuesta.json();
                    if (!respuesta.ok) {
                        let mensaje = contenido.message || 'No se pudo registrar la persona.';
                        if (contenido.errors) mensaje = Object.values(contenido.errors).flat().join(' ');
                        if (contenido.persona_existente) {
                            mensaje += ' Coincidencia existente: ' + contenido.persona_existente.nombre + '. Vuelve a la pestaña de búsqueda y selecciónala.';
                        }
                        throw new Error(mensaje);
                    }
                    window.location.assign(contenido.redirect);
                } catch (errorPeticion) {
                    error.textContent = errorPeticion.message || 'Ocurrió un error. Inténtalo nuevamente.';
                    error.hidden = false;
                } finally {
                    boton.disabled = false;
                    boton.textContent = 'Registrar y asignar';
                }
            });
        }

        const formularioCredito = document.getElementById('form-credito-modal');
        formularioCredito?.addEventListener('submit', (event) => {
            if (!hidden.value) {
                event.preventDefault();
                estado.textContent = 'Selecciona una persona de los resultados antes de asignar el crédito.';
                input.focus();
            }
        });
        const modalAsignar = document.getElementById('modalAsignarCredito');
        if (modalAsignar && window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalAsignar).show();
        }
    })();
</script>
@endpush
@endif
@endsection
@if ($pestana === 'produccion')
@push('scripts')
<script>
    (() => {
        const buscar = document.getElementById('pais_buscar');
        const resultados = document.getElementById('pais-resultados');
        const estado = document.getElementById('pais-buscar-estado');
        const urlBuscar = @json(route('admin.peliculas.paises.buscar', $pelicula));
        let temporizador = null;
        let controlador = null;
        const limpiar = () => {
            resultados.replaceChildren();
            resultados.hidden = true;
        };

        const crearOpcion = (pais) => {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            const texto = document.createElement('span');
            texto.textContent = `${pais.nombre} (${pais.codigo})`;
            const estadoPais = document.createElement('span');
            estadoPais.className = 'small muted';
            estadoPais.textContent = pais.asignado ? 'Ya asignado' : 'Asignar';
            boton.append(texto, estadoPais);
            boton.disabled = pais.asignado;
            boton.setAttribute('role', 'option');
            boton.addEventListener('click', () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = @json(route('admin.peliculas.paises.store', $pelicula));
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = @json(csrf_token());
                const codigo = document.createElement('input');
                codigo.type = 'hidden';
                codigo.name = 'codigo_pais';
                codigo.value = pais.codigo;
                form.append(csrf, codigo);
                document.body.append(form);
                form.submit();
            });
            return boton;
        };

        buscar?.addEventListener('input', () => {
            clearTimeout(temporizador);
            controlador?.abort();
            limpiar();
            const q = buscar.value.trim();
            if (q.length < 2) {
                estado.textContent = q ? 'Escribe al menos 2 caracteres.' : '';
                return;
            }
            estado.textContent = 'Buscando países…';
            temporizador = setTimeout(async () => {
                controlador = new AbortController();
                try {
                    const respuesta = await fetch(`${urlBuscar}?q=${encodeURIComponent(q)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        signal: controlador.signal
                    });
                    if (!respuesta.ok) throw new Error('No se pudo buscar países.');
                    const datos = await respuesta.json();
                    limpiar();
                    (datos.resultados || []).forEach(p => resultados.append(crearOpcion(p)));
                    resultados.hidden = resultados.children.length === 0;
                    estado.textContent = resultados.children.length ? 'Selecciona un país para asignarlo.' : 'No hay coincidencias. Puedes registrar el país nuevo.';
                } catch (error) {
                    if (error.name !== 'AbortError') estado.textContent = error.message || 'Error al buscar países.';
                }
            }, 250);
        });

        const modalCrearPais = document.getElementById('modalCrearPais');
        const formulario = document.getElementById('pais-crear-form');
        const cancelar = document.getElementById('pais-cancelar-crear');
        const error = document.getElementById('pais-crear-error');
        modalCrearPais?.addEventListener('shown.bs.modal', () => {
            document.getElementById('pais_nuevo_codigo')?.focus();
        });
        cancelar?.addEventListener('click', () => {
            error.hidden = true;
            error.textContent = '';
            formulario.reset();
        });
        modalCrearPais?.addEventListener('hidden.bs.modal', () => {
            error.hidden = true;
            error.textContent = '';
            formulario.reset();
        });
        formulario?.addEventListener('submit', async (event) => {
            event.preventDefault();
            error.hidden = true;
            error.textContent = '';
            const boton = document.getElementById('pais-crear-guardar');
            boton.disabled = true;
            boton.textContent = 'Guardando…';
            try {
                const respuesta = await fetch(formulario.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(formulario),
                    credentials: 'same-origin'
                });
                const datos = await respuesta.json();
                if (!respuesta.ok) {
                    let mensaje = datos.message || 'No se pudo registrar el país.';
                    if (datos.errors) mensaje = Object.values(datos.errors).flat().join(' ');
                    throw new Error(mensaje);
                }
                window.location.assign(datos.redirect);
            } catch (e) {
                error.textContent = e.message || 'Ocurrió un error. Inténtalo nuevamente.';
                error.hidden = false;
            } finally {
                boton.disabled = false;
                boton.textContent = 'Registrar y asignar';
            }
        });
    })();
</script>
@endpush
@endif