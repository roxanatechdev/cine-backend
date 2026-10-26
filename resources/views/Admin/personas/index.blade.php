@extends('admin.layout')

@section('title', 'Personas')

@section('content')
<x-admin.encabezado-pagina
    titulo="Personas"
    descripcion="Gestiona actores, directores y otros profesionales del cine."
>
    <x-slot:action>
        <a href="{{ route('admin.personas.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
            <span class="material-symbols-outlined" aria-hidden="true">person_add</span>
            Registrar persona
        </a>
    </x-slot:action>
</x-admin.encabezado-pagina>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<livewire:admin.personas.tabla-personas />
@endsection
