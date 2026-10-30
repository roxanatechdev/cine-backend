<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeliculaPaisSeeder extends Seeder
{
    public function run(): void
    {
        $relaciones = [
            // Películas internacionales
            ['pelicula' => 'Zootopia 2', 'paises' => ['US']],
            ['pelicula' => 'Wicked: For Good', 'paises' => ['US']],
            ['pelicula' => 'Avatar: Fire and Ash', 'paises' => ['US', 'NZ']],
            ['pelicula' => 'El agente secreto', 'paises' => ['BR']],
            ['pelicula' => 'Hamnet', 'paises' => ['GB']],
            ['pelicula' => 'Scream 7', 'paises' => ['US']],
            ['pelicula' => 'Hoppers', 'paises' => ['US']],
            ['pelicula' => 'The Super Mario Galaxy Movie', 'paises' => ['US', 'JP']],
            ['pelicula' => 'Toy Story 5', 'paises' => ['US']],
            ['pelicula' => 'Minions 3', 'paises' => ['US', 'FR']],

            // Películas de demostración latinoamericanas
            ['pelicula' => 'La última función', 'paises' => ['BO']],
            ['pelicula' => 'Código Andino', 'paises' => ['BO', 'CL']],
            ['pelicula' => 'El último boleto', 'paises' => ['CL']],
            ['pelicula' => 'Risas de medianoche', 'paises' => ['BO']],
            ['pelicula' => 'La casa del lago', 'paises' => ['AR']],
            ['pelicula' => 'Horizonte de fuego', 'paises' => ['BO', 'PE']],
            ['pelicula' => 'Navidad en La Paz', 'paises' => ['BO']],
            ['pelicula' => 'Planeta de cristal', 'paises' => ['US', 'CA']],
            ['pelicula' => 'El reino perdido', 'paises' => ['ES']],
            ['pelicula' => 'Frecuencia cero', 'paises' => ['MX']],
            ['pelicula' => 'El jardín de las estrellas', 'paises' => ['BO']],
            ['pelicula' => 'Operación Cóndor', 'paises' => ['BO', 'CO']],
            ['pelicula' => 'El bosque susurrante', 'paises' => ['CA']],
            ['pelicula' => 'Bajo el mismo cielo', 'paises' => ['BO', 'AR']],
        ];

        foreach ($relaciones as $relacion) {
            $idPelicula = DB::table('pelicula')
                ->where('titulo', $relacion['pelicula'])
                ->value('id_pelicula');

            if ($idPelicula === null) {
                throw new RuntimeException(
                    "No existe la película: {$relacion['pelicula']}. "
                        . 'Ejecuta PeliculaSeeder primero.'
                );
            }

            foreach ($relacion['paises'] as $codigoPais) {
                $paisExiste = DB::table('pais')
                    ->where('codigo', $codigoPais)
                    ->exists();

                if (! $paisExiste) {
                    throw new RuntimeException(
                        "No existe el país con código {$codigoPais}. "
                            . 'Ejecuta PaisSeeder primero y verifica sus códigos.'
                    );
                }

                DB::table('pelicula_pais')->updateOrInsert(
                    [
                        'id_pelicula' => $idPelicula,
                        'codigo_pais' => $codigoPais,
                    ],
                    []
                );
            }
        }
    }
}
