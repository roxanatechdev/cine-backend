@extends('admin.layout')

@section('title', 'Créditos de película')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.peliculas.index') }}"
        class="btn btn-outline-light btn-sm mb-3">
        ← Volver a películas
    </a>

    <h1 class="h3 mb-2">Créditos de la película</h1>
    <p class="muted mb-0">
        Película: <strong>{{ $pelicula->titulo }}</strong>
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
            <h2 class="h5 mb-1">Personas acreditadas</h2>
            <p class="muted small mb-0">
                Reparto, dirección y demás participaciones.
            </p>
        </div>

        <a href="{{ route('admin.peliculas.creditos.create', $pelicula) }}"
            class="btn btn-primary">
            + Asignar crédito
        </a>
    </div>

    @if ($creditos->count())
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Persona</th>
                    <th>Rol</th>
                    <th>Personaje</th>
                    <th>Orden</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($creditos as $credito)
                <tr>
                    <td>
                        {{ $credito->persona->nombre_completo }}
                    </td>
                    <td>{{ $credito->rol }}</td>
                    <td>{{ $credito->personaje ?: '—' }}</td>
                    <td>{{ $credito->orden ?? '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route(
                                    'admin.peliculas.creditos.edit',
                                    [$pelicula, $credito->id_persona, $credito->rol]
                                ) }}"
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
        {{ $creditos->links() }}
    </div>
    @else
    <div class="p-4 text-center">
        <p class="mb-2">Esta película todavía no tiene créditos.</p>
        <p class="muted small mb-0">
            Asigna personas para registrar su participación.
        </p>
    </div>
    @endif
</div>
@endsection