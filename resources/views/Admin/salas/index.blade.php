@extends('admin.layout')

@section('title', 'Administración de salas')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de salas"
    descripcion="Registra las salas y administra su capacidad informativa, margen de limpieza y estado operativo.">
    <x-slot:action>
        <a href="{{ route('admin.salas.create') }}" class="btn btn-primary">+ Registrar sala</a>
    </x-slot:action>
</x-admin.encabezado-pagina>

@if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert">
    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>@endif

<livewire:admin.salas.tabla-salas />
@endsection