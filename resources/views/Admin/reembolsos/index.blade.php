@extends('admin.layout')

@section('title', 'Administración de reembolsos')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de reembolsos"
    descripcion="Consulta las solicitudes de devolución, su estado y la trazabilidad con el pago y la compra.">
    <x-slot:action>
        <a href="{{ route('admin.pagos.index') }}" class="btn btn-outline-light">Ver pagos</a>
    </x-slot:action>
</x-admin.encabezado-pagina>

@if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<livewire:admin.reembolsos.tabla-reembolsos />
@endsection
