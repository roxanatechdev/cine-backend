@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar comprador' : 'Editar comprador')

@section('content')
<div class="form-page-header mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h2 font-display fw-bold text-cm-primary mb-1 titulo-main">
                {{ $modo === 'crear' ? 'Registrar comprador' : 'Editar comprador' }}
            </h1>
            <p class="text-muted mb-0 small">
                El registro de creación lo asigna automáticamente la base de datos.
            </p>
        </div>
        <a href="{{ route('admin.compradores.index') }}" class="btn admin-back-btn d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none">
            <span class="material-symbols-outlined fs-6" aria-hidden="true">arrow_back</span>
            <span>Volver a clientes</span>
        </a>
    </div>
</div>

<x-form.errors />

@php
$opcionesTipos = collect($tipos)->mapWithKeys(fn ($tipo) => [$tipo => $tipo === 'INVITADO' ? 'Invitado' : 'Registrado'])->all();
@endphp

<x-form.layout title="Información del comprador" subtitle="Completa los datos de contacto y el tipo de comprador.">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.compradores.store') : route('admin.compradores.update', $comprador->id_comprador) }}">
        @csrf
        @if ($modo === 'editar') @method('PUT') @endif

        <div class="form-cm__section">
            <div class="row g-3">
                <div class="col-12">
                    <x-form.field name="nombre" label="Nombre completo" :required="true" :show-error="false">
                        <x-form.input name="nombre" :value="$comprador->nombre" maxlength="100" required autofocus autocomplete="name" />
                    </x-form.field>
                </div>
                <div class="col-12">
                    <x-form.field name="correo" label="Correo electrónico" :required="true" help="El esquema indexa el correo, pero no exige que sea único." :show-error="false">
                        <x-form.input name="correo" type="email" :value="$comprador->correo" maxlength="150" required autocomplete="email" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="telefono" label="Teléfono" help="Campo opcional." :show-error="false">
                        <x-form.input name="telefono" :value="$comprador->telefono" maxlength="30" placeholder="Opcional" autocomplete="tel" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="tipo_comprador" label="Tipo de comprador" id="tipo_comprador_toggle" :required="true" :show-error="false">
                        <x-form.select name="tipo_comprador" :options="$opcionesTipos" :selected="old('tipo_comprador', $comprador->tipo_comprador ?? 'INVITADO')" placeholder="Selecciona un tipo" />
                    </x-form.field>
                </div>
            </div>
        </div>

        <x-form.actions>
            <a href="{{ route('admin.compradores.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">{{ $modo === 'crear' ? 'Registrar comprador' : 'Guardar cambios' }}</button>
        </x-form.actions>
    </form>
</x-form.layout>
@endsection