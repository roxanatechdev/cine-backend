@extends('admin.layout')

@section('title', 'Administración de funciones')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de funciones"
    descripcion="Programa proyecciones y administra sus formatos, precios y estados.">
    <x-slot:action>
        <a href="{{ route('admin.funciones.create') }}" class="btn btn-primary">+ Registrar función</a>
    </x-slot:action>
</x-admin.encabezado-pagina>

@if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert">
    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>@endif

<livewire:admin.funciones.tabla-funciones />
@endsection