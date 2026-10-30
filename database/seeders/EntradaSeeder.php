<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class EntradaSeeder extends Seeder
{
    public function run(): void
    {
        $compras = DB::table('compra')
            ->orderBy('id_compra')
            ->get(['id_compra']);

        if ($compras->isEmpty()) {
            $this->command?->warn(
                'No hay compras. Ejecuta CompraSeeder primero.'
            );

            return;
        }

        $creadas = 0;
        $actualizadas = 0;

        foreach ($compras as $compra) {
            // Si esta compra ya tiene una entrada del seeder,
            // reutilizamos su asiento para no duplicarla.
            $entradaExistente = DB::table('entrada')
                ->where('id_compra', $compra->id_compra)
                ->orderBy('id_entrada')
                ->first();

            if ($entradaExistente) {
                $idFuncionAsiento =
                    $entradaExistente->id_funcion_asiento;

                $precio = DB::table('funcion_asiento')
                    ->where(
                        'id_funcion_asiento',
                        $idFuncionAsiento
                    )
                    ->value('precio');

                $actualizadas++;
            } else {
                // Elegimos un asiento de una función programada
                // que no tenga ya una entrada activa.
                $asientoFuncion = DB::table('funcion_asiento as fa')
                    ->join('funcion as f', 'f.id_funcion', '=', 'fa.id_funcion')
                    ->where('f.estado', 'PROGRAMADA')
                    ->whereNotIn(
                        'fa.id_funcion_asiento',
                        DB::table('entrada')
                            ->select('id_funcion_asiento')
                            ->whereIn('estado', [
                                'RESERVADA',
                                'CONFIRMADA',
                                'UTILIZADA',
                            ])
                    )
                    ->orderBy('f.inicio')
                    ->orderBy('fa.id_funcion_asiento')
                    ->select(
                        'fa.id_funcion_asiento',
                        'fa.precio'
                    )
                    ->first();

                if (!$asientoFuncion) {
                    $this->command?->warn(
                        "No hay asientos disponibles para la compra {$compra->id_compra}."
                    );

                    continue;
                }

                $idFuncionAsiento =
                    $asientoFuncion->id_funcion_asiento;

                $precio = $asientoFuncion->precio;
                $creadas++;
            }

            $ahora = Carbon::now();
            $vence = $ahora->copy()->addMinutes(15);

            DB::transaction(function () use (
                $compra,
                $idFuncionAsiento,
                $precio,
                $ahora,
                $vence
            ) {
                DB::table('entrada')->updateOrInsert(
                    [
                        'id_compra' => $compra->id_compra,
                        'id_funcion_asiento' => $idFuncionAsiento,
                    ],
                    [
                        'precio_venta' => $precio,
                        'estado' => 'RESERVADA',
                        'reservada_en' => $ahora,
                        'confirmada_en' => null,
                        'utilizada_en' => null,
                        'cerrada_en' => null,
                    ]
                );

                DB::table('compra')
                    ->where('id_compra', $compra->id_compra)
                    ->update([
                        'total' => $precio,
                        'estado' => 'PENDIENTE_PAGO',
                        'vence_reserva' => $vence,
                        'cancelada_en' => null,
                        'motivo_cancelacion' => null,
                    ]);
            });
        }

        $this->command?->info(
            "Entradas nuevas: {$creadas}. Entradas reutilizadas: {$actualizadas}."
        );
    }
}
