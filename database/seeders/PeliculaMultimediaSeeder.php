<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeliculaMultimediaSeeder extends Seeder
{
    public function run(): void
    {
        $peliculas = [
            'Zootopia 2',
            'Wicked: For Good',
            'Avatar: Fire and Ash',
            'El agente secreto',
            'Hamnet',
            'Scream 7',
            'Hoppers',
            'The Super Mario Galaxy Movie',
            'Toy Story 5',
            'Minions 3',
            'La última función',
            'Código Andino',
            'El último boleto',
            'Risas de medianoche',
            'La casa del lago',
            'Horizonte de fuego',
            'Navidad en La Paz',
            'Planeta de cristal',
            'El reino perdido',
            'Frecuencia cero',
            'El jardín de las estrellas',
            'Operación Cóndor',
            'El bosque susurrante',
            'Bajo el mismo cielo',
        ];

        foreach ($peliculas as $indice => $titulo) {
            $idPelicula = DB::table('pelicula')
                ->where('titulo', $titulo)
                ->value('id_pelicula');

            if ($idPelicula === null) {
                throw new RuntimeException(
                    "No existe la película: {$titulo}. "
                        . 'Ejecuta PeliculaSeeder primero.'
                );
            }

            // Imagen de marcador de posición, no es un póster oficial.
            $url = 'https://placehold.co/600x900?text='
                . urlencode($titulo);

            DB::table('pelicula_multimedia')->updateOrInsert(
                [
                    'id_pelicula' => $idPelicula,
                    'tipo' => 'POSTER',
                    'url' => $url,
                ],
                [
                    'texto_alternativo' => 'Póster de ejemplo para '
                        . $titulo,
                    'es_principal' => 1,
                    'orden' => 0,
                    'activo' => 1,
                ]
            );
        }
    }
}
