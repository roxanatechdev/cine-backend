@extends('admin.layout')

@section('title', 'Administración de asientos')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-2">Administración de asientos</h1>
    <p class="muted mb-0">Administra la ubicación, el tipo y la disponibilidad de los asientos físicos por sala.</p>
</div>

@if (session('success'))
<div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="panel rounded-3 overflow-hidden">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 p-3 p-md-4 border-bottom border-secondary">
        <div>
            <h2 class="h5 mb-1">Asientos registrados</h2>
            <p class="muted small mb-0">{{ $asientos->total() }} {{ $asientos->total() === 1 ? 'asiento registrado' : 'asientos registrados' }}</p>
        </div>
        <a href="{{ route('admin.asientos.create') }}" class="btn btn-primary">+ Registrar asiento</a>
    </div>

    @if ($asientos->count())
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead><tr><th>Sala</th><th>Fila</th><th>Número</th><th>Tipo</th><th>Activo</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @foreach ($asientos as $asiento)
                <tr>
                    <td class="fw-semibold">{{ $asiento->sala?->nombre ?? 'Sala no disponible' }}</td>
                    <td>{{ $asiento->fila }}</td>
                    <td>{{ $asiento->numero }}</td>
                    <td>{{ $asiento->tipo }}</td>
                    <td><span class="badge bg-{{ $asiento->activo ? 'success' : 'secondary' }}">{{ $asiento->activo ? 'Sí' : 'No' }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.asientos.edit', $asiento->id_asiento) }}" class="btn btn-outline-light btn-sm">Editar</a>
                        <form method="POST" action="{{ route('admin.asientos.destroy', $asiento->id_asiento) }}" class="d-inline" onsubmit="return confirm('¿Eliminar este asiento? Si está asociado a alguna función, no podrá eliminarse.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $asientos->links() }}</div>
    @else
    <div class="p-4 text-center">
        <h3 class="h5">Todavía no hay asientos registrados</h3>
        <p class="muted mb-3">Registra un asiento y asígnalo a una sala.</p>
        <a href="{{ route('admin.asientos.create') }}" class="btn btn-primary">Registrar primer asiento</a>
    </div>
    @endif
</div>
@endsection
