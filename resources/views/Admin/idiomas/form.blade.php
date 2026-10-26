@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar idioma' : 'Editar idioma')

@section('content')
<div class="form-page-header mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h2 font-display fw-bold text-cm-primary mb-1 titulo-main">
                {{ $modo === 'crear' ? 'Registrar idioma' : 'Editar idioma' }}
            </h1>
            <p class="text-muted mb-0 small">
                El código identifica el idioma y puede estar vinculado a películas existentes.
            </p>
        </div>
        <a href="{{ route('admin.idiomas.index') }}" class="btn admin-back-btn d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none">
            <span class="material-symbols-outlined fs-6" aria-hidden="true">arrow_back</span>
            <span>Volver a idiomas</span>
        </a>
    </div>
</div>

<x-form.errors class="mb-3" />

<x-form.layout
    title="Información del idioma"
    subtitle="Define el código, el nombre visible y el estado del idioma en el catálogo.">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.idiomas.store') : route('admin.idiomas.update', $idioma->codigo) }}">
        @csrf
        @if ($modo === 'editar')
        @method('PUT')
        @endif

        <section class="form-cm__section" aria-label="Datos del idioma">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <x-form.field
                        name="codigo"
                        label="Código de idioma (BCP 47)"
                        :required="true"
                        help="Ejemplos: es, en, pt-BR. Máximo 15 caracteres."
                        :show-error="false">
                        <x-form.input
                            name="codigo"
                            :value="$idioma->codigo"
                            placeholder="es o pt-BR"
                            :maxlength="15"
                            :required="true"
                            autocomplete="off"
                            aria-describedby="codigo-ayuda" />
                    </x-form.field>
                </div>

                <div class="col-12 col-md-5">
                    <x-form.field
                        name="nombre"
                        label="Nombre"
                        :required="true"
                        help="Máximo 100 caracteres."
                        :show-error="false">
                        <x-form.input
                            name="nombre"
                            :value="$idioma->nombre"
                            placeholder="Ej. Español"
                            :maxlength="100"
                            :required="true"
                            autocomplete="off"
                            aria-describedby="nombre-ayuda" />
                    </x-form.field>
                </div>

                <div class="col-12 col-md-3">
                    <div class="form-cm__group">
                        <label for="activo_toggle" class="form-cm__label">
                            <span>Estado</span>
                            <span class="required" aria-hidden="true">*</span>
                            <span class="visually-hidden">(obligatorio)</span>
                        </label>

                        <x-form.select
                            name="activo"
                            :options="['1' => 'Activo', '0' => 'Inactivo']"
                            :selected="(string) (int) ($idioma->activo ?? 1)"
                            placeholder="Seleccionar estado"
                            help="Desactivar conserva el idioma y las películas que lo referencian." />
                    </div>
                </div>
            </div>
        </section>

        <x-form.actions class="justify-content-end">
            <a href="{{ route('admin.idiomas.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                {{ $modo === 'crear' ? 'Guardar idioma' : 'Guardar cambios' }}
            </button>
        </x-form.actions>
    </form>
</x-form.layout>
@endsection