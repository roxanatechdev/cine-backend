@extends('admin.layout')

@section('title', 'Países')

@section('content')
<x-admin.encabezado-pagina
    titulo="Catálogo de países"
    descripcion="Administra los países de procedencia asociados a las películas.">
    <x-slot:action>
        <a href="{{ route('admin.paises.create') }}" class="btn btn-primary">+ Registrar país</a>
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
<livewire:admin.paises.tabla-paises />
@endsection