@extends('admin.layout')

@section('title', 'Administración de pagos')

@section('content')
<x-admin.encabezado-pagina
    titulo="Administración de pagos"
    descripcion="Consulta de intentos de pago. El estado y los importes se gestionan desde el flujo transaccional de cobro.">
    <x-slot:action>
        <a href="{{ route('admin.reembolsos.index') }}" class="btn btn-primary">Administrar reembolsos</a>
    </x-slot:action>
</x-admin.encabezado-pagina>


<livewire:admin.pagos.tabla-pagos />
@endsection