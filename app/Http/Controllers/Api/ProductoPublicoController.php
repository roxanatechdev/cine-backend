<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/** Catálogo público de confitería: solo lectura, sin exponer datos administrativos. */
class ProductoPublicoController extends Controller
{
    public function index(): JsonResponse
    {
        $ahora = now();

        $productos = Producto::query()
            ->with(['categoria:id_categoria_producto,nombre'])
            ->where('estado', 'ACTIVO')
            ->where(function ($query) {
                $query->whereNull('stock')->orWhere('stock', '>', 0);
            })
            ->orderBy('id_categoria_producto')
            ->orderBy('nombre')
            ->get()
            ->map(function (Producto $producto) use ($ahora) {
                $promocionVigente = (bool) $producto->es_promocion
                    && $producto->precio_promocional !== null
                    && (float) $producto->precio_promocional < (float) $producto->precio_actual
                    && (!$producto->promocion_inicio || $producto->promocion_inicio->lte($ahora))
                    && (!$producto->promocion_fin || $producto->promocion_fin->gte($ahora));

                return [
                    'id_producto' => $producto->id_producto,
                    'nombre' => $producto->nombre,
                    'descripcion' => $producto->descripcion,
                    'id_categoria_producto' => $producto->id_categoria_producto,
                    'categoria' => $producto->categoria?->nombre,
                    'precio_actual' => (float) $producto->precio_actual,
                    'precio' => $promocionVigente
                        ? (float) $producto->precio_promocional
                        : (float) $producto->precio_actual,
                    'es_promocion' => $promocionVigente,
                    'precio_promocional' => $promocionVigente ? (float) $producto->precio_promocional : null,
                    'tamano' => $producto->tamano,
                    'tipo_variante' => $producto->tipo_variante,
                    'contenido_ml' => $producto->contenido_ml,
                    'peso_gramos' => $producto->peso_gramos,
                    'imagen' => $this->urlImagen($producto->imagen),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'total' => $productos->count(),
            'productos' => $productos,
        ]);
    }

    private function urlImagen(?string $ruta): ?string
    {
        if ($ruta === null || trim($ruta) === '') {
            return null;
        }

        $ruta = trim(str_replace('\\', '/', $ruta));

        // Las URLs completas (por ejemplo, imágenes alojadas externamente) se conservan.
        if (preg_match('/^(https?:\/\/|data:image\/)/i', $ruta)) {
            return $ruta;
        }

        // Si se guardó una ruta de imagen perteneciente al frontend, dejarla relativa
        // para que confiteria.js la resuelva desde la ubicación de confiteria.html.
        $rutaFrontend = ltrim($ruta, './');
        if (preg_match('/^(img|images|assets|frontend\/img)\//i', $rutaFrontend)) {
            return $rutaFrontend;
        }

        // Normalizar variantes habituales guardadas en la base de datos:
        // storage/productos/foto.jpg, public/productos/foto.jpg o /productos/foto.jpg.
        $ruta = ltrim($ruta, '/');
        $ruta = preg_replace('#^public/#i', '', $ruta);
        $ruta = preg_replace('#^storage/#i', '', $ruta);

        // El formulario administrativo de este proyecto guarda archivos en "productos".
        // Si la BD contiene solo "foto.jpg", buscarlo en esa carpeta.
        if (!str_contains($ruta, '/')) {
            $ruta = 'productos/' . $ruta;
        }

        return url(Storage::disk('public')->url($ruta));
    }
}
