@extends('Admin.layout')

@section('title', 'Categorías de productos')

@section('content')
    <x-admin.encabezado-pagina
        titulo="Categorías de productos"
        descripcion="Organiza los productos de confitería por categoría y administra su estado."
    >
        <x-slot:action>
            <a href="{{ route('admin.categorias-producto.create') }}" class="btn btn-primary">
                <span class="material-symbols-outlined align-middle" aria-hidden="true">add</span>
                Registrar categoría
            </a>
        </x-slot:action>
    </x-admin.encabezado-pagina>

    @if ($errors->any())
        <div class="alert alert-danger mt-3" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <livewire:admin.categorias-producto.tabla-categorias-producto />
@endsection
