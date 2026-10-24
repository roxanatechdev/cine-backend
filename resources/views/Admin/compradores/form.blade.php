@extends('admin.layout')

@section('title', $modo === 'crear' ? 'Registrar comprador' : 'Editar comprador')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.compradores.index') }}" class="btn btn-outline-light btn-sm mb-3">← Volver a compradores</a>
    <h1 class="h3 mb-2">{{ $modo === 'crear' ? 'Registrar comprador' : 'Editar comprador' }}</h1>
    <p class="muted mb-0">El registro de creación lo asigna automáticamente la base de datos.</p>
</div>

@if ($errors->any())
<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="panel rounded-3 p-3 p-md-4">
    <form method="POST" action="{{ $modo === 'crear' ? route('admin.compradores.store') : route('admin.compradores.update', $comprador->id_comprador) }}">
        @csrf
        @if ($modo === 'editar') @method('PUT') @endif

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre completo <span class="text-danger">*</span></label>
            <input id="nombre" name="nombre" type="text" maxlength="100" required autofocus value="{{ old('nombre', $comprador->nombre) }}" class="form-control @error('nombre') is-invalid @enderror">
            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="correo" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
            <input id="correo" name="correo" type="email" maxlength="150" required value="{{ old('correo', $comprador->correo) }}" class="form-control @error('correo') is-invalid @enderror">
            <div class="form-text muted">El esquema indexa el correo, pero no exige que sea único.</div>
            @error('correo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="telefono" class="form-label">Teléfono</label>
            <input id="telefono" name="telefono" type="text" maxlength="30" value="{{ old('telefono', $comprador->telefono) }}" class="form-control @error('telefono') is-invalid @enderror" placeholder="Opcional">
            @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label for="tipo_comprador" class="form-label">Tipo de comprador <span class="text-danger">*</span></label>
            <select id="tipo_comprador" name="tipo_comprador" required class="form-select @error('tipo_comprador') is-invalid @enderror">
                @foreach ($tipos as $tipo)
                <option value="{{ $tipo }}" @selected(old('tipo_comprador', $comprador->tipo_comprador ?? 'INVITADO') === $tipo)>{{ $tipo === 'INVITADO' ? 'Invitado' : 'Registrado' }}</option>
                @endforeach
            </select>
            @error('tipo_comprador')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('admin.compradores.index') }}" class="btn btn-outline-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">{{ $modo === 'crear' ? 'Registrar comprador' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>
@endsection
