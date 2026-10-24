@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar sala' : 'Editar sala')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.salas.index') }}" class="btn btn-outline-light btn-sm mb-3">← Volver a salas</a>
    <h1 class="h3 mb-2">{{ $modo === 'crear' ? 'Registrar sala' : 'Editar sala' }}</h1>
    <p class="muted mb-0">Completa los datos de la sala. La capacidad es informativa; los asientos son la fuente de verdad del aforo.</p>
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

<div class="panel rounded-3 p-3 p-md-4">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.salas.store') : route('admin.salas.update', $sala->id_sala) }}">
        @csrf
        @if ($modo === 'editar')
        @method('PUT')
        @endif

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre de la sala <span class="text-danger">*</span></label>
            <input id="nombre" name="nombre" type="text" maxlength="50" required autofocus
                value="{{ old('nombre', $sala->nombre) }}"
                class="form-control @error('nombre') is-invalid @enderror" placeholder="Ej. Sala 1">
            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="capacidad" class="form-label">Capacidad informativa</label>
            <input id="capacidad" name="capacidad" type="number" min="1" max="65535"
                value="{{ old('capacidad', $sala->capacidad) }}"
                class="form-control @error('capacidad') is-invalid @enderror" placeholder="Opcional">
            <div class="form-text muted">Opcional. El aforo real se obtiene de los asientos registrados.</div>
            @error('capacidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="margen_limpieza_minutos" class="form-label">Margen de limpieza (minutos) <span class="text-danger">*</span></label>
            <input id="margen_limpieza_minutos" name="margen_limpieza_minutos" type="number" min="0" max="65535" required
                value="{{ old('margen_limpieza_minutos', $sala->margen_limpieza_minutos ?? 30) }}"
                class="form-control @error('margen_limpieza_minutos') is-invalid @enderror">
            <div class="form-text muted">Se utiliza al considerar el intervalo entre funciones.</div>
            @error('margen_limpieza_minutos')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
            <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
            <select id="estado" name="estado" required class="form-select @error('estado') is-invalid @enderror">
                @foreach ($estados as $estado)
                <option value="{{ $estado }}" @selected(old('estado', $sala->estado ?? 'ACTIVA') === $estado)>
                    {{ $estado === 'ACTIVA' ? 'Activa' : ($estado === 'INACTIVA' ? 'Inactiva' : 'Mantenimiento') }}
                </option>
                @endforeach
            </select>
            @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('admin.salas.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">{{ $modo === 'crear' ? 'Registrar sala' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>
@endsection
