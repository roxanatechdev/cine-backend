@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar persona' : 'Editar persona')

@section('content')
<div class="form-page-header mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h2 font-display fw-bold text-cm-primary mb-1 titulo-main">
                {{ $modo === 'crear' ? 'Registrar persona' : 'Editar persona' }}
            </h1>
            <p class="text-muted mb-0 small">
                Datos de identificación y fotografía.</p>
        </div>
        <a href="{{ route('admin.personas.index') }}" class="btn admin-back-btn d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none">
            <span class="material-symbols-outlined fs-6" aria-hidden="true">arrow_back</span>
            <span>Volver a personas</span>
        </a>
    </div>
</div>

<x-form.errors />
@php
$fotoActual = $persona->foto
? (str_starts_with($persona->foto, 'personas/') ? asset('storage/' . $persona->foto) : $persona->foto)
: null;
@endphp
<x-form.layout title="Datos de la persona" subtitle="Completa la identificación y, si corresponde, la fotografía.">
    <form method="POST" enctype="multipart/form-data" action="{{ $modo === 'crear' ? route('admin.personas.store') : route('admin.personas.update', $persona) }}">
        @csrf
        @if ($modo === 'editar') @method('PUT') @endif

        <section class="form-cm__section">
            <div class="row g-3">
                <div class="col-12">
                    <x-form.field name="nombre_completo" label="Nombre completo" required>
                        <x-form.input name="nombre_completo" maxlength="180" required autofocus :value="$persona->nombre_completo" placeholder="Ej. Christopher Nolan" autocomplete="name" />
                    </x-form.field>
                </div>
                <div class="col-12">
                    <x-form.field name="nombre_busqueda" label="Nombre de búsqueda" help="Se utiliza para localizar a la persona en el catálogo.">
                        <x-form.input name="nombre_busqueda" maxlength="180" :value="$persona->nombre_busqueda" placeholder="Ej. Christopher Nolan" />
                    </x-form.field>
                </div>
            </div>
        </section>

        <section class="form-cm__section">
            <h3 class="form-cm__title">Fotografía</h3>
            <p class="form-cm__subtitle">Puedes subir un archivo o proporcionar una URL HTTPS, pero no ambas fuentes al mismo tiempo.</p>
            <div class="row g-3">
                <div class="col-12">
                    <x-form.image-upload name="archivo" label="Subir foto desde el equipo" id="archivo" accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif" :current-src="$fotoActual" current-label="Fotografía guardada de la persona" url-input="url_foto" help="Opcional. JPG, JPEG, PNG, WebP o AVIF; máximo 5 MB. La foto anterior se conserva hasta guardar correctamente." />
                </div>
                <div class="col-12">
                    <x-form.field name="url_foto" label="URL HTTPS de la fotografía" help="Debe ser una URL HTTPS directa terminada en JPG, JPEG, PNG, WebP o AVIF. No combines este campo con la subida de un archivo.">
                        <x-form.input name="url_foto" type="url" maxlength="255" :value="old('url_foto', '')" placeholder="https://ejemplo.com/persona.jpg" autocomplete="url" />
                    </x-form.field>
                </div>
            </div>
        </section>

        <x-form.actions class="justify-content-between">
            <p class="muted small mb-0"><span class="text-danger">*</span> Campos obligatorios.</p>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <a href="{{ route('admin.personas.index') }}" class="btn btn-outline-light">Cancelar</a>
                <button type="submit" class="btn btn-primary">{{ $modo === 'crear' ? 'Registrar persona' : 'Guardar cambios' }}</button>
            </div>
        </x-form.actions>
    </form>
</x-form.layout>
@endsection