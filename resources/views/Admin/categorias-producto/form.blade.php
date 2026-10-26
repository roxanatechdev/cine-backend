@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Nueva categoría' : 'Editar categoría')

@section('content')
<div class="form-page-header mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <p class="text-uppercase small muted fw-semibold mb-2">Catálogo de confitería</p>
            <h1 class="h2 font-display fw-bold text-cm-primary mb-1 titulo-main">
                {{ $modo === 'crear' ? 'Nueva categoría' : 'Editar categoría' }}
            </h1>
            <p class="text-muted mb-0 small">
                {{ $modo === 'crear' ? 'Completa los datos para registrar una categoría.' : 'Actualiza el nombre o la descripción de la categoría.' }}
            </p>
        </div>
        <a href="{{ route('admin.categorias-producto.index') }}" class="btn admin-back-btn d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none">
            <span class="material-symbols-outlined fs-6" aria-hidden="true">arrow_back</span>
            <span>Volver a categorias</span>
        </a>
    </div>
</div>

<x-form.errors class="mb-3" />

<x-form.layout
    title="Información de la categoría"
    subtitle="Define el nombre y la descripción que ayudarán a organizar los productos.">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.categorias-producto.store') : route('admin.categorias-producto.update', $categoriaProducto) }}">
        @csrf
        @if ($modo === 'editar')
        @method('PUT')
        @endif

        <section class="form-cm__section" aria-label="Datos de la categoría">
            <div class="row g-3">
                <div class="col-12">
                    <x-form.field
                        name="nombre"
                        label="Nombre de la categoría"
                        :required="true"
                        help="Máximo 80 caracteres. El nombre debe ser único.">
                        <x-form.input
                            name="nombre"
                            :value="$categoriaProducto->nombre"
                            placeholder="Ej. Bebidas, snacks, combos..."
                            :maxlength="80"
                            :required="true"
                            autofocus
                            autocomplete="off"
                            aria-describedby="nombre-ayuda" />
                    </x-form.field>
                </div>

                <div class="col-12">
                    <x-form.field
                        name="descripcion"
                        label="Descripción"
                        help="Campo opcional. Máximo 255 caracteres.">
                        <x-form.textarea
                            name="descripcion"
                            :value="$categoriaProducto->descripcion"
                            placeholder="Describe brevemente los productos que pertenecen a esta categoría."
                            :maxlength="255"
                            :rows="4"
                            aria-describedby="descripcion-ayuda" />
                    </x-form.field>
                </div>
            </div>

            @if ($modo === 'editar')
            <div class="alert alert-secondary py-2 small mt-3 mb-0" role="note">
                El estado se administra desde el listado. Este formulario no cambia si la categoría está activa o inactiva.
            </div>
            @else
            <div class="alert alert-secondary py-2 small mt-3 mb-0" role="note">
                La categoría se registrará activa por defecto.
            </div>
            @endif
        </section>

        <x-form.actions class="justify-content-end">
            <a href="{{ route('admin.categorias-producto.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                {{ $modo === 'crear' ? 'Registrar categoría' : 'Guardar cambios' }}
            </button>
        </x-form.actions>
    </form>
</x-form.layout>
@endsection