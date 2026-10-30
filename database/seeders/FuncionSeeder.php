<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use RuntimeException;

class FuncionSeeder extends Seeder
{
    public function run(): void
    {
        // Solo salas activas.
        $salas = DB::table('sala')
            ->where('estado', 'ACTIVA')
            ->orderBy('id_sala')
            ->get();

        if ($salas->isEmpty()) {
            throw new RuntimeException(
                'No existen salas activas para programar funciones.'
            );
        }

        // Solo películas aptas para programación:
        // activas y con duración definida.
        $peliculas = DB::table('pelicula')
            ->where('estado', 'ACTIVA')
            ->whereNotNull('duracion_minutos')
            ->where('duracion_minutos', '>', 0)
            ->orderBy('id_pelicula')
            ->get();

        if ($peliculas->isEmpty()) {
            throw new RuntimeException(
                'No hay películas activas con duración definida. ' .
                    'Revisa los datos de pelicula antes de continuar.'
            );
        }

        // Tres días de programación, con una función diaria
        // por sala. Así no se solapan funciones en una misma sala.
        $horarios = ['14:00:00', '17:30:00', '21:00:00'];

        $fechaBase = Carbon::today()->addDay();
        $indicePelicula = 0;

        foreach ($salas as $indiceSala => $sala) {
            for ($dia = 0; $dia < 3; $dia++) {
                $pelicula = $peliculas[$indicePelicula % $peliculas->count()];

                $inicio = $fechaBase->copy()
                    ->addDays($dia)
                    ->setTimeFromTimeString($horarios[$dia]);

                // La siguiente función de esta sala será al día siguiente.
                // Se incluye duración y margen de limpieza como referencia.
                $finEstimado = $inicio->copy()
                    ->addMinutes((int) $pelicula->duracion_minutos)
                    ->addMinutes((int) $sala->margen_limpieza_minutos);

                // Comprobación defensiva del horario frente a funciones
                // existentes que no estén canceladas.
                $conflicto = DB::table('funcion as f')
                    ->join(
                        'pelicula as p',
                        'p.id_pelicula',
                        '=',
                        'f.id_pelicula'
                    )
                    ->join('sala as s', 's.id_sala', '=', 'f.id_sala')
                    ->where('f.id_sala', $sala->id_sala)
                    ->where('f.estado', '!=', 'CANCELADA')
                    ->where('f.inicio', '<', $finEstimado->toDateTimeString())
                    ->whereRaw(
                        'DATE_ADD(
                            f.inicio,
                            INTERVAL (
                                p.duracion_minutos
                                + s.margen_limpieza_minutos
                            ) MINUTE
                        ) > ?',
                        [$inicio->toDateTimeString()]
                    )
                    ->exists();

                if ($conflicto) {
                    // No sobrescribir ni duplicar una función existente.
                    $indicePelicula++;
                    continue;
                }

                DB::table('funcion')->updateOrInsert(
                    [
                        'id_sala' => $sala->id_sala,
                        'inicio' => $inicio->toDateTimeString(),
                    ],
                    [
                        'id_pelicula' => $pelicula->id_pelicula,
                        'formato' => $dia === 1 ? '3D' : '2D',
                        'precio_base' => $dia === 1 ? 35.00 : 25.00,
                        'estado' => 'PROGRAMADA',
                    ]
                );

                $indicePelicula++;
            }
        }
    }
}
