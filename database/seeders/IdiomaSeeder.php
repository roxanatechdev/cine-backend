<?php

namespace Database\Seeders;

use App\Models\Idioma;
use Illuminate\Database\Seeder;

class IdiomaSeeder extends Seeder
{
    /**
     * Carga los idiomas iniciales de CinePrime.
     */
    public function run(): void
    {
        $idiomas = [
            ['codigo' => 'es', 'nombre' => 'Español'],
            ['codigo' => 'en', 'nombre' => 'Inglés'],
            ['codigo' => 'pt', 'nombre' => 'Portugués'],
            ['codigo' => 'fr', 'nombre' => 'Francés'],
            ['codigo' => 'ja', 'nombre' => 'Japonés'],
            ['codigo' => 'ko', 'nombre' => 'Coreano'],
            ['codigo' => 'zh', 'nombre' => 'Chino'],
            ['codigo' => 'it', 'nombre' => 'Italiano'],
            ['codigo' => 'de', 'nombre' => 'Alemán'],
            ['codigo' => 'ru', 'nombre' => 'Ruso'],
        ];

        foreach ($idiomas as $idioma) {
            Idioma::updateOrCreate(
                ['codigo' => $idioma['codigo']],
                [
                    'nombre' => $idioma['nombre'],
                    'activo' => true,
                ]
            );
        }
    }
}
