<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Funcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Endpoints públicos de lectura para el flujo de compra de entradas. */
class FuncionPublicaController extends Controller
{
    /** Devuelve los datos de una función que todavía puede vender entradas. */
    public function detalle(int $id): JsonResponse
    {
        $funcion = Funcion::query()
            ->with([
                'pelicula:id_pelicula,titulo,estado,duracion_minutos,clasificacion',
                'sala:id_sala,nombre,capacidad,estado',
            ])
            ->where('id_funcion', $id)
            ->where('estado', 'PROGRAMADA')
            ->where('inicio', '>', now())
            ->whereHas('pelicula', fn ($query) => $query->where('estado', 'ACTIVA'))
            ->whereHas('sala', fn ($query) => $query->where('estado', 'ACTIVA'))
            ->firstOrFail();

        return response()->json([
            'funcion' => [
                'id_funcion' => (int) $funcion->id_funcion,
                'inicio' => $funcion->inicio?->toIso8601String(),
                'formato' => $funcion->formato,
                'precio_base' => (string) $funcion->precio_base,
                'estado' => $funcion->estado,
            ],
            'pelicula' => [
                'id_pelicula' => (int) $funcion->pelicula->id_pelicula,
                'titulo' => $funcion->pelicula->titulo,
                'duracion_minutos' => $funcion->pelicula->duracion_minutos,
                'clasificacion' => $funcion->pelicula->clasificacion,
            ],
            'sala' => [
                'id_sala' => (int) $funcion->sala->id_sala,
                'nombre' => $funcion->sala->nombre,
                'capacidad' => $funcion->sala->capacidad,
            ],
            'moneda' => config('cineprime.moneda', 'BOB'),
        ]);
    }

    /** Devuelve la distribución real de la sala y el estado de cada asiento. */
    public function asientos(int $id): JsonResponse
    {
        $funcion = Funcion::query()
            ->with(['sala:id_sala,nombre,capacidad'])
            ->where('id_funcion', $id)
            ->where('estado', 'PROGRAMADA')
            ->where('inicio', '>', now())
            ->whereHas('pelicula', fn ($query) => $query->where('estado', 'ACTIVA'))
            ->whereHas('sala', fn ($query) => $query->where('estado', 'ACTIVA'))
            ->firstOrFail();

        $asientos = DB::table('funcion_asiento as fa')
            ->join('asiento as a', 'a.id_asiento', '=', 'fa.id_asiento')
            ->where('fa.id_funcion', $funcion->id_funcion)
            ->where('a.id_sala', $funcion->id_sala)
            ->orderBy('a.fila')
            ->orderBy('a.numero')
            ->select([
                'fa.id_funcion_asiento',
                'fa.id_asiento',
                'fa.precio',
                'a.fila',
                'a.numero',
                'a.tipo',
                'a.activo',
            ])->get();

        $ocupados = DB::table('entrada as e')
            ->join('compra as c', 'c.id_compra', '=', 'e.id_compra')
            ->join('funcion_asiento as fa', 'fa.id_funcion_asiento', '=', 'e.id_funcion_asiento')
            ->where('fa.id_funcion', $funcion->id_funcion)
            ->where(function ($query) {
                $query->whereIn('e.estado', ['CONFIRMADA', 'UTILIZADA'])
                    ->orWhere(function ($reservas) {
                        $reservas->where('e.estado', 'RESERVADA')
                            ->whereIn('c.estado', ['INICIADA', 'PENDIENTE_PAGO'])
                            ->where(function ($vence) {
                                $vence->whereNull('c.vence_reserva')
                                    ->orWhere('c.vence_reserva', '>', now());
                            });
                    });
            })
            ->pluck('e.id_funcion_asiento')
            ->map(fn ($value) => (int) $value)
            ->all();

        $ocupadosSet = array_fill_keys($ocupados, true);

        $mapaAsientos = $asientos->map(function ($asiento) use ($ocupadosSet) {
            $activo = (int) $asiento->activo === 1;
            $ocupado = isset($ocupadosSet[(int) $asiento->id_funcion_asiento]);

            return [
                'id_funcion_asiento' => (int) $asiento->id_funcion_asiento,
                'id_asiento' => (int) $asiento->id_asiento,
                'fila' => (string) $asiento->fila,
                'numero' => (int) $asiento->numero,
                'tipo' => (string) $asiento->tipo,
                'precio' => (string) $asiento->precio,
                'activo' => $activo,
                'estado' => !$activo ? 'INACTIVO' : ($ocupado ? 'OCUPADO' : 'DISPONIBLE'),
                'seleccionable' => $activo && !$ocupado,
            ];
        })->values();

        $pasillos = DB::table('sala_pasillo')
            ->where('id_sala', $funcion->id_sala)
            ->orderBy('fila')
            ->orderBy('despues_de_numero')
            ->get([
                'id_pasillo',
                'fila',
                'despues_de_numero',
                'orientacion',
                'fila_siguiente',
            ])
            ->map(fn ($pasillo) => [
                'id_pasillo' => (int) $pasillo->id_pasillo,
                'fila' => (string) $pasillo->fila,
                'despues_de_numero' => $pasillo->despues_de_numero === null ? null : (int) $pasillo->despues_de_numero,
                'orientacion' => (string) ($pasillo->orientacion ?? 'LONGITUDINAL'),
                'fila_siguiente' => $pasillo->fila_siguiente,
            ])->values();

        return response()->json([
            'funcion' => [
                'id_funcion' => (int) $funcion->id_funcion,
                'inicio' => $funcion->inicio?->toIso8601String(),
                'precio_base' => (string) $funcion->precio_base,
            ],
            'sala' => [
                'id_sala' => (int) $funcion->sala->id_sala,
                'nombre' => $funcion->sala->nombre,
                'capacidad' => $funcion->sala->capacidad,
            ],
            'asientos' => $mapaAsientos,
            'pasillos' => $pasillos,
            'resumen' => [
                'total' => $mapaAsientos->count(),
                'disponibles' => $mapaAsientos->where('estado', 'DISPONIBLE')->count(),
                'ocupados' => $mapaAsientos->where('estado', 'OCUPADO')->count(),
                'inactivos' => $mapaAsientos->where('estado', 'INACTIVO')->count(),
            ],
        ]);
    }

    /** Catálogo público de productos activos y disponibles para la venta. */
    public function productos(): JsonResponse
    {
        $ahora = now();
        $productos = DB::table('producto as p')
            ->leftJoin('categoria_producto as cp', 'cp.id_categoria_producto', '=', 'p.id_categoria_producto')
            ->where('p.estado', 'ACTIVO')
            ->where(function ($query) {
                $query->whereNull('p.stock')->orWhere('p.stock', '>', 0);
            })
            ->orderBy('cp.nombre')
            ->orderBy('p.nombre')
            ->get([
                'p.id_producto', 'p.id_categoria_producto', 'cp.nombre as categoria',
                'p.nombre', 'p.descripcion', 'p.precio_actual', 'p.imagen', 'p.stock',
                'p.es_promocion', 'p.precio_promocional', 'p.promocion_inicio', 'p.promocion_fin',
                'p.tamano', 'p.tipo_variante', 'p.contenido_ml', 'p.peso_gramos',
            ])
            ->map(function ($producto) use ($ahora) {
                $promocionActiva = (bool) ($producto->es_promocion ?? false)
                    && $producto->precio_promocional !== null
                    && ($producto->promocion_inicio === null || $producto->promocion_inicio <= $ahora->toDateTimeString())
                    && ($producto->promocion_fin === null || $producto->promocion_fin >= $ahora->toDateTimeString());

                return [
                    'id_producto' => (int) $producto->id_producto,
                    'id_categoria_producto' => $producto->id_categoria_producto === null ? null : (int) $producto->id_categoria_producto,
                    'categoria' => $producto->categoria,
                    'nombre' => $producto->nombre,
                    'descripcion' => $producto->descripcion,
                    'precio' => (string) ($promocionActiva ? $producto->precio_promocional : $producto->precio_actual),
                    'precio_actual' => (string) $producto->precio_actual,
                    'en_promocion' => $promocionActiva,
                    'imagen' => $producto->imagen ? Storage::disk('public')->url($producto->imagen) : null,
                    'stock_controlado' => $producto->stock !== null,
                    'disponible' => $producto->stock === null || (int) $producto->stock > 0,
                    'tamano' => $producto->tamano,
                    'tipo_variante' => $producto->tipo_variante,
                    'contenido_ml' => $producto->contenido_ml === null ? null : (int) $producto->contenido_ml,
                    'peso_gramos' => $producto->peso_gramos === null ? null : (int) $producto->peso_gramos,
                ];
            })->values();

        return response()->json(['productos' => $productos, 'moneda' => config('cineprime.moneda', 'BOB')]);
    }
}
