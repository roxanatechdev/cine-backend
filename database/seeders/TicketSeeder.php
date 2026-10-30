<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $compras = DB::table('compra')
            ->where('estado', 'CONFIRMADA')
            ->orderBy('id_compra')
            ->get(['id_compra']);

        if ($compras->isEmpty()) {
            $this->command?->warn(
                'No hay compras confirmadas. '
                    . 'No se emitirán tickets.'
            );

            return;
        }

        $creados = 0;
        $existentes = 0;

        foreach ($compras as $compra) {
            // Cada compra puede tener un único ticket.
            $ticketExistente = DB::table('ticket')
                ->where('id_compra', $compra->id_compra)
                ->first();

            if ($ticketExistente) {
                $existentes++;
                continue;
            }

            // Código estable y único para cada compra.
            $codigo = sprintf(
                'CP-%08d',
                $compra->id_compra
            );

            DB::table('ticket')->insert([
                'id_compra' => $compra->id_compra,
                'codigo_validacion' => $codigo,
                'emitido_en' => Carbon::now(),
                'estado' => 'EMITIDO',
                'utilizado_en' => null,
            ]);

            $creados++;
        }

        $this->command?->info(
            "Tickets creados: {$creados}. "
                . "Tickets existentes conservados: {$existentes}."
        );
    }
}
