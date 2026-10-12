@extends('admin.layout')

@section('title', 'Personas')

@section('content')
<div class="d-flex flex-column flex-md-row
            justify-content-between align-items-md-center gap-3 mb-4">

    <div>
        <h1 class="h3 mb-1">Personas</h1>
        <p class="muted mb-0">
            Gestiona actores, directores y otros profesionales del cine.
        </p>
    </div>

    <a href="{{ route('admin.personas.create') }}"
        class="btn btn-primary">
        + Registrar persona
    </a>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="panel rounded-3 p-3 p-md-4 mb-4">
    <form method="GET"
        action="{{ route('admin.personas.index') }}"
        class="row g-3 align-items-end">

        <div class="col-12 col-md-8">
            <label for="buscar" class="form-label">
                Buscar persona
            </label>

            <input
                type="search"
                id="buscar"
                name="buscar"
                maxlength="150"
                class="form-control"
                value="{{ $filtros['buscar'] ?? '' }}"
                placeholder="Nombre completo o nombre de búsqueda">
        </div>

        <div class="col-12 col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                Buscar
            </button>

            <a href="{{ route('admin.personas.index') }}"
                class="btn btn-outline-light">
                Limpiar
            </a>
        </div>
    </form>
</div>

<div class="panel rounded-3 overflow-hidden">
    @if ($personas->isEmpty())
    <div class="p-5 text-center">
        <h2 class="h5">No se encontraron personas</h2>
        <p class="muted mb-3">
            Registra una persona o modifica los términos de búsqueda.
        </p>

        <a href="{{ route('admin.personas.create') }}"
            class="btn btn-primary">
            Registrar persona
        </a>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Foto</th>
                    <th scope="col">Nombre completo</th>
                    <th scope="col">Nombre de búsqueda</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($personas as $persona)
                <tr>
                    <td>
                        @if ($persona->foto)
                        <img
                            src="{{ str_starts_with($persona->foto, 'personas/')
                                            ? asset('storage/' . $persona->foto)
                                            : $persona->foto }}"
                            alt="Foto de {{ $persona->nombre_completo }}"
                            loading="lazy"
                            width="52"
                            height="64"
                            class="rounded"
                            style="object-fit: cover;">
                        @else
                        <span class="muted small">
                            Sin foto
                        </span>
                        @endif
                    </td>

                    <td>
                        {{ $persona->nombre_completo }}
                    </td>

                    <td>
                        {{ $persona->nombre_busqueda }}
                    </td>

                    <td class="text-end">
                        <a
                            href="{{ route('admin.personas.edit', $persona) }}"
                            class="btn btn-outline-light btn-sm">
                            Editar
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-3">
        {{ $personas->links() }}
    </div>
    @endif
</div>
@endsection