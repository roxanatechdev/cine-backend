<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductoAdminController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(['ACTIVO', 'INACTIVO', 'AGOTADO'])],
            'categoria' => ['nullable', 'integer', 'min:1'],
        ]);

        $productos = Producto::query()->with('categoria')
            ->when($filtros['q'] ?? null, fn ($q, $nombre) => $q->where('nombre', 'like', '%' . $nombre . '%'))
            ->when(($filtros['estado'] ?? '') !== '', fn ($q) => $q->where('estado', $filtros['estado']))
            ->when(($filtros['categoria'] ?? '') !== '', fn ($q) => $q->where('id_categoria_producto', $filtros['categoria']))
            ->orderBy('nombre')->paginate(10)->withQueryString();

        $categorias = CategoriaProducto::query()->orderBy('nombre')->get();
        return view('Admin.productos.index', compact('productos', 'categorias', 'filtros'));
    }

    public function create()
    {
        return view('Admin.productos.form', [
            'producto' => new Producto(['estado' => 'ACTIVO', 'es_promocion' => false]),
            'categorias' => CategoriaProducto::query()->where('activo', 1)->orderBy('nombre')->get(),
            'modo' => 'crear',
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $datos['es_promocion'] = $request->boolean('es_promocion');
        $this->validarPromocion($datos);
        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $request->file('imagen')->store('productos', 'public');
        }
        Producto::create($datos);
        return redirect()->route('admin.productos.index')->with('success', 'Producto registrado correctamente.');
    }

    public function edit(Producto $producto)
    {
        return view('Admin.productos.form', [
            'producto' => $producto,
            'categorias' => CategoriaProducto::query()->orderBy('nombre')->get(),
            'modo' => 'editar',
        ]);
    }

    public function update(Request $request, Producto $producto)
    {
        $datos = $this->validar($request, $producto);
        $datos['es_promocion'] = $request->boolean('es_promocion');
        $this->validarPromocion($datos);
        if ($request->hasFile('imagen')) {
            if ($producto->imagen) Storage::disk('public')->delete($producto->imagen);
            $datos['imagen'] = $request->file('imagen')->store('productos', 'public');
        } else {
            unset($datos['imagen']);
        }
        $producto->update($datos);
        return redirect()->route('admin.productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, Producto $producto)
    {
        $datos = $request->validate(['estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO', 'AGOTADO'])]]);
        $producto->estado = $datos['estado'];
        $producto->save();
        return redirect()->route('admin.productos.index', $request->only('q', 'estado', 'categoria', 'page'))
            ->with('success', 'Estado del producto actualizado correctamente.');
    }

    private function validar(Request $request, ?Producto $producto = null): array
    {
        return $request->validate([
            'id_categoria_producto' => ['nullable', 'integer', 'exists:categoria_producto,id_categoria_producto'],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'precio_actual' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO', 'AGOTADO'])],
            'tamano' => ['nullable', 'string', 'max:50'],
            'tipo_variante' => ['nullable', 'string', 'max:50'],
            'contenido_ml' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'peso_gramos' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'es_promocion' => ['nullable', 'boolean'],
            'precio_promocional' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'promocion_inicio' => ['nullable', 'date'],
            'promocion_fin' => ['nullable', 'date', 'after_or_equal:promocion_inicio'],
        ]);
    }

    private function validarPromocion(array $datos): void
    {
        if (!($datos['es_promocion'] ?? false)) return;
        if (!isset($datos['precio_promocional']) || $datos['precio_promocional'] === '') {
            throw ValidationException::withMessages(['precio_promocional' => 'Indica el precio promocional cuando la promoción está activada.']);
        }
        if ((float) $datos['precio_promocional'] >= (float) $datos['precio_actual']) {
            throw ValidationException::withMessages(['precio_promocional' => 'El precio promocional debe ser menor que el precio actual.']);
        }
        if (!empty($datos['promocion_inicio']) && !empty($datos['promocion_fin']) && $datos['promocion_fin'] < $datos['promocion_inicio']) {
            throw ValidationException::withMessages(['promocion_fin' => 'La fecha final debe ser igual o posterior a la fecha inicial.']);
        }
    }
}
