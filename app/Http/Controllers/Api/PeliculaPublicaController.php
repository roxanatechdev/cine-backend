<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelicula;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints públicos de lectura para las secciones de películas de CinePrime.
 * No permite crear, editar ni eliminar películas.
 */
class PeliculaPublicaController extends Controller
{
    /** Películas estrenadas o sin fecha de estreno registrada. */
    public function cartelera(): JsonResponse
    {
        $hoy = now()->toDateString();

        $peliculas = $this->consultaBase()
            ->where(function (Builder $query) use ($hoy) {
                $query->whereNull('fecha_estreno')
                    ->orWhereDate('fecha_estreno', '<=', $hoy);
            })
            ->orderBy('titulo')
            ->get();

        return response()->json($peliculas->map(fn (Pelicula $pelicula) => $this->formatear($pelicula))->values());
    }

    /** Películas activas con fecha de estreno posterior a hoy. */
    public function proximamente(): JsonResponse
    {
        $hoy = now()->toDateString();

        $peliculas = $this->consultaBase()
            ->whereNotNull('fecha_estreno')
            ->whereDate('fecha_estreno', '>', $hoy)
            ->orderBy('fecha_estreno')
            ->orderBy('titulo')
            ->get();

        return response()->json($peliculas->map(fn (Pelicula $pelicula) => $this->formatear($pelicula))->values());
    }

    /** Campos y relaciones que necesita la web pública. */
    private function consultaBase(): Builder
    {
        return Pelicula::query()
            ->where('estado', 'ACTIVA')
            ->with([
                'generoPrincipal:id_genero,nombre',
                'multimedia' => function ($query) {
                    $query->where('activo', 1)
                        ->whereIn('tipo', ['POSTER', 'TRAILER'])
                        ->orderByDesc('es_principal')
                        ->orderBy('orden');
                },
            ]);
    }

    /** Normaliza los nombres para que el JavaScript no dependa de nombres internos de BD. */
    private function formatear(Pelicula $pelicula): array
    {
        $poster = $pelicula->multimedia->firstWhere('tipo', 'POSTER');
        $trailer = $pelicula->multimedia->firstWhere('tipo', 'TRAILER');

        return [
            'id_pelicula' => $pelicula->id_pelicula,
            'titulo' => $pelicula->titulo,
            'sinopsis' => $pelicula->sinopsis,
            'genero' => $pelicula->generoPrincipal?->nombre,
            'clasificacion' => $pelicula->clasificacion,
            'duracion_minutos' => $pelicula->duracion_minutos,
            'fecha_estreno' => $pelicula->fecha_estreno?->toDateString(),
            'imagen' => $poster?->url,
            'imagen_alt' => $poster?->texto_alternativo,
            'trailer' => $trailer?->url,
        ];
    }
}
