<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use RuntimeException;

class ReembolsoSeeder extends Seeder
{
    public function run(): void
    {
        $escenarios = [
            [
                'caso' => 'B',
                'referencia_pago' => 'CINEPRIME-ESCENARIO-B',
                'referencia_reembolso' => 'CINEPRIME-REEMBOLSO-B',
                'importe' => 10.00,
                'estado' => 'SOLICITADO',
                'motivo' => 'Solicitud de reembolso parcial de prueba.',
                'completado_en' => null,
            ],
            [
                'caso' => 'C',
                'referencia_pago' => 'CINEPRIME-ESCENARIO-C',
                'referencia_reembolso' => 'CINEPRIME-REEMBOLSO-C',
                'importe' => 10.00,
                'estado' => 'COMPLETADO',
                'motivo' => 'Reembolso parcial completado para pruebas.',
                'completado_en' => Carbon::now()->toDateTimeString(),
            ],
        ];

        $creadosOActualizados = 0;

        foreach ($escenarios as $escenario) {
            DB::transaction(function () use (
                $escenario,
                &$creadosOActualizados
            ) {
                // 1. Buscar el pago por su referencia estable.
                $pago = DB::table('pago')
                    ->where(
                        'referencia_externa',
                        $escenario['referencia_pago']
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $pago) {
                    throw new RuntimeException(
                        'No se encontró el pago del caso '
                            . $escenario['caso']
                            . '. Ejecuta EscenariosCompraSeeder primero.'
                    );
                }

                // 2. Solo se permiten reembolsos de pagos aprobados.
                if ($pago->estado !== 'APROBADO') {
                    throw new RuntimeException(
                        'El pago del caso '
                            . $escenario['caso']
                            . ' no está aprobado.'
                    );
                }

                // 3. Comprobar que el importe no exceda el pago.
                if (
                    (float) $escenario['importe'] <= 0
                    || (float) $escenario['importe'] > (float) $pago->importe
                ) {
                    throw new RuntimeException(
                        'El importe del reembolso del caso '
                            . $escenario['caso']
                            . ' no es válido.'
                    );
                }

                // 4. Verificar que la compra tenga ticket.
                $ticket = DB::table('ticket')
                    ->where('id_compra', $pago->id_compra)
                    ->first();

                if (! $ticket) {
                    throw new RuntimeException(
                        'La compra del caso '
                            . $escenario['caso']
                            . ' no tiene ticket. Ejecuta TicketSeeder primero.'
                    );
                }

                // 5. Evitar que los reembolsos completados
                // acumulados superen el importe aprobado.
                $completadosExistentes = DB::table('reembolso')
                    ->where('id_pago', $pago->id_pago)
                    ->where('estado', 'COMPLETADO')
                    ->where(
                        'referencia_externa',
                        '!=',
                        $escenario['referencia_reembolso']
                    )
                    ->sum('importe');

                $reembolsoActual = DB::table('reembolso')
                    ->where(
                        'referencia_externa',
                        $escenario['referencia_reembolso']
                    )
                    ->first();

                $totalCompletado = (float) $completadosExistentes;

                if (
                    $escenario['estado'] === 'COMPLETADO'
                    && $reembolsoActual
                    && $reembolsoActual->estado === 'COMPLETADO'
                ) {
                    $totalCompletado += (float) $reembolsoActual->importe;
                } elseif ($escenario['estado'] === 'COMPLETADO') {
                    $totalCompletado += (float) $escenario['importe'];
                }

                if ($totalCompletado > (float) $pago->importe) {
                    throw new RuntimeException(
                        'Los reembolsos completados superarían '
                            . 'el importe del pago del caso '
                            . $escenario['caso'] . '.'
                    );
                }

                // 6. Crear o actualizar el reembolso sin duplicarlo.
                DB::table('reembolso')->updateOrInsert(
                    [
                        'referencia_externa' =>
                        $escenario['referencia_reembolso'],
                    ],
                    [
                        'id_pago' => $pago->id_pago,
                        'importe' => $escenario['importe'],
                        'motivo' => $escenario['motivo'],
                        'estado' => $escenario['estado'],
                        'solicitado_en' => Carbon::now()
                            ->toDateTimeString(),
                        'completado_en' =>
                        $escenario['completado_en'],
                    ]
                );

                $creadosOActualizados++;

                $this->command?->info(
                    'Caso ' . $escenario['caso']
                        . ': reembolso '
                        . $escenario['estado']
                        . ' preparado.'
                );
            });
        }

        $this->command?->info(
            "Reembolsos creados o actualizados: "
                . $creadosOActualizados . '.'
        );
    }
}
