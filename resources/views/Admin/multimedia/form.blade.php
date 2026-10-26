@extends('Admin.layout')

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
    <a href="{{ route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'multimedia']) }}"
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

<x-form.errors />

<x-form.layout title="Recurso multimedia" subtitle="Gestiona la fuente, la vista previa y la configuración del recurso.">

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
        <section class="form-cm__section">
            <div class="mb-4">
                <h3 class="form-cm__title mb-1">Recurso multimedia</h3>
                <p class="muted small mb-0">
                    Define el tipo de recurso y su dirección pública.
                </p>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <x-form.field name="tipo" label="Tipo de recurso" id="tipo_toggle" required :show-error="false">
                        <x-form.select
                            name="tipo"
                            id="tipo"
                            :options="[
                                'POSTER' => 'Póster',
                                'TRAILER' => 'Tráiler',
                                'IMAGEN' => 'Imagen adicional',
                                'VIDEO' => 'Video adicional',
                            ]"
                            :selected="old('tipo', $multimedia->tipo ?? 'POSTER')"
                            />
                    </x-form.field>
                </div>


                {{-- Archivo local: solo para pósteres e imágenes --}}
                <div class="col-12" id="contenedor-archivo">
                    <label for="archivo" class="form-cm__label">
                        <span>Subir imagen desde el equipo</span>
                    </label>

                    <input
                        id="archivo"
                        name="archivo"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif"
                        class="form-cm__control @error('archivo') is-invalid @enderror">

                    <div class="form-cm__text">
                        Formatos permitidos: JPG, JPEG, PNG, WebP y AVIF.
                        Tamaño máximo: 5 MB.
                    </div>
                    <button type="button" id="descartar-archivo" class="btn btn-sm btn-outline-light mt-2" hidden>
                        Descartar archivo seleccionado
                    </button>

                    @error('archivo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                {{-- URL pública para imágenes o videos --}}
                <div class="col-12" id="contenedor-url">
                    <label for="url" class="form-cm__label">
                        <span>URL pública del recurso</span>
                    </label>

                    <input
                        id="url"
                        name="url"
                        type="url"
                        maxlength="500"
                        class="form-cm__control @error('url') is-invalid @enderror"
                        value="{{ $urlFormulario }}"
                        placeholder="https://ejemplo.com/recurso">

                    <div class="form-cm__text" id="ayuda-url">
                        Para imágenes, utiliza una URL HTTPS directa.
                        Para videos, utiliza un enlace válido de YouTube.
                    </div>

                    @error('url')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Vista previa y estado de la fuente antes de guardar --}}
                <div class="col-12" id="contenedor-vista-previa"
                    data-current-src="{{ $multimedia->exists ? (str_starts_with($multimedia->url, 'peliculas/') ? asset('storage/' . $multimedia->url) : $multimedia->url) : '' }}"
                    data-current-type="{{ $multimedia->tipo ?? 'POSTER' }}">
                    <div class="form-cm__label">Vista previa</div>
                    <div id="vista-previa-marco" class="form-cm__media-preview rounded-3 p-3">
                        <p id="vista-previa-estado" class="form-cm__text mb-0" role="status" aria-live="polite">
                            Selecciona una imagen o introduce una URL para comprobar el recurso antes de guardarlo.
                        </p>
                        <div id="vista-previa-contenido" class="mt-2"></div>
                    </div>
                    <p class="form-cm__text mt-2 mb-0" id="vista-previa-ayuda">
                        La vista previa permite comprobar que la fuente se puede mostrar; el recurso se guardará solo cuando pulses el botón correspondiente.
                    </p>
                </div>




                <div class="col-12">
                    <label for="texto_alternativo" class="form-cm__label">
                        Texto alternativo
                    </label>

                    <input
                        id="texto_alternativo"
                        name="texto_alternativo"
                        type="text"
                        maxlength="255"
                        class="form-cm__control @error('texto_alternativo') is-invalid @enderror"
                        value="{{ old(
                            'texto_alternativo',
                            $multimedia->texto_alternativo
                        ) }}"
                        placeholder="Ej. Póster oficial de la película">

                    <div class="form-cm__text">
                        Recomendado para pósteres e imágenes.
                    </div>

                    @error('texto_alternativo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        {{-- Configuración del recurso --}}
        <section class="form-cm__section">
            <div class="mb-4">
                <h3 class="form-cm__title mb-1">Configuración</h3>
                <p class="muted small mb-0">
                    Controla la visibilidad, prioridad y posición del recurso.
                </p>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="orden" class="form-cm__label">
                        Orden de visualización
                    </label>

                    <input
                        id="orden"
                        name="orden"
                        type="number"
                        min="0"
                        max="65535"
                        step="1"
                        class="form-cm__control @error('orden') is-invalid @enderror"
                        value="{{ old('orden', $multimedia->orden ?? 0) }}">

                    <div class="form-cm__text">
                        Los valores menores aparecen primero.
                    </div>

                    @error('orden')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <div class="form-cm__check mb-3">
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

                    <div class="form-cm__check">
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
        <div class="form-cm__actions d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">

            <p class="muted small mb-0">
                <span class="text-danger">*</span>
                Campos obligatorios.
            </p>

            <div class="d-flex flex-column flex-sm-row gap-2">
                <a
                    href="{{ route(
                        'admin.peliculas.edit',
                        ['pelicula' => $pelicula, 'tab' => 'multimedia']
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
</x-form.layout>
@endsection


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipo = document.getElementById('tipo');
        const contenedorArchivo = document.getElementById('contenedor-archivo');
        const archivo = document.getElementById('archivo');
        const descartarArchivo = document.getElementById('descartar-archivo');
        const ayudaUrl = document.getElementById('ayuda-url');
        const url = document.getElementById('url');
        const preview = document.getElementById('vista-previa-contenido');
        const estado = document.getElementById('vista-previa-estado');
        const previewContainer = document.getElementById('contenedor-vista-previa');
        let objectUrlActual = null;
        let revisionPreview = 0;

        function establecerEstado(mensaje) {
            estado.textContent = mensaje;
        }

        function limpiarPreview() {
            preview.replaceChildren();
        }

        function mostrarImagen(src, esLocal) {
            const revision = ++revisionPreview;
            limpiarPreview();
            establecerEstado(esLocal ? 'Cargando vista previa del archivo local…' : 'Comprobando la URL de la imagen…');

            const imagen = document.createElement('img');
            imagen.alt = 'Vista previa del recurso multimedia';
            imagen.className = 'img-thumbnail';
            imagen.classList.add('form-cm__media-preview-image');
            imagen.onload = function() {
                if (revision !== revisionPreview) return;
                establecerEstado(esLocal
                    ? 'Archivo local disponible para guardar.'
                    : 'Imagen disponible desde la URL.');
            };
            imagen.onerror = function() {
                if (revision !== revisionPreview) return;
                establecerEstado(esLocal
                    ? 'No se pudo leer la imagen seleccionada. Prueba con otro archivo.'
                    : 'No se pudo cargar la imagen desde esta URL. Comprueba que sea pública y directa.');
            };
            imagen.src = src;
            preview.appendChild(imagen);
        }

        function extraerIdYoutube(valor) {
            try {
                const parsed = new URL(valor);
                if (parsed.protocol !== 'https:') return null;
                const host = parsed.hostname.toLowerCase();
                if (host === 'youtu.be') {
                    const id = parsed.pathname.split('/').filter(Boolean)[0] || '';
                    return /^[A-Za-z0-9_-]{11}$/.test(id) ? id : null;
                }
                if (!['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'].includes(host)) return null;
                const segments = parsed.pathname.split('/').filter(Boolean);
                let id = '';
                if (parsed.pathname === '/watch') id = parsed.searchParams.get('v') || '';
                else if (['embed', 'shorts'].includes(segments[0])) id = segments[1] || '';
                return /^[A-Za-z0-9_-]{11}$/.test(id) ? id : null;
            } catch (_) {
                return null;
            }
        }

        function mostrarVideo(valor) {
            const id = extraerIdYoutube(valor);
            const revision = ++revisionPreview;
            limpiarPreview();
            if (!id) {
                establecerEstado(valor.trim()
                    ? 'Introduce una URL HTTPS válida de YouTube para ver la vista previa.'
                    : 'Introduce un enlace de YouTube para comprobar el video.');
                return;
            }

            establecerEstado('Cargando vista previa del video…');
            const marco = document.createElement('div');
            marco.className = 'form-cm__video-frame';
            const iframe = document.createElement('iframe');
            iframe.src = 'https://www.youtube-nocookie.com/embed/' + id;
            iframe.title = 'Vista previa del video de YouTube';
            iframe.loading = 'lazy';
            iframe.referrerPolicy = 'strict-origin-when-cross-origin';
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            iframe.allowFullscreen = true;
            iframe.className = 'form-cm__video-frame-embed';
            iframe.addEventListener('load', function() {
                if (revision === revisionPreview) {
                    establecerEstado('Vista previa del video cargada. La disponibilidad de reproducción puede depender de la configuración del video.');
                }
            });
            marco.appendChild(iframe);
            preview.appendChild(marco);
        }

        function actualizarVistaPrevia() {
            const esImagen = ['POSTER', 'IMAGEN'].includes(tipo.value);
            const archivoSeleccionado = archivo.files && archivo.files[0];
            const urlIntroducida = url.value.trim();

            if (objectUrlActual) {
                URL.revokeObjectURL(objectUrlActual);
                objectUrlActual = null;
            }
            if (descartarArchivo) descartarArchivo.hidden = !(archivoSeleccionado && esImagen);

            if (archivoSeleccionado && esImagen) {
                if (urlIntroducida) {
                    limpiarPreview();
                    ++revisionPreview;
                    establecerEstado('Elige una sola fuente: archivo local o URL, no ambas.');
                    return;
                }
                objectUrlActual = URL.createObjectURL(archivoSeleccionado);
                if (descartarArchivo) descartarArchivo.hidden = false;
                mostrarImagen(objectUrlActual, true);
                establecerEstado('Archivo seleccionado: ' + archivoSeleccionado.name + '. Aún no está guardado.');
                return;
            }

            if (urlIntroducida) {
                if (esImagen) mostrarImagen(urlIntroducida, false);
                else mostrarVideo(urlIntroducida);
                return;
            }

            const fuenteActual = previewContainer.dataset.currentSrc;
            const tipoActual = previewContainer.dataset.currentType;
            if (fuenteActual && tipoActual === tipo.value) {
                if (esImagen) mostrarImagen(fuenteActual, false);
                else mostrarVideo(fuenteActual);
                return;
            }

            limpiarPreview();
            ++revisionPreview;
            establecerEstado(esImagen
                ? 'Selecciona una imagen local o introduce una URL HTTPS para ver la vista previa.'
                : 'Introduce una URL de YouTube para ver la vista previa del video.');
        }

        function actualizarCampos() {
            const esImagen = ['POSTER', 'IMAGEN'].includes(tipo.value);
            contenedorArchivo.hidden = !esImagen;
            if (!esImagen) archivo.value = '';

            ayudaUrl.textContent = esImagen
                ? 'Puedes subir una imagen local o indicar una URL HTTPS directa, pero no ambas.'
                : 'Introduce un enlace válido de YouTube.';
            url.placeholder = esImagen
                ? 'https://ejemplo.com/imagen.jpg'
                : 'https://www.youtube.com/watch?v=XXXXXXXXXXX';
            actualizarVistaPrevia();
        }

        if (descartarArchivo) descartarArchivo.addEventListener('click', function() {
            archivo.value = '';
            actualizarVistaPrevia();
            archivo.focus();
        });
        tipo.addEventListener('change', actualizarCampos);
        archivo.addEventListener('change', actualizarVistaPrevia);
        url.addEventListener('input', actualizarVistaPrevia);
        actualizarCampos();
    });
</script>
