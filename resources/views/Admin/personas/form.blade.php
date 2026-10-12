@extends('admin.layout')

@section('title', $modo === 'crear'
? 'Registrar persona'
: 'Editar persona')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.personas.index') }}"
        class="btn btn-outline-light btn-sm mb-3">
        ← Volver a personas
    </a>

    <h1 class="h3 mb-2">
        {{ $modo === 'crear' ? 'Registrar persona' : 'Editar persona' }}
    </h1>

    <p class="muted mb-0">
        Datos de identificación y fotografía.
    </p>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <h2 class="h6 fw-bold">No se pudo guardar la persona</h2>
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
            ? route('admin.personas.store')
            : route('admin.personas.update', $persona) }}">

        @csrf

        @if ($modo === 'editar')
        @method('PUT')
        @endif

        <section class="p-3 p-md-4 border-bottom border-secondary">
            <h2 class="h5 mb-3">Datos de la persona</h2>

            <div class="row g-3">
                <div class="col-12">
                    <label for="nombre_completo" class="form-label">
                        Nombre completo <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="nombre_completo"
                        name="nombre_completo"
                        maxlength="150"
                        required
                        autofocus
                        class="form-control @error('nombre_completo') is-invalid @enderror"
                        value="{{ old('nombre_completo', $persona->nombre_completo) }}"
                        placeholder="Ej. Christopher Nolan">

                    @error('nombre_completo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="nombre_busqueda" class="form-label">
                        Nombre de búsqueda <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="nombre_busqueda"
                        name="nombre_busqueda"
                        maxlength="150"
                        required
                        class="form-control @error('nombre_busqueda') is-invalid @enderror"
                        value="{{ old('nombre_busqueda', $persona->nombre_busqueda) }}"
                        placeholder="Ej. Christopher Nolan">

                    <div class="form-text muted">
                        Se utiliza para localizar a la persona en el catálogo.
                    </div>

                    @error('nombre_busqueda')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        <section class="p-3 p-md-4 border-bottom border-secondary">
            <h2 class="h5 mb-1">Fotografía</h2>
            <p class="muted small mb-4">
                Opcional. Sube una imagen o proporciona una URL HTTPS directa.
            </p>

            @if ($modo === 'editar' && $persona->foto)
            <div class="mb-4">
                <p class="form-label">Foto actual</p>

                <img
                    src="{{ str_starts_with($persona->foto, 'personas/')
                            ? asset('storage/' . $persona->foto)
                            : $persona->foto }}"
                    alt="Foto actual de {{ $persona->nombre_completo }}"
                    class="img-thumbnail"
                    style="max-width: 180px; max-height: 240px; object-fit: contain;">

                <p class="form-text muted mt-2">
                    Si no eliges otra imagen ni introduces una URL,
                    se conservará esta fotografía.
                </p>
            </div>
            @endif

            <div class="mb-3">
                <label for="archivo" class="form-label">
                    Subir foto desde el equipo
                </label>

                <input
                    type="file"
                    id="archivo"
                    name="archivo"
                    accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif"
                    class="form-control @error('archivo') is-invalid @enderror">

                <div class="form-text muted">
                    JPG, JPEG, PNG, WebP o AVIF. Máximo 5 MB.
                </div>

                @error('archivo')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="url_foto" class="form-label">
                    URL HTTPS de la fotografía
                </label>

                <input
                    type="url"
                    id="url_foto"
                    name="url_foto"
                    maxlength="500"
                    class="form-control @error('url_foto') is-invalid @enderror"
                    value="{{ old('url_foto') }}"
                    placeholder="https://ejemplo.com/persona.jpg">

                <div class="form-text muted">
                    Utiliza una URL directa terminada en JPG, PNG, WebP o AVIF.
                    No combines este campo con la subida de un archivo.
                </div>

                @error('url_foto')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </section>

        <div class="d-flex flex-column flex-sm-row
                    justify-content-between align-items-sm-center
                    gap-3 p-3 p-md-4">

            <p class="muted small mb-0">
                <span class="text-danger">*</span> Campos obligatorios.
            </p>

            <div class="d-flex flex-column flex-sm-row gap-2">
                <a href="{{ route('admin.personas.index') }}"
                    class="btn btn-outline-light">
                    Cancelar
                </a>

                <button type="submit" class="btn btn-primary">
                    {{ $modo === 'crear'
                        ? 'Registrar persona'
                        : 'Guardar cambios' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection