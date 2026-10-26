@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Nuevo producto' : 'Editar producto')

@section('content')

<div class="form-page-header mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <p class="text-uppercase small muted fw-semibold mb-2">Catálogo de confitería</p>
            <h1 class="h2 font-display fw-bold text-cm-primary mb-1 titulo-main">
                {{ $modo === 'crear' ? 'Nuevo producto' : 'Editar producto' }}
            </h1>
            <p class="text-muted mb-0 small">
                Datos generales, variantes comerciales e información promocional.
            </p>
        </div>
        <a href="{{ route('admin.productos.index') }}" class="btn admin-back-btn d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none">
            <span class="material-symbols-outlined fs-6" aria-hidden="true">arrow_back</span>
            <span>Volver a productos</span>
        </a>
    </div>
</div>

<x-form.errors />
@php
$imagenActual = $producto->imagen
? (str_starts_with($producto->imagen, 'productos/') ? asset('storage/' . $producto->imagen) : $producto->imagen)
: null;
$categoriasOpciones = $categorias->mapWithKeys(fn ($categoria) => [
$categoria->id_categoria_producto => $categoria->nombre . (!$categoria->activo ? ' (inactiva)' : ''),
])->all();
@endphp
<x-form.layout title="Información del producto" subtitle="Completa los datos del catálogo, la imagen y las condiciones comerciales.">
    <form method="POST" enctype="multipart/form-data" action="{{ $modo === 'crear' ? route('admin.productos.store') : route('admin.productos.update', $producto) }}">
        @csrf
        @if($modo === 'editar') @method('PUT') @endif

        <section class="form-cm__section">
            <h3 class="form-cm__title">Información general</h3>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <x-form.field name="nombre" label="Nombre" required>
                        <x-form.input name="nombre" maxlength="100" required :value="$producto->nombre" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-6">
                    <x-form.field name="id_categoria_producto" label="Categoría" id="id_categoria_producto_toggle" :show-error="false">
                        <x-form.select name="id_categoria_producto" :options="$categoriasOpciones" :selected="old('id_categoria_producto', $producto->id_categoria_producto)" placeholder="Sin categoría" />
                    </x-form.field>
                </div>
                <div class="col-12">
                    <x-form.field name="descripcion" label="Descripción">
                        <x-form.textarea name="descripcion" maxlength="255" rows="3" :value="$producto->descripcion" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-4">
                    <x-form.field name="precio_actual" label="Precio actual" required>
                        <x-form.input name="precio_actual" type="number" min="0" max="99999999.99" step="0.01" required :value="$producto->precio_actual" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-4">
                    <x-form.field name="stock" label="Stock" help="Déjalo vacío si no se controla inventario.">
                        <x-form.input name="stock" type="number" min="0" step="1" :value="$producto->stock" />
                    </x-form.field>
                </div>
                <div class="col-12 col-md-4">
                    <x-form.field name="estado" label="Estado" id="estado_toggle" required :show-error="false">
                        <x-form.select name="estado" :options="['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo', 'AGOTADO' => 'Agotado']" :selected="old('estado', $producto->estado ?: 'ACTIVO')" required />
                    </x-form.field>
                </div>
                <div class="col-12">
                    <x-form.image-upload name="imagen" label="Imagen del producto" id="imagen" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" :current-src="$imagenActual" current-label="Imagen guardada del producto" help="Opcional. JPG, PNG o WebP; máximo 5 MB. La imagen guardada se conserva si no seleccionas otra. El reemplazo solo se confirma al guardar el formulario." />
                </div>
            </div>
        </section>

        <section class="form-cm__section">
            <h3 class="form-cm__title">Detalles comerciales</h3>
            <p class="form-cm__subtitle">Los campos de tamaño, variante, contenido y peso son opcionales.</p>
            <div class="row g-3">
                <div class="col-12 col-md-6"><x-form.field name="tamano" label="Tamaño"><x-form.input name="tamano" maxlength="50" :value="$producto->tamano" placeholder="Ej. Grande, mediano" /></x-form.field></div>
                <div class="col-12 col-md-6"><x-form.field name="tipo_variante" label="Tipo de variante"><x-form.input name="tipo_variante" maxlength="50" :value="$producto->tipo_variante" placeholder="Ej. Sin azúcar, sabor limón" /></x-form.field></div>
                <div class="col-12 col-md-6"><x-form.field name="contenido_ml" label="Contenido (ml)"><x-form.input name="contenido_ml" type="number" min="0" max="65535" step="1" :value="$producto->contenido_ml" /></x-form.field></div>
                <div class="col-12 col-md-6"><x-form.field name="peso_gramos" label="Peso (gramos)"><x-form.input name="peso_gramos" type="number" min="0" step="1" :value="$producto->peso_gramos" /></x-form.field></div>
            </div>
        </section>

        <section class="form-cm__section">
            <h3 class="form-cm__title">Promoción</h3>
            <p class="form-cm__subtitle">Al activar la promoción, el precio promocional debe ser menor que el precio actual.</p>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="es_promocion" name="es_promocion" value="1" @checked((bool)old('es_promocion', $producto->es_promocion))>
                <label class="form-check-label" for="es_promocion">Este producto tiene promoción</label>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-4"><x-form.field name="precio_promocional" label="Precio promocional"><x-form.input name="precio_promocional" type="number" min="0" max="99999999.99" step="0.01" :value="$producto->precio_promocional" /></x-form.field></div>
                <div class="col-12 col-md-4"><x-form.field name="promocion_inicio" label="Inicio de promoción"><x-form.input name="promocion_inicio" type="datetime-local" :value="$producto->promocion_inicio ? $producto->promocion_inicio->format('Y-m-d\\TH:i') : ''" /></x-form.field></div>
                <div class="col-12 col-md-4"><x-form.field name="promocion_fin" label="Fin de promoción"><x-form.input name="promocion_fin" type="datetime-local" :value="$producto->promocion_fin ? $producto->promocion_fin->format('Y-m-d\\TH:i') : ''" /></x-form.field></div>
            </div>
            <p class="form-cm__text mt-3 mb-0">Las fechas se guardan como datos del periodo promocional. La aplicación automática de promociones vigentes no forma parte de este formulario.</p>
        </section>

        <x-form.actions class="justify-content-end">
            <a href="{{ route('admin.productos.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button class="btn btn-primary" type="submit">{{ $modo === 'crear' ? 'Registrar producto' : 'Guardar cambios' }}</button>
        </x-form.actions>
    </form>
</x-form.layout>
@endsection