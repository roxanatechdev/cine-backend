@extends('Admin.layout')
@section('title', 'Productos')
@section('content')
<x-admin.encabezado-pagina
    titulo="Productos"
    descripcion="Administra productos, variantes, inventario y promociones.">
    <x-slot:action>
        <a href="{{ route('admin.productos.create') }}" class="btn btn-primary">
            <span class="material-symbols-outlined align-middle" aria-hidden="true">add</span>
            Nuevo producto
        </a>
    </x-slot:action>
</x-admin.encabezado-pagina>
<livewire:admin.productos.tabla-productos />
@endsection