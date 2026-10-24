@extends('Admin.layout')

@section('title', $modo === 'crear' ? 'Registrar idioma' : 'Editar idioma')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.idiomas.index') }}" class="btn btn-outline-light btn-sm mb-3">← Volver a idiomas</a>
    <h1 class="admin-page-title">{{ $modo === 'crear' ? 'Registrar idioma' : 'Editar idioma' }}</h1>
    <p class="muted mb-0">El código identifica el idioma y puede estar vinculado a películas existentes.</p>
</div>

<div class="panel p-4">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.idiomas.store') : route('admin.idiomas.update', $idioma->codigo) }}">
        @csrf
        @if ($modo === 'editar') @method('PUT') @endif
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label for="codigo" class="form-label">Código de idioma (BCP 47)</label>
                <input id="codigo" name="codigo" value="{{ old('codigo', $idioma->codigo) }}" class="form-control @error('codigo') is-invalid @enderror" maxlength="15" required placeholder="es o pt-BR" autocomplete="off">
                @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text muted">Ejemplos: es, en, pt-BR.</div>
            </div>
            <div class="col-12 col-md-5">
                <label for="nombre" class="form-label">Nombre</label>
                <input id="nombre" name="nombre" value="{{ old('nombre', $idioma->nombre) }}" class="form-control @error('nombre') is-invalid @enderror" maxlength="100" required>
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-3">
                <label for="activo" class="form-label">Estado</label>
                <select id="activo" name="activo" class="form-select @error('activo') is-invalid @enderror" required>
                    <option value="1" @selected((string) old('activo', $idioma->activo ?? 1) === '1')>Activo</option>
                    <option value="0" @selected((string) old('activo', $idioma->activo ?? 1) === '0')>Inactivo</option>
                </select>
                @error('activo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.idiomas.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">{{ $modo === 'crear' ? 'Guardar idioma' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>
@endsection
