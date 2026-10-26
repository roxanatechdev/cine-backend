@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar género' : 'Editar género')

@section('content')
<div class="form-page-header mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h2 font-display fw-bold text-cm-primary mb-1 titulo-main">
                {{ $modo === 'crear' ? 'Registrar género' : 'Editar género' }}
            </h1>
            <p class="text-muted mb-0 small">
                Completa el nombre, la descripción opcional y el estado del género.
            </p>
        </div>
        <a href="{{ route('admin.generos.index') }}" class="btn admin-back-btn d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none">
            <span class="material-symbols-outlined fs-6" aria-hidden="true">arrow_back</span>
            <span>Volver al géneros</span>
        </a>
    </div>
</div>

<x-form.errors class="mb-3" />

<x-form.layout
    title="Información del género"
    subtitle="Define cómo se identifica y se muestra este género en el catálogo.">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.generos.store') : route('admin.generos.update', $genero->id_genero) }}">
        @csrf
        @if ($modo === 'editar')
        @method('PUT')
        @endif

        <section class="form-cm__section" aria-label="Datos del género">
            <div class="row">
                <div class="col-12 col-lg-8">
                    <x-form.field name="nombre" label="Nombre" :required="true" help="Máximo 80 caracteres.">
                        <x-form.input
                            name="nombre"
                            :value="$genero->nombre"
                            placeholder="Ej. Ciencia ficción"
                            :maxlength="80"
                            :required="true"
                            autocomplete="off"
                            aria-describedby="nombre-ayuda" />
                    </x-form.field>
                </div>

                <div class="col-12">
                    <x-form.field name="descripcion" label="Descripción" help="Campo opcional, máximo 255 caracteres.">
                        <x-form.textarea
                            name="descripcion"
                            :value="$genero->descripcion"
                            placeholder="Describe brevemente este género"
                            :maxlength="255"
                            :rows="3"
                            aria-describedby="descripcion-ayuda" />
                    </x-form.field>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-cm__group">
                        <label for="activo_toggle" class="form-cm__label">
                            <span>Estado</span>
                            <span class="required" aria-hidden="true">*</span>
                            <span class="visually-hidden">(obligatorio)</span>
                        </label>

                        <x-form.select
                            name="activo"
                            :options="['1' => 'Activo', '0' => 'Inactivo']"
                            :selected="(int) $genero->activo"
                            placeholder="Seleccionar estado"
                            help="Desactivar conserva el género y las películas que lo referencian." />
                    </div>
                </div>
            </div>
        </section>

        <x-form.actions class="justify-content-end">
            <a href="{{ route('admin.generos.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                {{ $modo === 'crear' ? 'Registrar género' : 'Guardar cambios' }}
            </button>
        </x-form.actions>
    </form>
</x-form.layout>
@endsection