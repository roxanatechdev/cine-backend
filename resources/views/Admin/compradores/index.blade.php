@extends('admin.layout')

@section('title', 'Administración de compradores')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de compradores"
    descripcion="Consulta y administra los datos de contacto y el tipo de comprador.">

    <x-slot:action>
        <a href="{{ route('admin.compradores.create') }}" class="btn btn-primary">+ Registrar cliente</a>
    </x-slot:action>
</x-admin.encabezado-pagina>

@if (session('success'))
<div class="alert alert-success" role="alert">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<livewire:admin.compradores.tabla-compradores />
@endsection