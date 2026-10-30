<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AsientoSeeder extends Seeder
{
    public function run(): void
    {
        $distribuciones = [
            'Sala 1' => [
                'filas' => 8,
                'asientos_por_fila' => 10,
            ],
            'Sala 2' => [
                'filas' => 6,
                'asientos_por_fila' => 10,
            ],
            'Sala 3' => [
                'filas' => 5,
                'asientos_por_fila' => 8,
            ],
            'Sala Premium' => [
                'filas' => 5,
                'asientos_por_fila' => 6,
            ],
            'Sala 5' => [
                'filas' => 5,
                'asientos_por_fila' => 10,
            ],
        ];

        foreach ($distribuciones as $nombreSala => $distribucion) {
            $sala = DB::table('sala')
                ->where('nombre', $nombreSala)
                ->first();

            if (!$sala) {
                throw new \RuntimeException(
                    "No existe la sala: {$nombreSala}. " .
                        'Ejecuta primero SalaSeeder.'
                );
            }

            for ($i = 0; $i < $distribucion['filas']; $i++) {
                // Genera filas A, B, C... sin depender del ID de la sala.
                $fila = chr(ord('A') + $i);

                for (
                    $numero = 1;
                    $numero <= $distribucion['asientos_por_fila'];
                    $numero++
                ) {
                    // Los dos primeros asientos de la fila A
                    // se marcan como accesibles.
                    if ($fila === 'A' && $numero <= 2) {
                        $tipo = 'ACCESIBLE';
                    } elseif ($fila === 'A' && $numero <= 4) {
                        $tipo = 'PREFERENCIAL';
                    } else {
                        $tipo = 'ESTANDAR';
                    }

                    DB::table('asiento')->updateOrInsert(
                        [
                            'id_sala' => $sala->id_sala,
                            'fila' => $fila,
                            'numero' => $numero,
                        ],
                        [
                            'tipo' => $tipo,
                            'activo' => 1,
                        ]
                    );
                }
            }
        }
    }
}
