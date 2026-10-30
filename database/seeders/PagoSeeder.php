<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PagoSeeder extends Seeder
{
    public function run(): void
    {
        $compras = DB::table('compra')
            ->orderBy('id_compra')
            ->get(['id_compra', 'total', 'estado']);

        if ($compras->isEmpty()) {
            $this->command?->warn(
                'No hay compras. Ejecuta CompraSeeder primero.'
            );

            return;
        }

        $creados = 0;
        $omitidos = 0;

        foreach ($compras as $indice => $compra) {
            $importe = (float) $compra->total;

            // La tabla pago exige un importe estrictamente positivo.
            if ($importe <= 0) {
                $this->command?->warn(
                    "Se omite la compra {$compra->id_compra}: "
                        . 'su total debe ser mayor que cero.'
                );

                $omitidos++;

                continue;
            }

            // No generamos pagos para compras canceladas o expiradas.
            if (in_array($compra->estado, ['CANCELADA', 'EXPIRADA'], true)) {
                $omitidos++;

                continue;
            }

            // Distribución reproducible para datos de demostración:
            // 7 pagos pendientes y 3 intentos rechazados por cada
            // grupo completo de 10 compras.
            $estado = (($indice + 1) % 4 === 0)
                ? 'RECHAZADO'
                : 'PENDIENTE';

            $metodo = ($indice % 2 === 0)
                ? 'QR'
                : 'TARJETA';

            // Referencia determinista: evita duplicar registros
            // al ejecutar el seeder nuevamente.
            $referencia = sprintf(
                'CINEPRIME-PAGO-%06d',
                $compra->id_compra
            );

            $creadoEn = Carbon::now()
                ->subMinutes(count($compras) - $indice);

            DB::table('pago')->updateOrInsert(
                [
                    'referencia_externa' => $referencia,
                ],
                [
                    'id_compra' => $compra->id_compra,
                    'metodo' => $metodo,
                    'importe' => $importe,
                    'estado' => $estado,
                    'creado_en' => $creadoEn,
                    'aprobado_en' => null,
                ]
            );

            $creados++;
        }

        $this->command?->info(
            "Pagos procesados: {$creados}. "
                . "Compras omitidas: {$omitidos}."
        );
    }
}
