@extends('admin.layout')

@section('title', $modo === 'crear'
? 'Nuevo recurso multimedia'
: 'Editar recurso multimedia')

@section('content')


@php
$urlActual = $multimedia->url ?? '';

$esArchivoLocal = str_starts_with(
$urlActual,
'peliculas/'
);

// Si el recurso es local, no mostrar su ruta interna
// en el campo destinado a URLs públicas.
$urlFormulario = old('url');

if ($urlFormulario === null) {
$urlFormulario = $esArchivoLocal
? ''
: $urlActual;
} elseif (str_starts_with($urlFormulario, 'peliculas/')) {
// Evitar recuperar una ruta local de un envío anterior.
$urlFormulario = '';
}
@endphp


<div class="mb-4">
    <a href="{{ route('admin.peliculas.multimedia.index', $pelicula) }}"
        class="btn btn-outline-light btn-sm mb-3">
        ← Volver a los recursos multimedia
    </a>

    <h1 class="h3 mb-2">
        {{ $modo === 'crear'
            ? 'Registrar recurso multimedia'
            : 'Editar recurso multimedia' }}
    </h1>

    <p class="muted mb-0">
        Película: <strong>{{ $pelicula->titulo }}</strong>
    </p>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <h2 class="h6 fw-bold mb-2">
        No se pudo guardar el recurso
    </h2>

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
        enctype="multipart/form-data"
        action="{{ $modo === 'crear'
        ? route('admin.peliculas.multimedia.store', $pelicula)
        : route('admin.peliculas.multimedia.update', [
            $pelicula,
            $multimedia
        ]) }}">

        @csrf

        @if ($modo === 'editar')
        @method('PUT')
        @endif


        {{-- Tipo y dirección del recurso --}}
        <section class="p-3 p-md-4 border-bottom border-secondary">
            <div class="mb-4">
                <h2 class="h5 mb-1">Recurso multimedia</h2>
                <p class="muted small mb-0">
                    Define el tipo de recurso y su dirección pública.
                </p>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="tipo" class="form-label">
                        Tipo de recurso
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        id="tipo"
                        name="tipo"
                        required
                        class="form-select @error('tipo') is-invalid @enderror">

                        @foreach ([
                        'POSTER' => 'Póster',
                        'TRAILER' => 'Tráiler',
                        'IMAGEN' => 'Imagen adicional',
                        'VIDEO' => 'Video adicional',
                        ] as $valor => $etiqueta)
                        <option
                            value="{{ $valor }}"
                            @selected(
                            old('tipo', $multimedia->tipo) === $valor
                            )>
                            {{ $etiqueta }}
                        </option>
                        @endforeach
                    </select>

                    @error('tipo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                {{-- Archivo local: solo para pósteres e imágenes --}}
                <div class="col-12" id="contenedor-archivo">
                    <label for="archivo" class="form-label">
                        Subir imagen desde el equipo
                    </label>

                    <input
                        id="archivo"
                        name="archivo"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif"
                        class="form-control @error('archivo') is-invalid @enderror">

                    <div class="form-text muted">
                        Formatos permitidos: JPG, JPEG, PNG, WebP y AVIF.
                        Tamaño máximo: 5 MB.
                    </div>

                    @error('archivo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                {{-- URL pública para imágenes o videos --}}
                <div class="col-12" id="contenedor-url">
                    <label for="url" class="form-label">
                        URL pública del recurso
                    </label>

                    <input
                        id="url"
                        name="url"
                        type="url"
                        maxlength="500"
                        class="form-control @error('url') is-invalid @enderror"
                        value="{{ $urlFormulario }}"
                        placeholder="https://ejemplo.com/recurso">

                    <div class="form-text muted" id="ayuda-url">
                        Para imágenes, utiliza una URL HTTPS directa.
                        Para videos, utiliza un enlace válido de YouTube.
                    </div>

                    @error('url')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if ($modo === 'editar' && in_array(
                $multimedia->tipo,
                ['POSTER', 'IMAGEN'],
                true
                ))
                <div class="col-12">
                    <p class="form-label">Imagen actual</p>

                    <img
                        src="{{ str_starts_with($multimedia->url, 'peliculas/')
                ? asset('storage/' . $multimedia->url)
                : $multimedia->url }}"
                        alt="{{ $multimedia->texto_alternativo
                ?: 'Imagen actual del recurso multimedia' }}"
                        class="img-thumbnail"
                        style="max-width: 240px; max-height: 320px; object-fit: contain;">

                    <p class="form-text muted mt-2">
                        Si no seleccionas otra imagen ni cambias la URL,
                        se conservará la imagen actual.
                    </p>
                </div>
                @endif


                <div class="col-12">
                    <label for="texto_alternativo" class="form-label">
                        Texto alternativo
                    </label>

                    <input
                        id="texto_alternativo"
                        name="texto_alternativo"
                        type="text"
                        maxlength="255"
                        class="form-control @error('texto_alternativo') is-invalid @enderror"
                        value="{{ old(
                            'texto_alternativo',
                            $multimedia->texto_alternativo
                        ) }}"
                        placeholder="Ej. Póster oficial de la película">

                    <div class="form-text muted">
                        Recomendado para pósteres e imágenes.
                    </div>

                    @error('texto_alternativo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        {{-- Configuración del recurso --}}
        <section class="p-3 p-md-4 border-bottom border-secondary">
            <div class="mb-4">
                <h2 class="h5 mb-1">Configuración</h2>
                <p class="muted small mb-0">
                    Controla la visibilidad, prioridad y posición del recurso.
                </p>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="orden" class="form-label">
                        Orden de visualización
                    </label>

                    <input
                        id="orden"
                        name="orden"
                        type="number"
                        min="0"
                        max="65535"
                        step="1"
                        class="form-control @error('orden') is-invalid @enderror"
                        value="{{ old('orden', $multimedia->orden ?? 0) }}">

                    <div class="form-text muted">
                        Los valores menores aparecen primero.
                    </div>

                    @error('orden')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <div class="form-check mb-3">
                        <input
                            id="es_principal"
                            name="es_principal"
                            type="checkbox"
                            value="1"
                            class="form-check-input @error('es_principal') is-invalid @enderror"
                            @checked(old( 'es_principal' ,
                            $multimedia->es_principal ?? false
                        ))>

                        <label for="es_principal" class="form-check-label">
                            Marcar como recurso principal de este tipo
                        </label>

                        @error('es_principal')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    <div class="form-check">
                        <input
                            id="activo"
                            name="activo"
                            type="checkbox"
                            value="1"
                            class="form-check-input @error('activo') is-invalid @enderror"
                            @checked(old( 'activo' ,
                            $multimedia->exists
                        ? $multimedia->activo
                        : true
                        ))>

                        <label for="activo" class="form-check-label">
                            Recurso activo y disponible para mostrarse
                        </label>

                        @error('activo')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- Acciones --}}
        <div class="d-flex flex-column flex-sm-row
                    justify-content-between align-items-sm-center
                    gap-3 p-3 p-md-4">

            <p class="muted small mb-0">
                <span class="text-danger">*</span>
                Campos obligatorios.
            </p>

            <div class="d-flex flex-column flex-sm-row gap-2">
                <a
                    href="{{ route(
                        'admin.peliculas.multimedia.index',
                        $pelicula
                    ) }}"
                    class="btn btn-outline-light order-2 order-sm-1">
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="btn btn-primary order-1 order-sm-2">
                    {{ $modo === 'crear'
                        ? 'Guardar recurso'
                        : 'Guardar cambios' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipo = document.getElementById('tipo');
        const contenedorArchivo =
            document.getElementById('contenedor-archivo');
        const archivo = document.getElementById('archivo');
        const ayudaUrl = document.getElementById('ayuda-url');
        const url = document.getElementById('url');

        function actualizarCampos() {
            const esImagen = ['POSTER', 'IMAGEN'].includes(tipo.value);

            contenedorArchivo.hidden = !esImagen;

            if (!esImagen) {
                archivo.value = '';
            }

            ayudaUrl.textContent = esImagen ?
                'Puedes subir una imagen local o indicar una URL HTTPS directa, pero no ambas.' :
                'Introduce un enlace válido de YouTube.';

            url.placeholder = esImagen ?
                'https://ejemplo.com/imagen.jpg' :
                'https://www.youtube.com/watch?v=XXXXXXXXXXX';
        }

        tipo.addEventListener('change', actualizarCampos);
        actualizarCampos();
    });
</script>