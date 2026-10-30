<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompraSeeder extends Seeder
{
    public function run(): void
    {
        $compradores = DB::table('comprador')
            ->orderBy('id_comprador')
            ->get(['id_comprador']);

        if ($compradores->isEmpty()) {
            $this->command?->warn(
                'No hay compradores. Ejecuta CompradorSeeder primero.'
            );

            return;
        }

        foreach ($compradores as $indice => $comprador) {
            // Fecha fija para que volver a ejecutar el seeder
            // actualice la misma compra en lugar de duplicarla.
            $creadaEn = sprintf(
                '2026-10-%02d %02d:00:00',
                18 + ($indice % 10),
                10 + ($indice % 8)
            );

            DB::table('compra')->updateOrInsert(
                [
                    'id_comprador' => $comprador->id_comprador,
                    'creada_en' => $creadaEn,
                ],
                [
                    'vence_reserva' => null,
                    'moneda' => 'BOB',
                    'total' => 0.00,
                    'estado' => 'INICIADA',
                    'cancelada_en' => null,
                    'motivo_cancelacion' => null,
                ]
            );
        }

        $this->command?->info(
            "Compras creadas o actualizadas: {$compradores->count()}."
        );
    }
}
