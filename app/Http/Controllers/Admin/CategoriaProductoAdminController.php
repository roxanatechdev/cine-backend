<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProducto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaProductoAdminController extends Controller
{
    /** Mostrar el catálogo con búsqueda y filtro de estado. */
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'estado' => ['nullable', Rule::in(['0', '1'])],
        ]);

        $categorias = CategoriaProducto::query()
            ->when($filtros['q'] ?? null, function ($query, $nombre) {
                $query->where('nombre', 'like', '%' . $nombre . '%');
            })
            ->when(array_key_exists('estado', $filtros) && $filtros['estado'] !== null && $filtros['estado'] !== '', function ($query) use ($filtros) {
                $query->where('activo', (int) $filtros['estado']);
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('Admin.categorias-producto.index', compact('categorias', 'filtros'));
    }

    /** Mostrar formulario de creación. */
    public function create()
    {
        return view('Admin.categorias-producto.form', [
            'categoriaProducto' => new CategoriaProducto(),
            'modo' => 'crear',
        ]);
    }

    /** Guardar una categoría nueva; activo usa el default 1 de la BD. */
    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas());
        CategoriaProducto::create($datos);

        return redirect()
            ->route('admin.categorias-producto.index')
            ->with('success', 'Categoría de producto registrada correctamente.');
    }

    /** Mostrar formulario de edición. */
    public function edit(CategoriaProducto $categoriaProducto)
    {
        return view('Admin.categorias-producto.form', [
            'categoriaProducto' => $categoriaProducto,
            'modo' => 'editar',
        ]);
    }

    /** Actualizar nombre y descripción sin alterar el estado. */
    public function update(Request $request, CategoriaProducto $categoriaProducto)
    {
        $datos = $request->validate($this->reglas($categoriaProducto));
        $categoriaProducto->update($datos);

        return redirect()
            ->route('admin.categorias-producto.index')
            ->with('success', 'Categoría de producto actualizada correctamente.');
    }

    /** Cambiar estado sin borrar el registro ni romper relaciones. */
    public function cambiarEstado(Request $request, CategoriaProducto $categoriaProducto)
    {
        $datos = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        $categoriaProducto->activo = (int) $datos['activo'];
        $categoriaProducto->save();

        $mensaje = $categoriaProducto->activo
            ? 'Categoría activada correctamente.'
            : 'Categoría desactivada correctamente.';

        return redirect()
            ->route('admin.categorias-producto.index', $request->only('q', 'estado', 'page'))
            ->with('success', $mensaje);
    }

    /** Reglas comunes de creación y edición. */
    private function reglas(?CategoriaProducto $categoriaProducto = null): array
    {
        $nombreUnico = Rule::unique('categoria_producto', 'nombre');

        if ($categoriaProducto) {
            $nombreUnico->ignore(
                $categoriaProducto->getKey(),
                $categoriaProducto->getKeyName()
            );
        }

        return [
            'nombre' => ['required', 'string', 'max:80', $nombreUnico],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
