<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalaPasilloSeeder extends Seeder
{
    public function run(): void
    {
        $nombresSalas = [
            'Sala 1',
            'Sala 2',
            'Sala 3',
            'Sala Premium',
            'Sala 5',
        ];

        foreach ($nombresSalas as $nombreSala) {
            $sala = DB::table('sala')
                ->where('nombre', $nombreSala)
                ->first();

            if (!$sala) {
                throw new \RuntimeException(
                    "No existe la sala: {$nombreSala}. " .
                        'Ejecuta primero SalaSeeder.'
                );
            }

            // Obtener las filas que realmente existen en esta sala.
            $filas = DB::table('asiento')
                ->where('id_sala', $sala->id_sala)
                ->distinct()
                ->orderBy('fila')
                ->pluck('fila')
                ->toArray();

            if (empty($filas)) {
                throw new \RuntimeException(
                    "La sala {$nombreSala} no tiene asientos."
                );
            }

            // Pasillo longitudinal: después del asiento 5
            // en cada fila que tenga al menos 6 asientos.
            foreach ($filas as $fila) {
                $tienePosicion = DB::table('asiento')
                    ->where('id_sala', $sala->id_sala)
                    ->where('fila', $fila)
                    ->where('numero', '>', 5)
                    ->exists();

                if ($tienePosicion) {
                    DB::table('sala_pasillo')->updateOrInsert(
                        [
                            'id_sala' => $sala->id_sala,
                            'fila' => $fila,
                            'despues_de_numero' => 5,
                        ],
                        [
                            'orientacion' => 'LONGITUDINAL',
                            'fila_siguiente' => null,
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            // Pasillos transversales entre filas consecutivas.
            for ($i = 0; $i < count($filas) - 1; $i++) {
                $filaActual = $filas[$i];
                $filaSiguiente = $filas[$i + 1];

                DB::table('sala_pasillo')->updateOrInsert(
                    [
                        'id_sala' => $sala->id_sala,
                        'orientacion' => 'TRANSVERSAL',
                        'fila' => $filaActual,
                        'fila_siguiente' => $filaSiguiente,
                    ],
                    [
                        'despues_de_numero' => null,
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
