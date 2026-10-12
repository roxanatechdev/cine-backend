@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Nueva película' : 'Editar película')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.peliculas.index') }}"
        class="btn btn-outline-light btn-sm mb-3">
        <span aria-hidden="true">←</span> Volver al catálogo
    </a>

    <div class="d-flex flex-column flex-sm-row justify-content-between
                align-items-sm-start gap-3">
        <div>
            <h1 class="h3 mb-2">
                {{ $modo === 'crear' ? 'Registrar película' : 'Editar película' }}
            </h1>
            <p class="muted mb-0">
                {{ $modo === 'crear'
                    ? 'Añade una nueva película al catálogo de CinePrime.'
                    : 'Actualiza la información y disponibilidad de esta película.' }}
            </p>
        </div>

        @if ($modo === 'editar')
        <span class="badge rounded-pill
                {{ $pelicula->estado === 'ACTIVA' ? 'text-bg-success' : 'text-bg-secondary' }}">
            {{ $pelicula->estado === 'ACTIVA' ? 'Película activa' : 'Película inactiva' }}
        </span>
        @endif
    </div>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <h2 class="h6 fw-bold mb-2">No se pudo guardar la película</h2>
    <p class="mb-2">Revisa los siguientes campos:</p>
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="panel rounded-3 overflow-hidden">
    <form
        method="POST"
        action="{{ $modo === 'crear'
            ? route('admin.peliculas.store')
            : route('admin.peliculas.update', $pelicula) }}">

        @csrf

        @if ($modo === 'editar')
        @method('PUT')
        @endif

        {{-- Información principal --}}
        <section class="p-3 p-md-4 border-bottom border-secondary">
            <div class="mb-4">
                <h2 class="h5 mb-1">Información principal</h2>
                <p class="muted small mb-0">
                    Datos esenciales para identificar la película.
                </p>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label for="titulo" class="form-label">
                        Título <span class="text-danger">*</span>
                    </label>
                    <input
                        id="titulo"
                        name="titulo"
                        type="text"
                        maxlength="150"
                        required
                        autofocus
                        class="form-control @error('titulo') is-invalid @enderror"
                        value="{{ old('titulo', $pelicula->titulo) }}"
                        placeholder="Ej. El Reino de Cristal"
                        aria-describedby="titulo-ayuda">

                    <div id="titulo-ayuda" class="form-text muted">
                        Máximo 150 caracteres.
                    </div>

                    @error('titulo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="sinopsis" class="form-label">
                        Sinopsis <span class="text-danger">*</span>
                    </label>
                    <textarea
                        id="sinopsis"
                        name="sinopsis"
                        rows="4"
                        required
                        class="form-control @error('sinopsis') is-invalid @enderror"
                        placeholder="Describe brevemente la historia de la película."
                        aria-describedby="sinopsis-ayuda">{{ old('sinopsis', $pelicula->sinopsis) }}</textarea>

                    <div id="sinopsis-ayuda" class="form-text muted">
                        Escribe una descripción clara de la trama.
                    </div>

                    @error('sinopsis')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        {{-- Detalles de la película --}}
        <section class="p-3 p-md-4 border-bottom border-secondary">
            <div class="mb-4">
                <h2 class="h5 mb-1">Detalles de la película</h2>
                <p class="muted small mb-0">
                    Información complementaria para el catálogo.
                </p>
            </div>

            <div class="row g-3">

                <div class="col-12 col-md-6">
                    <label for="id_genero_principal" class="form-label">
                        Género principal
                    </label>

                    <select
                        id="id_genero_principal"
                        name="id_genero_principal"
                        class="form-select @error('id_genero_principal') is-invalid @enderror">

                        <option value="">Sin género asignado</option>

                        @foreach ($generos as $genero)
                        <option
                            value="{{ $genero->id_genero }}"
                            @selected(
                            (string) old( 'id_genero_principal' ,
                            $pelicula->id_genero_principal
                            ) === (string) $genero->id_genero
                            )>
                            {{ $genero->nombre }}
                        </option>
                        @endforeach
                    </select>

                    @error('id_genero_principal')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="idioma_original_codigo" class="form-label">
                        Idioma original
                    </label>

                    <select
                        id="idioma_original_codigo"
                        name="idioma_original_codigo"
                        class="form-select @error('idioma_original_codigo') is-invalid @enderror">

                        <option value="">Sin idioma asignado</option>

                        @foreach ($idiomas as $idioma)
                        <option
                            value="{{ $idioma->codigo }}"
                            @selected(
                            (string) old( 'idioma_original_codigo' ,
                            $pelicula->idioma_original_codigo
                            ) === (string) $idioma->codigo
                            )>
                            {{ $idioma->nombre }}
                        </option>
                        @endforeach
                    </select>

                    @error('idioma_original_codigo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                <div class="col-12 col-md-6">
                    <label for="clasificacion" class="form-label">
                        Clasificación por edades
                    </label>
                    <select
                        id="clasificacion"
                        name="clasificacion"
                        class="form-select @error('clasificacion') is-invalid @enderror">
                        <option value="">Sin clasificación</option>

                        @foreach ([
                        'G' => 'G — Audiencia general',
                        'PG' => 'PG — Orientación parental sugerida',
                        'PG-13' => 'PG-13 — Precaución para menores de 13 años',
                        'R' => 'R — Restringida',
                        'NC-17' => 'NC-17 — Solo adultos',
                        ] as $valor => $descripcion)
                        <option
                            value="{{ $valor }}"
                            @selected(old('clasificacion', $pelicula->clasificacion) === $valor)>
                            {{ $descripcion }}
                        </option>
                        @endforeach
                    </select>

                    @error('clasificacion')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="duracion_minutos" class="form-label">
                        Duración (minutos)
                    </label>
                    <input
                        id="duracion_minutos"
                        name="duracion_minutos"
                        type="number"
                        min="1"
                        max="65535"
                        step="1"
                        class="form-control @error('duracion_minutos') is-invalid @enderror"
                        value="{{ old('duracion_minutos', $pelicula->duracion_minutos) }}"
                        placeholder="Ej. 118">

                    @error('duracion_minutos')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="fecha_estreno" class="form-label">
                        Fecha de estreno
                    </label>
                    <input
                        id="fecha_estreno"
                        name="fecha_estreno"
                        type="date"
                        class="form-control @error('fecha_estreno') is-invalid @enderror"
                        value="{{ old('fecha_estreno', $pelicula->fecha_estreno?->format('Y-m-d')) }}">

                    <div class="form-text muted">
                        Se admiten fechas pasadas y futuras válidas.
                    </div>

                    @error('fecha_estreno')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>
        <!-- 
        {{-- Recursos multimedia --}}
        <section class="p-3 p-md-4 border-bottom border-secondary">
            <div class="mb-4">
                <h2 class="h5 mb-1">Recursos multimedia</h2>
                <p class="muted small mb-0">
                    Enlaces al póster y al tráiler, si están disponibles.
                </p>
            </div>

                    <div class="row g-3">
                <div class="col-12">
                    <label for="imagen" class="form-label">
                        URL del póster
                    </label>
                    <input
                        id="imagen"
                        name="imagen"
                        type="url"
                        maxlength="255"
                        class="form-control @error('imagen') is-invalid @enderror"
                        value="{{ old('imagen', $pelicula->imagen) }}"
                        placeholder="https://ejemplo.com/poster.jpg">

                    @error('imagen')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="trailer" class="form-label">
                        URL del tráiler
                    </label>
                    <input
                        id="trailer"
                        name="trailer"
                        type="url"
                        maxlength="255"
                        class="form-control @error('trailer') is-invalid @enderror"
                        value="{{ old('trailer', $pelicula->trailer) }}"
                        placeholder="https://ejemplo.com/trailer">

                    @error('trailer')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>
-->
        {{-- Estado de la película --}}
        @if ($modo === 'editar')
        <section class="p-3 p-md-4 border-bottom border-secondary">
            <div class="mb-3">
                <h2 class="h5 mb-1">Disponibilidad</h2>
                <p class="muted small mb-0">
                    Controla si la película está activa, en borrador o inactiva.
                </p>
            </div>

            <div class="mb-3">
                <label for="estado" class="form-label">Estado</label>

                <select
                    name="estado"
                    id="estado"
                    class="form-select @error('estado') is-invalid @enderror"
                    required>

                    <option value="BORRADOR" @selected(old('estado', $pelicula->estado) === 'BORRADOR')>
                        Borrador
                    </option>

                    <option value="ACTIVA" @selected(old('estado', $pelicula->estado) === 'ACTIVA')>
                        Activa
                    </option>

                    <option value="INACTIVA" @selected(old('estado', $pelicula->estado) === 'INACTIVA')>
                        Inactiva
                    </option>
                </select>

                @error('estado')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </section>
        @else
        {{-- En modo crear se oculta el selector y se envía 'ACTIVA' por defecto de forma silenciosa --}}
        <input type="hidden" name="estado" value="BORRADOR">
        @endif

        <div class="d-flex flex-column flex-sm-row justify-content-between
                    align-items-sm-center gap-3 p-3 p-md-4">
            <p class="muted small mb-0">
                <span class="text-danger">*</span> Campos obligatorios.
            </p>

            <div class="d-flex flex-column flex-sm-row gap-2">
                <a
                    href="{{ route('admin.peliculas.index') }}"
                    class="btn btn-outline-light order-2 order-sm-1">
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="btn btn-primary order-1 order-sm-2">
                    {{ $modo === 'crear' ? 'Guardar película' : 'Guardar cambios' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection