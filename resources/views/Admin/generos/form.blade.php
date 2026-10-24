@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar género' : 'Editar género')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.generos.index') }}" class="btn btn-outline-light btn-sm mb-3">← Volver a géneros</a>
    <h1 class="h3 mb-2">{{ $modo === 'crear' ? 'Registrar género' : 'Editar género' }}</h1>
    <p class="muted mb-0">Completa el nombre, la descripción opcional y el estado del género.</p>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="panel rounded-3 p-3 p-md-4">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.generos.store') : route('admin.generos.update', $genero->id_genero) }}">
        @csrf
        @if ($modo === 'editar') @method('PUT') @endif
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
            <input id="nombre" name="nombre" type="text" maxlength="80" required value="{{ old('nombre', $genero->nombre) }}" class="form-control @error('nombre') is-invalid @enderror" placeholder="Ej. Ciencia ficción">
            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea id="descripcion" name="descripcion" maxlength="255" rows="3" class="form-control @error('descripcion') is-invalid @enderror" placeholder="Descripción opcional del género">{{ old('descripcion', $genero->descripcion) }}</textarea>
            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label for="activo" class="form-label">Estado <span class="text-danger">*</span></label>
            <select id="activo" name="activo" required class="form-select @error('activo') is-invalid @enderror">
                <option value="1" @selected((string) old('activo', (int) $genero->activo) === '1')>Activo</option>
                <option value="0" @selected((string) old('activo', (int) $genero->activo) === '0')>Inactivo</option>
            </select>
            @error('activo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text muted">Desactivar conserva el género y las películas que lo referencian.</div>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('admin.generos.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">{{ $modo === 'crear' ? 'Registrar género' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>
@endsection
