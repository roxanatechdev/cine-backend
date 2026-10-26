@extends('admin.layout')

@section('title', 'Administración de compras')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de compras"
    descripcion="Consulta el historial y gestiona transiciones de estado sin editar importes ni eliminar registros." />

@if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert">
    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<livewire:admin.compras.tabla-compras />
@endsection