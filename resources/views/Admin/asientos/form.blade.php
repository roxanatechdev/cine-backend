@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar asiento' : 'Editar asiento')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.asientos.index') }}" class="btn btn-outline-light btn-sm mb-3">← Volver a asientos</a>
    <h1 class="h3 mb-2">{{ $modo === 'crear' ? 'Registrar asiento' : 'Editar asiento' }}</h1>
    <p class="muted mb-0">La combinación sala, fila y número debe ser única.</p>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if ($salas->isEmpty())
<div class="alert alert-warning" role="alert">Primero debes registrar una sala para poder crear un asiento.</div>
@endif

<div class="panel rounded-3 p-3 p-md-4">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.asientos.store') : route('admin.asientos.update', $asiento->id_asiento) }}">
        @csrf
        @if ($modo === 'editar') @method('PUT') @endif

        <div class="mb-3">
            <label for="id_sala" class="form-label">Sala <span class="text-danger">*</span></label>
            <select id="id_sala" name="id_sala" required class="form-select @error('id_sala') is-invalid @enderror">
                <option value="">Selecciona una sala</option>
                @foreach ($salas as $sala)
                <option value="{{ $sala->id_sala }}" @selected((string) old('id_sala', $asiento->id_sala) === (string) $sala->id_sala)>{{ $sala->nombre }}</option>
                @endforeach
            </select>
            @error('id_sala')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="fila" class="form-label">Fila <span class="text-danger">*</span></label>
                <input id="fila" name="fila" type="text" maxlength="5" required value="{{ old('fila', $asiento->fila) }}" class="form-control @error('fila') is-invalid @enderror" placeholder="Ej. A">
                @error('fila')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="numero" class="form-label">Número <span class="text-danger">*</span></label>
                <input id="numero" name="numero" type="number" min="1" max="65535" required value="{{ old('numero', $asiento->numero) }}" class="form-control @error('numero') is-invalid @enderror">
                @error('numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="tipo" class="form-label">Tipo de asiento <span class="text-danger">*</span></label>
            <select id="tipo" name="tipo" required class="form-select @error('tipo') is-invalid @enderror">
                @foreach ($tipos as $tipo)
                <option value="{{ $tipo }}" @selected(old('tipo', $asiento->tipo ?? 'ESTANDAR') === $tipo)>{{ $tipo }}</option>
                @endforeach
            </select>
            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
            <label for="activo" class="form-label">Disponibilidad <span class="text-danger">*</span></label>
            <select id="activo" name="activo" required class="form-select @error('activo') is-invalid @enderror">
                <option value="1" @selected((string) old('activo', $asiento->activo ?? 1) === '1')>Activo</option>
                <option value="0" @selected((string) old('activo', $asiento->activo ?? 1) === '0')>Inactivo</option>
            </select>
            @error('activo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('admin.asientos.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary" @disabled($salas->isEmpty())>{{ $modo === 'crear' ? 'Registrar asiento' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>
@endsection
