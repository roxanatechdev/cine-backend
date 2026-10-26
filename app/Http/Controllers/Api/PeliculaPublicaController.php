<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelicula;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/** Endpoints públicos de solo lectura para el catálogo de CinePrime. */
class PeliculaPublicaController extends Controller
{
    /** Todas las películas activas; fuente del carrusel principal. */
    public function destacadas(): JsonResponse
    {
        $peliculas = $this->consultaBase()
            ->orderByRaw('fecha_estreno IS NULL')
            ->orderBy('fecha_estreno')
            ->orderBy('titulo')
            ->get();

        return response()->json($peliculas->map(fn (Pelicula $pelicula) => $this->formatear($pelicula))->values());
    }

    /** Películas estrenadas o sin fecha de estreno registrada. */
    public function cartelera(): JsonResponse
    {
        $hoy = now()->toDateString();
        $peliculas = $this->consultaBase()
            ->where(fn (Builder $query) => $query->whereNull('fecha_estreno')->orWhereDate('fecha_estreno', '<=', $hoy))
            ->orderBy('titulo')->get();

        return response()->json($peliculas->map(fn (Pelicula $pelicula) => $this->formatear($pelicula))->values());
    }

    /** Películas activas con fecha de estreno posterior a hoy. */
    public function proximamente(): JsonResponse
    {
        $hoy = now()->toDateString();
        $peliculas = $this->consultaBase()
            ->whereNotNull('fecha_estreno')
            ->whereDate('fecha_estreno', '>', $hoy)
            ->orderBy('fecha_estreno')->orderBy('titulo')->get();

        return response()->json($peliculas->map(fn (Pelicula $pelicula) => $this->formatear($pelicula))->values());
    }

    private function consultaBase(): Builder
    {
        return Pelicula::query()
            ->where('estado', 'ACTIVA')
            ->with([
                'generoPrincipal:id_genero,nombre',
                'multimedia' => function ($query) {
                    $query->where('activo', 1)
                        ->whereIn('tipo', ['POSTER', 'IMAGEN', 'TRAILER'])
                        ->orderByDesc('es_principal')
                        ->orderBy('orden')
                        ->orderBy('id_multimedia');
                },
            ]);
    }

    private function formatear(Pelicula $pelicula): array
    {
        $poster = $pelicula->multimedia->firstWhere('tipo', 'POSTER')
            ?? $pelicula->multimedia->firstWhere('tipo', 'IMAGEN');
        $trailer = $pelicula->multimedia->firstWhere('tipo', 'TRAILER');

        return [
            'id_pelicula' => $pelicula->id_pelicula,
            'titulo' => $pelicula->titulo,
            'sinopsis' => $pelicula->sinopsis,
            'genero' => $pelicula->generoPrincipal?->nombre,
            'clasificacion' => $pelicula->clasificacion,
            'duracion_minutos' => $pelicula->duracion_minutos,
            'fecha_estreno' => $pelicula->fecha_estreno?->toDateString(),
            'estado' => $pelicula->estado,
            'imagen' => $poster?->url,
            'imagen_alt' => $poster?->texto_alternativo,
            'trailer' => $trailer?->url,
            'rating' => null,
            'formato' => null,
        ];
    }
}
