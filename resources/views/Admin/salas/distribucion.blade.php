@extends('admin.layout')

@section('title', 'Distribución visual · ' . $sala->nombre)

@section('content')
<div class="dist-page-shell">
    <div class="dist-page-header mb-3">
        <a href="{{ route('admin.salas.index') }}" class="btn admin-back-btn btn-sm d-inline-flex align-items-center gap-1 mb-3">
            <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
            Volver a salas
        </a>
        <h1 class="h3 mb-1">Distribución visual de {{ $sala->nombre }}</h1>
        <p class="text-cm-variant mb-0">Edita el plano como una pizarra: los cambios quedan en un borrador y solo se guardan al pulsar «Guardar distribución».</p>
    </div>

    <livewire:admin.salas.editor-distribucion-sala :sala-id="$sala->id_sala" />
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-distribucion-salas.css') }}?v=18">
@endpush

@push('scripts')
{{-- Debe cargarse antes de @livewireScripts (el layout imprime este stack antes) para registrar Alpine.data en 'alpine:init'. --}}
<script src="{{ asset('js/editor-distribucion-salas.js') }}?v=18"></script>
@endpush
