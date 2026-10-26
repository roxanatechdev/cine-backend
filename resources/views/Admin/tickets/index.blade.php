@extends('admin.layout')

@section('title', 'Administración de tickets')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de tickets"
    descripcion="Consulta los tickets emitidos y su relación con compras y entradas. Los estados se modifican desde el flujo de validación o de compra.">
    <x-slot:action>
        <a href="{{ route('admin.compras.index') }}" class="btn btn-outline-light">Ver compras</a>
    </x-slot:action>
</x-admin.encabezado-pagina>

@if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert">
    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>@endif

<livewire:admin.tickets.tabla-tickets />
@endsection