@extends('admin.layout')

@section('title', 'Géneros cinematográficos')

@section('content')
<x-admin.encabezado-pagina
    titulo="Géneros cinematográficos"
    descripcion="Administra el catálogo de géneros que se asignan a las películas.">
    <x-slot:action>
        <a href="{{ route('admin.generos.create') }}" class="btn btn-primary">+ Registrar género</a>
    </x-slot:action>
</x-admin.encabezado-pagina>

@if (session('success'))
<div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<livewire:admin.generos.tabla-generos />
@endsection