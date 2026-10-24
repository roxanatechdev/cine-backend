@extends('Admin.layout')
@section('title', 'Productos')
@section('content')
<x-admin.encabezado-pagina
    titulo="Productos"
    descripcion="Administra productos, variantes, inventario y promociones."
>
    <x-slot:action>
        <a href="{{ route('admin.productos.create') }}" class="btn btn-primary">+ Nuevo producto</a>
    </x-slot:action>
</x-admin.encabezado-pagina>
<x-admin.tabla.contenedor class="admin-table-card">
 <x-admin.tabla.filtros tag="div" class="admin-table-filters p-3 p-md-4"><form method="GET" action="{{ route('admin.productos.index') }}" class="row g-2 align-items-end">
  <div class="col-12 col-lg-4"><label for="q" class="form-label">Nombre</label><input class="form-control" id="q" name="q" maxlength="100" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar producto..."></div>
  <div class="col-12 col-md-5 col-lg-3"><label for="categoria" class="form-label">Categoría</label><select class="form-select" id="categoria" name="categoria"><option value="">Todas</option>@foreach($categorias as $categoria)<option value="{{ $categoria->id_categoria_producto }}" @selected((string)($filtros['categoria'] ?? '') === (string)$categoria->id_categoria_producto)>{{ $categoria->nombre }}</option>@endforeach</select></div>
  <div class="col-12 col-md-4 col-lg-3"><label for="estado" class="form-label">Estado</label><select class="form-select" id="estado" name="estado"><option value="">Todos</option>@foreach(['ACTIVO'=>'Activo','INACTIVO'=>'Inactivo','AGOTADO'=>'Agotado'] as $valor=>$etiqueta)<option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>@endforeach</select></div>
  <div class="col-12 col-md-3 col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Filtrar</button><a href="{{ route('admin.productos.index') }}" class="btn btn-outline-light">Limpiar</a></div>
 </form></x-admin.tabla.filtros>
 <div class="px-3 px-md-4 py-3 border-bottom border-secondary"><h2 class="h6 mb-1">Listado</h2><p class="admin-table-count mb-0">{{ $productos->total() }} producto(s)</p></div>
 @if($productos->count())<div class="table-responsive"><table class="table table-hover align-middle admin-table"><thead><tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Promoción</th><th class="text-end">Acciones</th></tr></thead><tbody>
 @foreach($productos as $producto)<tr><td><div class="d-flex align-items-center gap-2">@if($producto->imagen)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($producto->imagen) }}" alt="" style="width:52px;height:52px;object-fit:cover;border-radius:8px">@endif<div class="fw-semibold">{{ $producto->nombre }}<div class="small muted">#{{ $producto->id_producto }}@if($producto->tamano) · {{ $producto->tamano }}@endif</div></div></div></td><td>{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</td><td>{{ number_format((float)$producto->precio_actual, 2) }}@if($producto->es_promocion && $producto->precio_promocional)<div class="small text-info">Promo: {{ number_format((float)$producto->precio_promocional, 2) }}</div>@endif</td><td>{{ is_null($producto->stock) ? 'No controlado' : $producto->stock }}</td><td><span class="badge {{ $producto->estado === 'ACTIVO' ? 'text-bg-success' : ($producto->estado === 'AGOTADO' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ $producto->estado }}</span></td><td>{{ $producto->es_promocion ? 'Sí' : 'No' }}</td><td class="text-end"><div class="d-inline-flex flex-wrap justify-content-end gap-2"><a href="{{ route('admin.productos.edit',$producto) }}"
 class="btn admin-action-icon"
 data-bs-toggle="tooltip"
 data-bs-placement="top"
 title="Editar"
 aria-label="Editar producto">
 <span class="material-symbols-outlined" aria-hidden="true">edit</span>
</a><form method="POST" action="{{ route('admin.productos.estado',$producto) }}">@csrf @method('PATCH')<select name="estado" class="form-select form-select-sm mb-1" aria-label="Cambiar estado">@foreach(['ACTIVO'=>'Activo','INACTIVO'=>'Inactivo','AGOTADO'=>'Agotado'] as $valor=>$etiqueta)<option value="{{ $valor }}" @selected($producto->estado === $valor)>{{ $etiqueta }}</option>@endforeach</select><button class="btn btn-outline-info btn-sm">Guardar estado</button></form></div></td></tr>@endforeach
 </tbody></table></div><x-admin.tabla.paginacion id="paginacion" class="admin-table-footer p-3 p-md-4">{{ $productos->links() }}</x-admin.tabla.paginacion>
 @else<div class="admin-table-empty"><h2 class="h5">No hay productos para mostrar</h2><p class="muted">Cambia los filtros o registra el primer producto.</p><a href="{{ route('admin.productos.create') }}" class="btn btn-primary">Crear producto</a></div>@endif
</x-admin.tabla.contenedor>
@endsection
