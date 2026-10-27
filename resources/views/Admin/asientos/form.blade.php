@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar asiento' : 'Editar asiento')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.asientos.index') }}" class="btn btn-outline-light btn-sm mb-3">← Volver a asientos</a>
    <h1 class="h3 mb-2">{{ $modo === 'crear' ? 'Registrar asiento' : 'Editar asiento' }}</h1>
    <p class="muted mb-0">La combinación sala, fila y número debe ser única.</p>
</div>

<x-form.errors />

@if ($salas->isEmpty())
    <div class="alert alert-warning" role="status">Primero debes registrar una sala para poder crear un asiento.</div>
@endif

@php
    $opcionesSalas = $salas->mapWithKeys(fn ($sala) => [$sala->id_sala => $sala->nombre])->all();
    $opcionesTipos = collect($tipos)->mapWithKeys(fn ($tipo) => [$tipo => $tipo])->all();
    $opcionesActivo = ['1' => 'Activo', '0' => 'Inactivo'];
@endphp

@if ($modo === 'editar' && $asientoProtegido)
    <div class="alert alert-warning" role="status">
        <h2 class="h6 mb-2">Asiento protegido por historial de funciones</h2>
        <p class="mb-2">Este asiento está asociado a funciones registradas. No puedes cambiar su sala, fila, número ni tipo, porque eso alteraría su identidad histórica. La disponibilidad puede modificarse según las reglas actuales.</p>
        <p class="mb-2"><strong>Funciones asociadas:</strong> {{ $funcionesAsociadas->count() }}</p>
        <ul class="mb-0 ps-3">
            @foreach ($funcionesAsociadas as $funcionAsociada)
                <li class="mb-2">
                    <strong>{{ $funcionAsociada->pelicula_titulo }}</strong>
                    — {{ \Illuminate\Support\Carbon::parse($funcionAsociada->inicio)->format('d/m/Y H:i') }}
                    — {{ $funcionAsociada->formato }}
                    — Estado: {{ $funcionAsociada->estado }}
                    — Precio del asiento: ${{ number_format((float) $funcionAsociada->precio_asiento, 2, ',', '.') }}
                    — Entradas vinculadas: {{ $funcionAsociada->cantidad_entradas }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

<x-form.layout title="Información del asiento" subtitle="Define la sala y la ubicación del asiento.">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.asientos.store') : route('admin.asientos.update', $asiento->id_asiento) }}">
        @csrf
        @if ($modo === 'editar') @method('PUT') @endif

        <div class="form-cm__section">
            <div class="row g-3">
                <div class="col-12">
                    <x-form.field name="id_sala" label="Sala" id="id_sala_toggle" :required="true" :show-error="false">
                        <x-form.select name="id_sala" :options="$opcionesSalas" :selected="old('id_sala', $asiento->id_sala)" placeholder="Selecciona una sala" help="Selecciona la sala a la que pertenece este asiento." :disabled="$salas->isEmpty()" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="fila" label="Fila" :required="true" :show-error="false">
                        <x-form.input name="fila" :value="$asiento->fila" maxlength="5" placeholder="Ej. A" required />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="numero" label="Número" :required="true" :show-error="false">
                        <x-form.input name="numero" type="number" :value="$asiento->numero" required min="1" max="65535" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="tipo" label="Tipo de asiento" id="tipo_toggle" :required="true" :show-error="false">
                        <x-form.select name="tipo" :options="$opcionesTipos" :selected="old('tipo', $asiento->tipo ?? 'ESTANDAR')" placeholder="Selecciona un tipo" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="activo" label="Disponibilidad" id="activo_toggle" :required="true" :show-error="false">
                        <x-form.select name="activo" :options="$opcionesActivo" :selected="old('activo', $asiento->activo ?? 1)" placeholder="Selecciona disponibilidad" />
                    </x-form.field>
                </div>
            </div>
        </div>

        <x-form.actions>
            <a href="{{ route('admin.asientos.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary" @disabled($salas->isEmpty())>{{ $modo === 'crear' ? 'Registrar asiento' : 'Guardar cambios' }}</button>
        </x-form.actions>
    </form>
</x-form.layout>
@endsection
