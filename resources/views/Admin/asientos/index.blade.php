@extends('admin.layout')

@section('title', 'Administración de asientos')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de asientos"
    descripcion="Administra la ubicación, el tipo y la disponibilidad de los asientos físicos por sala.">
    <x-slot:action>
        <a href="{{ route('admin.asientos.create') }}" class="btn btn-primary"><span class="material-symbols-outlined align-middle me-1" aria-hidden="true">add</span>Registrar asiento</a>
    </x-slot:action>
</x-admin.encabezado-pagina>

@if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<livewire:admin.asientos.tabla-asientos />
@endsection
