@extends('Admin.layout')

@section('title', 'Países de producción')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.peliculas.index') }}"
        class="btn btn-outline-light btn-sm mb-3">
        ← Volver a películas
    </a>

    <h1 class="h3 mb-2">Países de producción</h1>

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
            <h2 class="h5 mb-1">Países asociados</h2>
            <p class="muted small mb-0">
                Gestiona los países vinculados a esta producción.
            </p>
        </div>

        <a href="{{ route('admin.peliculas.paises.create', $pelicula) }}"
            class="btn btn-primary">
            + Asignar país
        </a>
    </div>

    @if ($paises->count())
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>País</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($paises as $pais)
                <tr>
                    <td>{{ $pais->codigo }}</td>
                    <td>{{ $pais->nombre }}</td>
                    <td class="text-end">
                        <form method="POST"
                            action="{{ route(
                                          'admin.peliculas.paises.destroy',
                                          [$pelicula, $pais->codigo]
                                      ) }}"
                            class="d-inline"
                            onsubmit="return confirm(
                                          '¿Deseas quitar este país de la película?'
                                      )">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="btn btn-outline-danger btn-sm">
                                Quitar
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($paises, 'links'))
    <div class="p-3">
        {{ $paises->links() }}
    </div>
    @endif
    @else
    <div class="p-4 text-center">
        <p class="mb-2">
            Esta película todavía no tiene países de producción.
        </p>

        <p class="muted small mb-0">
            Asigna uno o varios países para completar su información.
        </p>
    </div>
    @endif
</div>
@endsection