@extends('Admin.layout')

@section('title', 'Asignar país de producción')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.peliculas.paises.index', $pelicula) }}"
        class="btn btn-outline-light btn-sm mb-3">
        ← Volver a países
    </a>

    <h1 class="h3 mb-2">Asignar país de producción</h1>

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
        action="{{ route('admin.peliculas.paises.store', $pelicula) }}">
        @csrf

        <div class="mb-4">
            <label for="codigo_pais" class="form-label">
                País <span class="text-danger">*</span>
            </label>

            <select id="codigo_pais"
                name="codigo_pais"
                class="form-select @error('codigo_pais') is-invalid @enderror"
                required>
                <option value="">Selecciona un país</option>

                @foreach ($paises as $pais)
                <option value="{{ $pais->codigo }}"
                    @selected(
                    (string) old('codigo_pais', '' )===(string) $pais->codigo
                    )>
                    {{ $pais->nombre }} ({{ $pais->codigo }})
                </option>
                @endforeach
            </select>

            @error('codigo_pais')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            @if ($paises->isEmpty())
            <div class="form-text muted">
                No hay países disponibles para asignar.
            </div>
            @endif
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('admin.peliculas.paises.index', $pelicula) }}"
                class="btn btn-outline-light">
                Cancelar
            </a>

            <button type="submit"
                class="btn btn-primary"
                @disabled($paises->isEmpty())>
                Asignar país
            </button>
        </div>
    </form>
</div>
@endsection