<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaisSeeder extends Seeder
{
    public function run(): void
    {
        $paises = [
            ['codigo' => 'BO', 'nombre' => 'Bolivia'],
            ['codigo' => 'US', 'nombre' => 'Estados Unidos'],
            ['codigo' => 'GB', 'nombre' => 'Reino Unido'],
            ['codigo' => 'CA', 'nombre' => 'Canadá'],
            ['codigo' => 'MX', 'nombre' => 'México'],
            ['codigo' => 'AR', 'nombre' => 'Argentina'],
            ['codigo' => 'BR', 'nombre' => 'Brasil'],
            ['codigo' => 'CL', 'nombre' => 'Chile'],
            ['codigo' => 'CO', 'nombre' => 'Colombia'],
            ['codigo' => 'PE', 'nombre' => 'Perú'],
            ['codigo' => 'ES', 'nombre' => 'España'],
            ['codigo' => 'FR', 'nombre' => 'Francia'],
            ['codigo' => 'IT', 'nombre' => 'Italia'],
            ['codigo' => 'DE', 'nombre' => 'Alemania'],
            ['codigo' => 'JP', 'nombre' => 'Japón'],
            ['codigo' => 'KR', 'nombre' => 'Corea del Sur'],
            ['codigo' => 'CN', 'nombre' => 'China'],
            ['codigo' => 'IN', 'nombre' => 'India'],
            ['codigo' => 'AU', 'nombre' => 'Australia'],
            ['codigo' => 'NZ', 'nombre' => 'Nueva Zelanda'],
        ];

        foreach ($paises as $pais) {
            DB::table('pais')->updateOrInsert(
                ['codigo' => $pais['codigo']],
                ['nombre' => $pais['nombre']]
            );
        }
    }
}
