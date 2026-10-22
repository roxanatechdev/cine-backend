@extends('Admin.layout')

@section('title', $modo === 'crear' ? 'Nueva categoría' : 'Editar categoría')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.categorias-producto.index') }}" class="btn btn-outline-light btn-sm mb-3">
        ← Volver a categorías
    </a>
    <p class="text-uppercase small muted fw-semibold mb-2">Catálogo de confitería</p>
    <h1 class="admin-page-title">{{ $modo === 'crear' ? 'Nueva categoría' : 'Editar categoría' }}</h1>
    <p class="muted mb-0">{{ $modo === 'crear' ? 'Completa los datos para registrar una categoría.' : 'Actualiza el nombre o la descripción de la categoría.' }}</p>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="panel p-3 p-md-4">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.categorias-producto.store') : route('admin.categorias-producto.update', $categoriaProducto) }}">
        @csrf
        @if ($modo === 'editar')
            @method('PUT')
        @endif

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre de la categoría <span class="text-danger">*</span></label>
            <input id="nombre" name="nombre" type="text" maxlength="80" required autofocus
                value="{{ old('nombre', $categoriaProducto->nombre) }}"
                class="form-control @error('nombre') is-invalid @enderror"
                placeholder="Ej. Bebidas, snacks, combos...">
            @error('nombre')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text muted">Máximo 80 caracteres. El nombre debe ser único.</div>
        </div>

        <div class="mb-4">
            <label for="descripcion" class="form-label">Descripción <span class="muted fw-normal">(opcional)</span></label>
            <textarea id="descripcion" name="descripcion" rows="4" maxlength="255"
                class="form-control @error('descripcion') is-invalid @enderror"
                placeholder="Describe brevemente los productos que pertenecen a esta categoría.">{{ old('descripcion', $categoriaProducto->descripcion) }}</textarea>
            @error('descripcion')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text muted">Máximo 255 caracteres.</div>
        </div>

        @if ($modo === 'editar')
        <div class="alert alert-secondary py-2 small">
            El estado se administra desde el listado. Este formulario no cambia si la categoría está activa o inactiva.
        </div>
        @else
        <div class="alert alert-secondary py-2 small">
            La categoría se registrará activa por defecto.
        </div>
        @endif

        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('admin.categorias-producto.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                {{ $modo === 'crear' ? 'Registrar categoría' : 'Guardar cambios' }}
            </button>
        </div>
    </form>
</div>
@endsection
