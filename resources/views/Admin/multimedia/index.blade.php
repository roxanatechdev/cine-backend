@extends('admin.layout')

@section('title', 'Multimedia de la película')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.peliculas.index') }}"
        class="btn btn-outline-light btn-sm mb-3">
        &larr; Volver al catálogo
    </a>

    <div class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-center gap-3">
        <div>
            <span class="text-info small fw-semibold text-uppercase">
                CinePrime / Administración
            </span>

            <h1 class="h3 mt-2 mb-2">Recursos multimedia</h1>

            <p class="muted mb-0">
                Película:
                <strong class="text-light">
                    {{ $pelicula->titulo }}
                </strong>
            </p>
        </div>

        <a href="{{ route(
            'admin.peliculas.multimedia.create',
            $pelicula
        ) }}" class="btn btn-primary">
            + Añadir recurso
        </a>
    </div>
</div>


@if ($errors->any())
<div class="alert alert-danger mb-4" role="alert">
    <strong>No se pudo completar la operación.</strong>

    <ul class="mb-0 mt-2">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<section class="panel p-3 p-lg-4"
    aria-labelledby="multimedia-titulo">

    <div class="d-flex flex-column flex-sm-row
                justify-content-between align-items-sm-center
                gap-2 mb-4">
        <div>
            <h2 id="multimedia-titulo" class="h5 mb-1">
                Recursos registrados
            </h2>

            <p class="muted small mb-0">
                Imágenes, pósteres y vídeos asociados a esta película.
            </p>
        </div>

        <span class="badge text-bg-secondary align-self-start">
            {{ $multimedia->total() }}
            {{ $multimedia->total() === 1 ? 'recurso' : 'recursos' }}
        </span>
    </div>

    @forelse ($multimedia as $recurso)
    <article class="border border-secondary rounded-3 p-3 mb-3">
        <div class="row g-3 align-items-start">

            {{-- Vista previa --}}
            <div class="col-12 col-md-3 col-lg-2">
                @if (in_array($recurso->tipo, ['POSTER', 'IMAGEN']))
                <div class="bg-dark rounded overflow-hidden" style="width: 100%; height: 180px;">
                    <img
                        src="{{ str_starts_with($recurso->url, 'peliculas/')
                    ? asset('storage/' . $recurso->url)
                    : $recurso->url }}"
                        alt="{{ $recurso->texto_alternativo
                    ?: 'Imagen de ' . $pelicula->titulo }}"
                        class="w-100 h-100"
                        style="object-fit: cover;"
                        loading="lazy">
                </div>
                @else
                <div class="ratio ratio-3x4 bg-dark rounded
                                    d-flex align-items-center justify-content-center">
                    <div class="text-center p-3">
                        <span class="text-danger fs-1"
                            aria-hidden="true">&#9654;</span>

                        <p class="small muted mb-0 mt-2">
                            {{ $recurso->tipo === 'TRAILER'
                                        ? 'Tráiler'
                                        : 'Vídeo' }}
                        </p>
                    </div>
                </div>
                @endif
            </div>

            {{-- Información --}}
            <div class="col-12 col-md-9 col-lg-10">
                <div class="d-flex flex-column flex-sm-row
                                justify-content-between gap-3">

                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge text-bg-dark border border-secondary">
                                {{ $recurso->tipo }}
                            </span>

                            @if ($recurso->es_principal)
                            <span class="badge text-bg-info">
                                Recurso principal
                            </span>
                            @endif

                            @if ($recurso->activo)
                            <span class="badge text-bg-success">
                                Activo
                            </span>
                            @else
                            <span class="badge text-bg-secondary">
                                Inactivo
                            </span>
                            @endif
                        </div>

                        <p class="small mb-2">
                            <strong>URL:</strong>
                            <a href="{{ $recurso->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-info text-break">
                                {{ $recurso->url }}
                            </a>
                        </p>

                        <p class="small mb-2">
                            <strong>Texto alternativo:</strong>
                            <span class="muted">
                                {{ $recurso->texto_alternativo ?: 'No definido' }}
                            </span>
                        </p>

                        <p class="small muted mb-0">
                            Orden: {{ $recurso->orden }}
                        </p>
                    </div>

                    <div class="flex-shrink-0">
                        <a href="{{ route(
                                'admin.peliculas.multimedia.edit',
                                [$pelicula, $recurso]
                            ) }}" class="btn btn-outline-info btn-sm">
                            Editar
                        </a>
                    </div>

                    @if ($recurso->activo)
                    <form
                        method="POST"
                        action="{{ route(
            'admin.peliculas.multimedia.desactivar',
            [$pelicula, $recurso]
        ) }}"
                        onsubmit="return confirm(
            '¿Deseas desactivar este recurso? El archivo se conservará.'
        )">

                        @csrf
                        @method('PATCH')

                        <button type="submit" class="btn btn-outline-warning btn-sm">
                            Desactivar
                        </button>
                    </form>
                    @endif
                </div>
            </div>



        </div>
    </article>
    @empty
    <div class="text-center py-5">
        <div class="text-info mb-3 fs-1" aria-hidden="true">
            &#128444;
        </div>

        <h3 class="h6 fw-semibold mb-2">
            Esta película todavía no tiene recursos multimedia
        </h3>

        <p class="muted mb-3">
            Añade un póster, una imagen o un enlace de vídeo
            para completar su contenido visual.
        </p>

        <a href="{{ route(
                'admin.peliculas.multimedia.create',
                $pelicula
            ) }}" class="btn btn-primary btn-sm">
            Añadir primer recurso
        </a>
    </div>
    @endforelse

    {{-- Navegación entre páginas --}}
    @if ($multimedia->hasPages())
    <div class="mt-4">
        {{ $multimedia->links() }}
    </div>
    @endif
</section>
@endsection