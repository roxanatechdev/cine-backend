@extends('Admin.layout')

@section('title', 'Países de producción')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="admin-page-title">Países de producción</h1>
        <p class="muted mb-0">Administra los países asociados a las películas.</p>
    </div>
    <a href="{{ route('admin.paises.create') }}" class="btn btn-primary">+ Registrar país</a>
</div>

@if (session('success'))
<div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="panel mb-4">
    <form method="GET" action="{{ route('admin.paises.index') }}" class="p-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-9">
                <label for="buscar" class="form-label">Buscar por código o nombre</label>
                <input id="buscar" name="buscar" value="{{ $buscar }}" class="form-control" maxlength="100" placeholder="Ej.: CL, Chile, Argentina">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button class="btn btn-outline-info flex-grow-1" type="submit">Buscar</button>
                <a class="btn btn-outline-light" href="{{ route('admin.paises.index') }}">Limpiar</a>
            </div>
        </div>
    </form>
</div>

<div class="panel overflow-hidden">
    <div class="p-3 border-bottom border-secondary">
        <p class="muted mb-0">{{ $paises->total() }} {{ $paises->total() === 1 ? 'país registrado' : 'países registrados' }}</p>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead><tr><th>Código ISO</th><th>Nombre</th><th>Películas asociadas</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse ($paises as $pais)
                <tr>
                    <td><code>{{ $pais->codigo }}</code></td>
                    <td>{{ $pais->nombre }}</td>
                    <td>{{ $pais->peliculas_count }}</td>
                    <td class="text-end"><a href="{{ route('admin.paises.edit', $pais->codigo) }}" class="btn btn-outline-light btn-sm">Editar</a></td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center muted py-4">No hay países que coincidan con la búsqueda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($paises->hasPages())<div class="p-3">{{ $paises->links() }}</div>@endif
</div>
@endsection
