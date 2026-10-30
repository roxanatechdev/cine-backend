<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeneroSeeder extends Seeder
{
    public function run(): void
    {
        $generos = [
            [
                'nombre' => 'Acción',
                'descripcion' => 'Películas con combates, persecuciones y escenas de alto impacto.',
            ],
            [
                'nombre' => 'Aventura',
                'descripcion' => 'Historias de exploración, viajes y grandes desafíos.',
            ],
            [
                'nombre' => 'Animación',
                'descripcion' => 'Películas creadas mediante técnicas de animación.',
            ],
            [
                'nombre' => 'Ciencia ficción',
                'descripcion' => 'Historias sobre tecnología, ciencia y futuros posibles.',
            ],
            [
                'nombre' => 'Comedia',
                'descripcion' => 'Películas orientadas al humor y al entretenimiento.',
            ],
            [
                'nombre' => 'Drama',
                'descripcion' => 'Historias centradas en conflictos y relaciones humanas.',
            ],
            [
                'nombre' => 'Fantasía',
                'descripcion' => 'Historias con elementos mágicos o mundos imaginarios.',
            ],
            [
                'nombre' => 'Terror',
                'descripcion' => 'Películas diseñadas para provocar miedo o tensión.',
            ],
            [
                'nombre' => 'Suspenso',
                'descripcion' => 'Historias de incertidumbre, tensión y misterio.',
            ],
            [
                'nombre' => 'Romance',
                'descripcion' => 'Historias centradas en relaciones amorosas.',
            ],
            [
                'nombre' => 'Documental',
                'descripcion' => 'Películas basadas en hechos, personas o situaciones reales.',
            ],
            [
                'nombre' => 'Musical',
                'descripcion' => 'Películas que integran canciones o coreografías en la historia.',
            ],
        ];

        foreach ($generos as $genero) {
            DB::table('genero')->updateOrInsert(
                ['nombre' => $genero['nombre']],
                [
                    'descripcion' => $genero['descripcion'],
                    'activo' => 1,
                ]
            );
        }
    }
}
