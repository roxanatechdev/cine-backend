<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ConfiteriaPublicaController extends Controller
{
    /** Categorías activas para los filtros públicos de confitería. */
    public function categorias(): JsonResponse
    {
        $categorias = CategoriaProducto::query()
            ->where('activo', 1)
            ->withCount(['productos' => function ($query) {
                $query->where('estado', 'ACTIVO');
            }])
            ->orderBy('nombre')
            ->get(['id_categoria_producto', 'nombre', 'descripcion', 'activo']);

        return response()->json(['data' => $categorias]);
    }

    /** Productos activos; las imágenes conservan la ruta guardada en la BD. */
    public function productos(): JsonResponse
    {
        $productos = Producto::query()
            ->with(['categoria:id_categoria_producto,nombre'])
            ->where('estado', 'ACTIVO')
            ->where(function ($query) {
                $query->whereNull('id_categoria_producto')
                    ->orWhereHas('categoria', function ($categoriaQuery) {
                        $categoriaQuery->where('activo', 1);
                    });
            })
            ->orderBy('nombre')
            ->get()
            ->map(function (Producto $producto) {
                $producto->imagen_url = $this->resolverImagen($producto->imagen);
                return $producto;
            });

        return response()->json(['data' => $productos]);
    }

    private function resolverImagen(?string $imagen): ?string
    {
        if (!$imagen) {
            return null;
        }

        $imagen = trim($imagen);
        if ($imagen === '') {
            return null;
        }

        // URLs completas de imágenes externas.
        if (preg_match('/^https?:\/\//i', $imagen)) {
            return $imagen;
        }

        // Rutas guardadas como storage/productos/archivo.jpg.
        if (str_starts_with($imagen, 'storage/')) {
            return url('/' . ltrim($imagen, '/'));
        }

        // Rutas del disco público de Laravel, normalmente productos/archivo.jpg.
        if (!str_starts_with($imagen, '/') && !preg_match('/^(img|images|assets)\//i', $imagen)) {
            return Storage::disk('public')->url(ltrim($imagen, '/'));
        }

        // Rutas públicas relativas a la web del frontend, o absolutas del mismo host.
        return $imagen;
    }
}
