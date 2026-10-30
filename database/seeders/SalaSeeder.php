<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalaSeeder extends Seeder
{
    public function run(): void
    {
        $salas = [
            [
                'nombre' => 'Sala 1',
                'capacidad' => 80,
                'margen_limpieza_minutos' => 30,
                'estado' => 'ACTIVA',
            ],
            [
                'nombre' => 'Sala 2',
                'capacidad' => 60,
                'margen_limpieza_minutos' => 30,
                'estado' => 'ACTIVA',
            ],
            [
                'nombre' => 'Sala 3',
                'capacidad' => 40,
                'margen_limpieza_minutos' => 25,
                'estado' => 'ACTIVA',
            ],
            [
                'nombre' => 'Sala Premium',
                'capacidad' => 30,
                'margen_limpieza_minutos' => 35,
                'estado' => 'ACTIVA',
            ],
            [
                'nombre' => 'Sala 5',
                'capacidad' => 50,
                'margen_limpieza_minutos' => 30,
                'estado' => 'MANTENIMIENTO',
            ],
        ];

        foreach ($salas as $sala) {
            DB::table('sala')->updateOrInsert(
                ['nombre' => $sala['nombre']],
                [
                    'capacidad' => $sala['capacidad'],
                    'margen_limpieza_minutos' =>
                    $sala['margen_limpieza_minutos'],
                    'estado' => $sala['estado'],
                ]
            );
        }
    }
}
