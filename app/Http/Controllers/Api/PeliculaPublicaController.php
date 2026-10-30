<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Funcion;
use App\Models\Pelicula;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoints públicos de solo lectura para la cartelera de CinePrime.
 * Los datos proceden del catálogo administrado desde el panel de administración.
 */
class PeliculaPublicaController extends Controller
{
    /**
     * Cartelera semanal: películas activas con funciones PROGRAMADAS
     * desde el momento actual hasta el final del domingo de esta semana.
     * Respuesta: { semana: {...}, peliculas: [...] }.
     */
    public function cartelera(): JsonResponse
    {
        $ahora = now();
        $inicioSemana = $ahora->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $ahora->copy()->endOfWeek(Carbon::SUNDAY);

        $peliculas = $this->consultaBase()
            ->orderBy('titulo')
            ->get();

        $idsPeliculas = $peliculas->pluck('id_pelicula');

        $funcionesPorPelicula = Funcion::query()
            ->with(['sala:id_sala,nombre'])
            ->whereIn('id_pelicula', $idsPeliculas)
            ->where('estado', 'PROGRAMADA')
            ->where('inicio', '>=', $ahora)
            ->where('inicio', '<=', $finSemana)
            ->orderBy('inicio')
            ->get()
            ->groupBy('id_pelicula');

        $idsConFunciones = $funcionesPorPelicula->keys();
        $peliculas = $peliculas->whereIn('id_pelicula', $idsConFunciones)->values();

        $resultado = $peliculas->map(function (Pelicula $pelicula) use ($funcionesPorPelicula) {
            $datos = $this->formatear($pelicula);

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

            $datos['funciones'] = $funciones;
            $datos['formatos'] = $funciones->pluck('formato')->unique()->values();
            return $datos;
        })->values();

        return response()->json([
            'semana' => [
                'desde' => $inicioSemana->toDateString(),
                'hasta' => $finSemana->toDateString(),
                'zona_horaria' => config('app.timezone'),
            ],
            'peliculas' => $resultado,
        ]);
    }

    /** Detalle público de una película activa, consultada únicamente por su ID. */
    public function detalle(int $id): JsonResponse
    {
        $pelicula = $this->consultaBase()->findOrFail($id);
        // La ficha técnica completa y los países solo se cargan en la página de detalle.
        $pelicula->load([
            'creditos' => function ($query) {
                $query->with('persona:id_persona,nombre_completo,nombre_busqueda')
                    ->orderBy('rol')
                    ->orderBy('orden');
            },
            'paises:codigo,nombre',
            'multimedia' => function ($query) {
                $query->where('activo', 1)
                    ->whereIn('tipo', ['POSTER', 'IMAGEN', 'TRAILER', 'VIDEO'])
                    ->orderByDesc('es_principal')
                    ->orderBy('orden')
                    ->orderBy('id_multimedia');
            },
        ]);
        $datos = $this->formatear($pelicula);
        $datos['multimedia'] = $pelicula->multimedia->whereIn('tipo', ['IMAGEN', 'TRAILER', 'VIDEO'])->map(fn ($recurso) => [
            'id_multimedia' => $recurso->id_multimedia,
            'tipo' => $recurso->tipo,
            'url' => $this->urlMultimedia($recurso->url),
            'texto_alternativo' => $recurso->texto_alternativo,
            'es_principal' => (bool) $recurso->es_principal,
            'orden' => $recurso->orden,
        ])->values();

        $funciones = Funcion::query()
            ->with(['sala:id_sala,nombre'])
            ->where('id_pelicula', $pelicula->id_pelicula)
            ->where('estado', 'PROGRAMADA')
            ->where('inicio', '>=', now())
            ->orderBy('inicio')
            ->get()
            ->map(fn (Funcion $funcion) => [
                'id_funcion' => $funcion->id_funcion,
                'inicio' => $funcion->inicio?->toIso8601String(),
                'fecha' => $funcion->inicio?->toDateString(),
                'hora' => $funcion->inicio?->format('H:i'),
                'formato' => $funcion->formato,
                'precio' => $funcion->precio_base,
                'sala' => $funcion->sala?->nombre,
            ])->values();

        $datos['funciones'] = $funciones;
        $datos['formatos'] = $funciones->pluck('formato')->unique()->values();

        return response()->json(['pelicula' => $datos]);
    }

    /** Películas activas con fecha de estreno futura, sin exigir funciones creadas. */
    public function proximamente(): JsonResponse
    {
        $hoy = now()->toDateString();

        $peliculas = $this->consultaBase()
            ->whereNotNull('fecha_estreno')
            ->whereDate('fecha_estreno', '>', $hoy)
            ->orderBy('fecha_estreno')
            ->orderBy('titulo')
            ->get()
            ->map(fn (Pelicula $pelicula) => $this->formatear($pelicula))
            ->values();

        return response()->json([
            'semana' => null,
            'peliculas' => $peliculas,
        ]);
    }

    /** Películas activas para carruseles u otras vistas públicas. */
    public function destacadas(): JsonResponse
    {
        $peliculas = $this->consultaBase()
            ->orderByRaw('fecha_estreno IS NULL')
            ->orderBy('fecha_estreno')
            ->orderBy('titulo')
            ->get()
            ->map(fn (Pelicula $pelicula) => $this->formatear($pelicula))
            ->values();

        return response()->json($peliculas);
    }

    private function consultaBase(): Builder
    {
        return Pelicula::query()
            ->where('estado', 'ACTIVA')
            ->with([
                'generoPrincipal:id_genero,nombre',
                'idiomaOriginal:codigo,nombre',
                'creditos' => function ($query) {
                    $query->whereIn('rol', ['DIRECTOR', 'ACTOR'])
                        ->with('persona:id_persona,nombre_completo,nombre_busqueda')
                        ->orderBy('rol')
                        ->orderBy('orden');
                },
                'multimedia' => function ($query) {
                    $query->where('activo', 1)
                        ->whereIn('tipo', ['POSTER', 'IMAGEN', 'TRAILER', 'VIDEO'])
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
            'idioma' => $pelicula->idiomaOriginal?->nombre,
            'idioma_original' => $pelicula->idiomaOriginal ? [
                'codigo' => $pelicula->idiomaOriginal->codigo,
                'nombre' => $pelicula->idiomaOriginal->nombre,
            ] : null,
            'clasificacion' => $pelicula->clasificacion,
            'duracion_minutos' => $pelicula->duracion_minutos,
            'fecha_estreno' => $pelicula->fecha_estreno?->toDateString(),
            'estado' => $pelicula->estado,
            'imagen' => $poster ? $this->urlMultimedia($poster->url) : null,
            'imagen_alt' => $poster?->texto_alternativo,
            'trailer' => $trailer ? $this->urlMultimedia($trailer->url) : null,
            'rating' => null,
            'formato' => null,
            'creditos' => $pelicula->creditos->map(fn ($credito) => [
                'rol' => $credito->rol,
                'nombre' => $credito->persona?->nombre_completo,
                'nombre_busqueda' => $credito->persona?->nombre_busqueda,
                'personaje' => $credito->personaje,
                'orden' => $credito->orden,
            ])->values(),
            'paises_produccion' => $pelicula->paises->map(fn ($pais) => [
                'codigo' => $pais->codigo,
                'nombre' => $pais->nombre,
            ])->values(),
            'funciones' => [],
            'formatos' => [],
        ];
    }

    private function urlMultimedia(string $ruta): string
    {
        if (preg_match('/^https?:\/\//i', $ruta)) {
            return $ruta;
        }

        return url(Storage::disk('public')->url($ruta));
    }
}
