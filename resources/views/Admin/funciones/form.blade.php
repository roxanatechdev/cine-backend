@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar función' : 'Editar función')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.funciones.index') }}" class="btn btn-outline-light btn-sm mb-3">← Volver a funciones</a>
    <h1 class="h3 mb-2">{{ $modo === 'crear' ? 'Registrar función' : 'Editar función' }}</h1>
    <p class="muted mb-0">La programación valida la duración de la película y el margen de limpieza de la sala para evitar solapamientos.</p>
</div>

@if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if ($peliculas->isEmpty())<div class="alert alert-warning" role="alert">No hay películas activas con duración válida. Debes activar una película y registrar su duración antes de programarla.</div>@endif
@if ($salas->isEmpty())<div class="alert alert-warning" role="alert">No hay salas activas disponibles para programar funciones.</div>@endif

<div class="panel rounded-3 p-3 p-md-4">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.funciones.store') : route('admin.funciones.update', $funcion->id_funcion) }}">
        @csrf
        @if ($modo === 'editar') @method('PUT') @endif

        <div class="mb-3">
            <label for="id_pelicula" class="form-label">Película <span class="text-danger">*</span></label>
            <select id="id_pelicula" name="id_pelicula" required class="form-select @error('id_pelicula') is-invalid @enderror">
                <option value="">Selecciona una película</option>
                @foreach ($peliculas as $pelicula)
                <option value="{{ $pelicula->id_pelicula }}" @selected((string) old('id_pelicula', $funcion->id_pelicula) === (string) $pelicula->id_pelicula)>{{ $pelicula->titulo }} ({{ $pelicula->duracion_minutos }} min)</option>
                @endforeach
            </select>
            @error('id_pelicula')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="id_sala" class="form-label">Sala <span class="text-danger">*</span></label>
            <select id="id_sala" name="id_sala" required class="form-select @error('id_sala') is-invalid @enderror">
                <option value="">Selecciona una sala</option>
                @foreach ($salas as $sala)
                <option value="{{ $sala->id_sala }}" @selected((string) old('id_sala', $funcion->id_sala) === (string) $sala->id_sala)>{{ $sala->nombre }}{{ $sala->estado === 'ACTIVA' ? '' : ' ('.$sala->estado.')' }} — limpieza: {{ $sala->margen_limpieza_minutos }} min</option>
                @endforeach
            </select>
            @error('id_sala')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="inicio" class="form-label">Fecha y hora de inicio <span class="text-danger">*</span></label>
                <input id="inicio" name="inicio" type="datetime-local" required value="{{ old('inicio', $funcion->inicio?->format('Y-m-d\\TH:i')) }}" class="form-control @error('inicio') is-invalid @enderror">
                @error('inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="formato" class="form-label">Formato <span class="text-danger">*</span></label>
                <select id="formato" name="formato" required class="form-select @error('formato') is-invalid @enderror">
                    @foreach ($formatos as $formato)
                    <option value="{{ $formato }}" @selected(old('formato', $funcion->formato ?? '2D') === $formato)>{{ $formato }}</option>
                    @endforeach
                </select>
                @error('formato')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="precio_base" class="form-label">Precio base <span class="text-danger">*</span></label>
                <input id="precio_base" name="precio_base" type="number" min="0" max="99999999.99" step="0.01" required value="{{ old('precio_base', $funcion->precio_base) }}" class="form-control @error('precio_base') is-invalid @enderror">
                @error('precio_base')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                <select id="estado" name="estado" required class="form-select @error('estado') is-invalid @enderror">
                    @foreach ($estados as $estado)
                    <option value="{{ $estado }}" @selected(old('estado', $funcion->estado ?? 'PROGRAMADA') === $estado)>{{ str_replace('_', ' ', $estado) }}</option>
                    @endforeach
                </select>
                @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('admin.funciones.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary" @disabled($peliculas->isEmpty() || $salas->isEmpty())>{{ $modo === 'crear' ? 'Registrar función' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>
@endsection
