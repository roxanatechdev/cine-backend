@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar país' : 'Editar país')

@section('content')
<div class="form-page-header mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h2 font-display fw-bold text-cm-primary mb-1 titulo-main">
                {{ $modo === 'crear' ? 'Registrar país' : 'Editar país' }}
            </h1>
            <p class="text-muted mb-0 small">
                Usa el código ISO 3166-1 alfa-2 y el nombre oficial del país.
            </p>
        </div>
        <a href="{{ route('admin.paises.index') }}" class="btn admin-back-btn d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none">
            <span class="material-symbols-outlined fs-6" aria-hidden="true">arrow_back</span>
            <span>Volver a paises</span>
        </a>

    </div>
</div>

<x-form.errors class="mb-3" />

<x-form.layout
    title="Información del país"
    subtitle="Define el código internacional y el nombre que se mostrará en el catálogo.">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.paises.store') : route('admin.paises.update', $pais->codigo) }}">
        @csrf
        @if ($modo === 'editar')
        @method('PUT')
        @endif

        <section class="form-cm__section" aria-label="Datos del país">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <x-form.field
                        name="codigo"
                        label="Código del país (ISO 3166-1 alfa-2)"
                        :required="true"
                        help="Dos letras mayúsculas. Ejemplos: CL, AR, BR, US.">
                        <x-form.input
                            name="codigo"
                            :value="$pais->codigo"
                            placeholder="CL"
                            :maxlength="2"
                            :required="true"
                            minlength="2"
                            pattern="[A-Z]{2}"
                            autocomplete="off"
                            style="text-transform: uppercase"
                            aria-describedby="codigo-ayuda" />
                    </x-form.field>
                </div>

                <div class="col-12 col-md-8">
                    <x-form.field
                        name="nombre"
                        label="Nombre del país"
                        :required="true"
                        help="Máximo 100 caracteres. El nombre debe ser único.">
                        <x-form.input
                            name="nombre"
                            :value="$pais->nombre"
                            placeholder="Chile"
                            :maxlength="100"
                            :required="true"
                            autocomplete="off"
                            aria-describedby="nombre-ayuda" />
                    </x-form.field>
                </div>
            </div>
        </section>

        <x-form.actions class="justify-content-end">
            <a href="{{ route('admin.paises.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                {{ $modo === 'crear' ? 'Registrar país' : 'Guardar cambios' }}
            </button>
        </x-form.actions>
    </form>
</x-form.layout>
@endsection