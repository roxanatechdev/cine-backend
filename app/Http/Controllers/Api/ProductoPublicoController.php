<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/** Catálogo público de confitería: solo lectura. */
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
                    'precio' => $promocionVigente ? (float) $producto->precio_promocional : (float) $producto->precio_actual,
                    'es_promocion' => $promocionVigente,
                    'precio_promocional' => $promocionVigente ? (float) $producto->precio_promocional : null,
                    'tamano' => $producto->tamano,
                    'tipo_variante' => $producto->tipo_variante,
                    'contenido_ml' => $producto->contenido_ml,
                    'peso_gramos' => $producto->peso_gramos,
                    'imagen' => $this->resolverImagen($producto->imagen),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'total' => $productos->count(),
            'productos' => $productos,
        ]);
    }

    /**
     * El formulario de administración guarda los archivos en el disco public
     * con rutas como productos/archivo.jpg. Se devuelve una ruta /storage/... 
     * para que el navegador la solicite al mismo servidor de la API.
     */
    private function resolverImagen(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        $ruta = trim(str_replace('\\', '/', $valor));

        if (preg_match('/^data:image\//i', $ruta)) {
            return $ruta;
        }

        // Las URLs externas reales se conservan. Las de example.test son datos ficticios.
        if (preg_match('/^https?:\/\//i', $ruta)) {
            $host = parse_url($ruta, PHP_URL_HOST);
            if ($host && !str_ends_with(strtolower($host), '.example.test')) {
                return $ruta;
            }
            $ruta = (string) parse_url($ruta, PHP_URL_PATH);
        }

        $ruta = ltrim($ruta, '/');
        $ruta = preg_replace('#^(public/)?storage/#i', '', $ruta);
        $ruta = preg_replace('#^public/#i', '', $ruta);
        $ruta = preg_replace('#^products/#i', 'productos/', $ruta);

        // Los nombres sin carpeta se guardan en la carpeta usada por el formulario.
        if (!str_contains($ruta, '/')) {
            $ruta = 'productos/' . $ruta;
        }

        // Si ya viene como ruta relativa de frontend, no se fuerza a Storage.
        if (preg_match('#^(img|images|assets)/#i', $ruta)) {
            return '/' . $ruta;
        }

        // Ruta relativa estable: evita que APP_URL apunte a localhost o dominio distinto.
        return '/storage/' . ltrim($ruta, '/');
    }
}
