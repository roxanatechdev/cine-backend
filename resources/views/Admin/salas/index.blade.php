@extends('admin.layout')

@section('title', 'Administración de salas')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-2">Administración de salas</h1>
    <p class="muted mb-0">
        Registra las salas y administra su capacidad informativa, margen de limpieza y estado operativo.
    </p>
</div>

@if (session('error'))
<div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif

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
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 p-3 p-md-4 border-bottom border-secondary">
        <div>
            <h2 class="h5 mb-1">Salas registradas</h2>
            <p class="muted small mb-0">
                {{ $salas->total() }} {{ $salas->total() === 1 ? 'sala registrada' : 'salas registradas' }}
            </p>
        </div>
        <a href="{{ route('admin.salas.create') }}" class="btn btn-primary">+ Registrar sala</a>
    </div>

    @if ($salas->count())
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Capacidad informativa</th>
                    <th>Margen de limpieza</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($salas as $sala)
                <tr>
                    <td class="fw-semibold">{{ $sala->nombre }}</td>
                    <td>{{ $sala->capacidad === null ? 'No especificada' : $sala->capacidad }}</td>
                    <td>{{ $sala->margen_limpieza_minutos }} min</td>
                    <td>
                        @php
                            $estadoClase = match ($sala->estado) {
                                'ACTIVA' => 'success',
                                'INACTIVA' => 'secondary',
                                'MANTENIMIENTO' => 'warning text-dark',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $estadoClase }}">{{ $sala->estado }}</span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.salas.edit', $sala->id_sala) }}" class="btn btn-outline-light btn-sm">Editar</a>
                        <form method="POST" action="{{ route('admin.salas.destroy', $sala->id_sala) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta sala? Si tiene asientos o funciones asociados, no podrá eliminarse.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $salas->links() }}</div>
    @else
    <div class="p-4 text-center">
        <h3 class="h5">Todavía no hay salas registradas</h3>
        <p class="muted mb-3">Registra la primera sala para comenzar a administrar el catálogo.</p>
        <a href="{{ route('admin.salas.create') }}" class="btn btn-primary">Registrar primera sala</a>
    </div>
    @endif
</div>
@endsection
