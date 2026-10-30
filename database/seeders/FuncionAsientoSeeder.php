<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FuncionAsientoSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener los asientos que pertenecen a la sala
        // asignada a cada función.
        $asientosPorFuncion = DB::table('funcion as f')
            ->join('asiento as a', 'a.id_sala', '=', 'f.id_sala')
            ->select(
                'f.id_funcion',
                'f.precio_base',
                'a.id_asiento'
            )
            ->orderBy('f.id_funcion')
            ->orderBy('a.id_asiento')
            ->get();

        if ($asientosPorFuncion->isEmpty()) {
            throw new \RuntimeException(
                'No se encontraron asientos asociados a las funciones.'
            );
        }

        foreach ($asientosPorFuncion as $registro) {
            DB::table('funcion_asiento')->updateOrInsert(
                [
                    'id_funcion' => $registro->id_funcion,
                    'id_asiento' => $registro->id_asiento,
                ],
                [
                    'precio' => $registro->precio_base,
                ]
            );
        }
    }
}
