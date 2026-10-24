@extends('admin.layout')

@section('title', 'Multimedia de la película')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'multimedia']) }}" class="btn btn-outline-light btn-sm mb-3">
        &larr; Volver al editor de película
    </a>
    <h1 class="h3 mt-2 mb-2">Recursos multimedia</h1>
    <p class="muted mb-0">Película: <strong class="text-light">{{ $pelicula->titulo }}</strong></p>
</div>

@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger mb-4" role="alert">
        <strong>No se pudo completar la operación.</strong>
        <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
@include('admin.multimedia._listado')
@endsection
