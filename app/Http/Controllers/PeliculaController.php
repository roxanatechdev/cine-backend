<?php

namespace App\Http\Controllers;

use App\Models\Funcion;
use App\Models\Pelicula;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PeliculaController extends Controller
{
    /**
     * Catálogo público: películas activas y solo funciones futuras
     * comprendidas en la semana calendario actual (lunes a domingo).
     */
    public function index(): JsonResponse
    {
        $ahora = now();
        $inicioSemana = $ahora->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $ahora->copy()->endOfWeek(Carbon::SUNDAY);

        $peliculas = Pelicula::query()
            ->where('estado', 'ACTIVA')
            ->with([
                'generoPrincipal:id_genero,nombre',
                'idiomaOriginal:codigo,nombre',
                'multimedia' => function ($query) {
                    $query->where('activo', 1)
                        ->whereIn('tipo', ['POSTER', 'IMAGEN', 'TRAILER'])
                        ->orderByDesc('es_principal')
                        ->orderBy('orden')
                        ->orderBy('id_multimedia');
                },
            ])
            ->orderBy('titulo')
            ->get();

        $idsPeliculas = $peliculas->pluck('id_pelicula');

        $funcionesPorPelicula = Funcion::query()
            ->with(['sala:id_sala,nombre'])
            ->whereIn('id_pelicula', $idsPeliculas)
            ->where('estado', 'PROGRAMADA')
            ->whereBetween('inicio', [$inicioSemana, $finSemana])
            ->orderBy('inicio')
            ->get()
            ->groupBy('id_pelicula');

        // IMPORTANTE: en el inicio solo mostramos películas que tengan al menos
        // una función PROGRAMADA futura dentro de la semana actual.
        // Antes se recorrían todas las películas ACTIVAS, incluso las que tenían
        // una colección de funciones vacía; por eso aparecían todas en el index.
        $idsPeliculasConFunciones = $funcionesPorPelicula->keys();
        $peliculas = $peliculas->whereIn('id_pelicula', $idsPeliculasConFunciones)->values();

        $resultado = $peliculas->map(function (Pelicula $pelicula) use ($funcionesPorPelicula) {
            $poster = $pelicula->multimedia->first(fn ($item) => $item->tipo === 'POSTER')
                ?? $pelicula->multimedia->first(fn ($item) => $item->tipo === 'IMAGEN');
            $trailer = $pelicula->multimedia->first(fn ($item) => $item->tipo === 'TRAILER');

            $funciones = ($funcionesPorPelicula->get($pelicula->id_pelicula) ?? collect())
                ->map(fn (Funcion $funcion) => [
                    'id_funcion' => $funcion->id_funcion,
                    'inicio' => $funcion->inicio?->toIso8601String(),
                    'fecha' => $funcion->inicio?->toDateString(),
                    'hora' => $funcion->inicio?->format('H:i'),
                    'formato' => $funcion->formato,
                    'precio' => $funcion->precio_base,
                    'sala' => $funcion->sala?->nombre,
                ])->values();

            return [
                'id_pelicula' => $pelicula->id_pelicula,
                'titulo' => $pelicula->titulo,
                'sinopsis' => $pelicula->sinopsis,
                'clasificacion' => $pelicula->clasificacion,
                'duracion_minutos' => $pelicula->duracion_minutos,
                'fecha_estreno' => $pelicula->fecha_estreno?->toDateString(),
                'estado' => $pelicula->estado,
                'genero' => $pelicula->generoPrincipal?->nombre,
                'idioma' => $pelicula->idiomaOriginal?->nombre,
                'imagen' => $poster ? $this->urlMultimedia($poster->url) : null,
                'texto_alternativo' => $poster?->texto_alternativo,
                'trailer' => $trailer ? $this->urlMultimedia($trailer->url) : null,
                'rating' => null,
                'formato' => null,
                'funciones' => $funciones,
            ];
        });

        return response()->json([
            'semana' => [
                'desde' => $inicioSemana->toDateString(),
                'hasta' => $finSemana->toDateString(),
                'zona_horaria' => config('app.timezone'),
            ],
            'peliculas' => $resultado->values(),
        ]);
    }

    private function urlMultimedia(string $ruta): string
    {
        if (preg_match('/^https?:\/\//i', $ruta)) {
            return $ruta;
        }

        return url(Storage::disk('public')->url($ruta));
    }
}
