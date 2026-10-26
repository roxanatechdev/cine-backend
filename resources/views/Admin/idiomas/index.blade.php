@extends('admin.layout')

@section('title', 'Idiomas')

@section('content')
<x-admin.encabezado-pagina
    titulo="Catálogo de idiomas"
    descripcion="Administra los códigos de idioma BCP 47 utilizados por las películas.">
    <x-slot:action>
        <a href="{{ route('admin.idiomas.create') }}" class="btn btn-primary">+ Registrar idioma</a>
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
<livewire:admin.idiomas.tabla-idiomas />
@endsection