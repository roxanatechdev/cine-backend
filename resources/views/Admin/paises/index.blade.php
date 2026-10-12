@extends('admin.layout')

@section('title', 'Países de producción')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-2">Países de producción</h1>

    <p class="muted mb-0">
        Administra los países disponibles para registrar
        la procedencia de las películas.
    </p>
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

<div class="panel rounded-3 overflow-hidden">
    <div class="d-flex flex-column flex-sm-row
                justify-content-between align-items-sm-center
                gap-3 p-3 p-md-4 border-bottom border-secondary">
        <div>
            <h2 class="h5 mb-1">Catálogo de países</h2>
            <p class="muted small mb-0">
                {{ $paises->total() }}
                {{ $paises->total() === 1 ? 'país registrado' : 'países registrados' }}
            </p>
        </div>

        <a href="{{ route('admin.paises.create') }}"
            class="btn btn-primary">
            + Registrar país
        </a>
    </div>

    @if ($paises->count())
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($paises as $pais)
                <tr>
                    <td>
                        <span class="badge bg-secondary">
                            {{ $pais->codigo }}
                        </span>
                    </td>

                    <td>{{ $pais->nombre }}</td>

                    <td class="text-end">
                        <a href="{{ route('admin.paises.edit', $pais->codigo) }}"
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
        {{ $paises->links() }}
    </div>
    @else
    <div class="p-4 text-center">
        <p class="mb-2">Todavía no hay países registrados.</p>

        <p class="muted small mb-0">
            Registra el primero para comenzar a construir el catálogo.
        </p>
    </div>
    @endif
</div>
@endsection