@extends('admin.layout')

@section('title', $modo === 'crear'
? 'Registrar país'
: 'Editar país')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.paises.index') }}"
        class="btn btn-outline-light btn-sm mb-3">
        ← Volver a países
    </a>

    <h1 class="h3 mb-2">
        {{ $modo === 'crear' ? 'Registrar país' : 'Editar país' }}
    </h1>

    <p class="muted mb-0">
        Introduce el código ISO de dos letras y el nombre del país.
    </p>
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

<div class="panel rounded-3 p-3 p-md-4">
    <form method="POST"
        action="{{ $modo === 'crear'
              ? route('admin.paises.store')
              : route('admin.paises.update', $pais->codigo) }}">

        @csrf

        @if ($modo === 'editar')
        @method('PUT')
        @endif

        <div class="mb-3">
            <label for="codigo" class="form-label">
                Código del país <span class="text-danger">*</span>
            </label>

            <input
                id="codigo"
                name="codigo"
                type="text"
                maxlength="2"
                minlength="2"
                pattern="[A-Za-z]{2}"
                required
                value="{{ old('codigo', $pais->codigo) }}"
                class="form-control @error('codigo') is-invalid @enderror"
                placeholder="Ej. BO"
                style="text-transform: uppercase">

            <div class="form-text muted">
                Dos letras mayúsculas. Ejemplos: BO, AR, BR, US.
            </div>

            @error('codigo')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="nombre" class="form-label">
                Nombre del país <span class="text-danger">*</span>
            </label>

            <input
                id="nombre"
                name="nombre"
                type="text"
                maxlength="100"
                required
                value="{{ old('nombre', $pais->nombre) }}"
                class="form-control @error('nombre') is-invalid @enderror"
                placeholder="Ej. Bolivia">

            @error('nombre')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('admin.paises.index') }}"
                class="btn btn-outline-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary">
                {{ $modo === 'crear' ? 'Registrar país' : 'Guardar cambios' }}
            </button>
        </div>
    </form>
</div>
@endsection
