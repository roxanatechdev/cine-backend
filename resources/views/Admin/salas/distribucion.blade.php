@extends('admin.layout')

@section('title', 'Distribución visual · ' . $sala->nombre)

@section('content')
<div class="dist-page-shell">
<div class="dist-page-header mb-4">
    <a href="{{ route('admin.salas.index') }}" class="btn btn-outline-light btn-sm mb-3">← Volver a salas</a>
    <h1 class="h3 mb-2">Distribución visual de {{ $sala->nombre }}</h1>
    <p class="muted mb-0">Selecciona una celda para agregar un asiento o selecciona uno existente para administrar su tipo y disponibilidad.</p>
</div>

@if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif

<livewire:admin.salas.editor-distribucion-sala :sala-id="$sala->id_sala" />
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-distribucion-salas.css') }}?v=17">
@endpush
