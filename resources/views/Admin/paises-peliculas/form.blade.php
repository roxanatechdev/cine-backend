@extends('admin.layout')

@section('title', 'Asignar país de producción')

@section('content')
<div class="form-page-header mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h2 font-display fw-bold text-cm-primary mb-1 titulo-main">
                Asignar país de producción
            </h1>
            <p class="text-muted mb-0 small">
                Selecciona un país para asociarlo a la película sin duplicar sus países de producción.
            </p>
            <p class="text-muted mb-0 mt-2">
                Película: <strong>{{ $pelicula->titulo }}</strong>
            </p>
        </div>
        <a href="{{ route('admin.peliculas.paises.index', $pelicula) }}"
            class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-1 rounded-3 px-3 py-2 align-self-start align-self-md-auto">
            <span aria-hidden="true">←</span> Volver a países
        </a>
    </div>
</div>

<x-form.errors class="mb-3" />

@php
    $opcionesPaises = $paises->mapWithKeys(
        fn ($pais) => [$pais->codigo => $pais->nombre . ' (' . $pais->codigo . ')']
    )->all();
@endphp

<x-form.layout
    title="País de producción"
    subtitle="Elige un país del catálogo para asociarlo a esta película.">
    <form method="POST" action="{{ route('admin.peliculas.paises.store', $pelicula) }}">
        @csrf

        <section class="form-cm__section" aria-label="Selección del país">
            <div class="row g-3">
                <div class="col-12">
                    <x-form.field
                        name="codigo_pais"
                        id="codigo_pais_toggle"
                        label="País"
                        :required="true"
                        :showError="false">
                        <x-form.select
                            name="codigo_pais"
                            :options="$opcionesPaises"
                            :selected="old('codigo_pais', '')"
                            placeholder="Selecciona un país"
                            help="El país debe existir en el catálogo y no estar asignado previamente a esta película."
                            :disabled="$paises->isEmpty()" />
                    </x-form.field>

                    @if ($paises->isEmpty())
                        <p class="form-cm__text mt-2 mb-0" role="status">
                            No hay países disponibles para asignar. Registra primero un país en el catálogo.
                        </p>
                    @endif
                </div>
            </div>
        </section>

        <x-form.actions class="justify-content-end">
            <a href="{{ route('admin.peliculas.paises.index', $pelicula) }}" class="btn btn-outline-light">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary" @disabled($paises->isEmpty())>
                Asignar país
            </button>
        </x-form.actions>
    </form>
</x-form.layout>
@endsection
