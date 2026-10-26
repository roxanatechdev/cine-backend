@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar función' : 'Editar función')

@section('content')
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

<x-form.errors />
@if (session('error'))
<div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if ($peliculas->isEmpty())
<div class="alert alert-warning" role="status">No hay películas activas con duración válida. Debes activar una película y registrar su duración antes de programarla.</div>
@endif
@if ($salas->isEmpty())
<div class="alert alert-warning" role="status">No hay salas activas disponibles para programar funciones.</div>
@endif

@php
$opcionesPeliculas = $peliculas->mapWithKeys(fn ($pelicula) => [$pelicula->id_pelicula => $pelicula->titulo . ' (' . $pelicula->duracion_minutos . ' min)'])->all();
$opcionesSalas = $salas->mapWithKeys(fn ($sala) => [$sala->id_sala => $sala->nombre . ($sala->estado === 'ACTIVA' ? '' : ' (' . $sala->estado . ')') . ' — limpieza: ' . $sala->margen_limpieza_minutos . ' min'])->all();
$opcionesFormatos = collect($formatos)->mapWithKeys(fn ($formato) => [$formato => $formato])->all();
$opcionesEstados = collect($estados)->mapWithKeys(fn ($estado) => [$estado => str_replace('_', ' ', $estado)])->all();
@endphp

<x-form.layout title="Programación de la función" subtitle="Selecciona la película, la sala, el horario y las condiciones comerciales.">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.funciones.store') : route('admin.funciones.update', $funcion->id_funcion) }}">
        @csrf
        @if ($modo === 'editar') @method('PUT') @endif

        <div class="form-cm__section">
            <div class="row g-3">
                <div class="col-12">
                    <x-form.field name="id_pelicula" label="Película" id="id_pelicula_toggle" :required="true" :show-error="false">
                        <x-form.select name="id_pelicula" :options="$opcionesPeliculas" :selected="old('id_pelicula', $funcion->id_pelicula)" placeholder="Selecciona una película" help="Solo se ofrecen películas activas con duración válida." :disabled="$peliculas->isEmpty()" />
                    </x-form.field>
                </div>
                <div class="col-12">
                    <x-form.field name="id_sala" label="Sala" id="id_sala_toggle" :required="true" :show-error="false">
                        <x-form.select name="id_sala" :options="$opcionesSalas" :selected="old('id_sala', $funcion->id_sala)" placeholder="Selecciona una sala" help="Se muestra el estado de la sala y su margen de limpieza." :disabled="$salas->isEmpty()" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="inicio" label="Fecha y hora de inicio" :required="true" :show-error="false">
                        <x-form.input name="inicio" type="datetime-local" :value="$funcion->inicio?->format('Y-m-d\\TH:i')" required />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="formato" label="Formato" id="formato_toggle" :required="true" :show-error="false">
                        <x-form.select name="formato" :options="$opcionesFormatos" :selected="old('formato', $funcion->formato ?? '2D')" placeholder="Selecciona un formato" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="precio_base" label="Precio base" :required="true" :show-error="false">
                        <x-form.input name="precio_base" type="number" :value="$funcion->precio_base" required min="0" max="99999999.99" step="0.01" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="estado" label="Estado" id="estado_toggle" :required="true" :show-error="false">
                        <x-form.select name="estado" :options="$opcionesEstados" :selected="old('estado', $funcion->estado ?? 'PROGRAMADA')" placeholder="Selecciona un estado" />
                    </x-form.field>
                </div>
            </div>
        </div>

        <x-form.actions>
            <a href="{{ route('admin.funciones.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary" @disabled($peliculas->isEmpty() || $salas->isEmpty())>{{ $modo === 'crear' ? 'Registrar función' : 'Guardar cambios' }}</button>
        </x-form.actions>
    </form>
</x-form.layout>
@endsection