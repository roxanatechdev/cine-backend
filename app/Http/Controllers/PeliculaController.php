<?php

namespace App\Http\Controllers;

use App\Models\Pelicula;
use Illuminate\Http\JsonResponse;

class PeliculaController extends Controller
{
    public function index(): JsonResponse
    {
        $peliculas = Pelicula::query()
            ->where('estado', 'ACTIVA')
            ->orderBy('titulo')
            ->get();

        return response()->json($peliculas);
    }
}
