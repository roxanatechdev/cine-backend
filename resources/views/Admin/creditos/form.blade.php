@extends('Admin.layout')

@section('title', $modo === 'crear'
? 'Asignar crédito'
: 'Editar crédito')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.peliculas.creditos.index', $pelicula) }}"
        class="btn btn-outline-light btn-sm mb-3">
        ← Volver a créditos
    </a>

    <h1 class="h3">
        {{ $modo === 'crear' ? 'Asignar persona' : 'Editar crédito' }}
    </h1>

    <p class="muted mb-0">
        Película: <strong>{{ $pelicula->titulo }}</strong>
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
              ? route('admin.peliculas.creditos.store', $pelicula)
              : route('admin.peliculas.creditos.update', [
                  $pelicula,
                  $credito->id_persona,
                  $credito->rol
              ]) }}">

        @csrf

        @if ($modo === 'editar')
        @method('PUT')
        @endif

        <div class="mb-3">
            <label for="id_persona" class="form-label">
                Persona <span class="text-danger">*</span>
            </label>

            <select id="id_persona"
                name="id_persona"
                class="form-select @error('id_persona') is-invalid @enderror"
                required>
                <option value="">Selecciona una persona</option>

                @foreach ($personas as $persona)
                <option value="{{ $persona->id_persona }}"
                    @selected((string) old( 'id_persona' ,
                    $credito->id_persona ?? ''
                    ) === (string) $persona->id_persona)>
                    {{ $persona->nombre_completo }}
                </option>
                @endforeach
            </select>

            @error('id_persona')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="rol" class="form-label">
                Rol <span class="text-danger">*</span>
            </label>

            <select id="rol"
                name="rol"
                required
                class="form-select @error('rol') is-invalid @enderror">
                <option value="">Selecciona un rol</option>
                @foreach (['DIRECTOR', 'ACTOR', 'GUIONISTA', 'PRODUCTOR', 'COMPOSITOR'] as $rolDisponible)
                <option value="{{ $rolDisponible }}"
                    @selected(old('rol', $credito->rol ?? '') === $rolDisponible)>
                    {{ $rolDisponible }}
                </option>
                @endforeach
            </select>

            @error('rol')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="personaje" class="form-label">
                Personaje
            </label>

            <input id="personaje"
                name="personaje"
                type="text"
                maxlength="150"
                value="{{ old('personaje', $credito->personaje ?? '') }}"
                class="form-control @error('personaje') is-invalid @enderror"
                placeholder="Nombre del personaje, si corresponde">

            @error('personaje')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="orden" class="form-label">
                Orden de aparición
            </label>

            <input id="orden"
                name="orden"
                type="number"
                min="0"
                step="1"
                value="{{ old('orden', $credito->orden ?? 0) }}"
                class="form-control @error('orden') is-invalid @enderror">

            @error('orden')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('admin.peliculas.creditos.index', $pelicula) }}"
                class="btn btn-outline-light">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary">
                {{ $modo === 'crear' ? 'Asignar crédito' : 'Guardar cambios' }}
            </button>
        </div>
    </form>
</div>
@endsection