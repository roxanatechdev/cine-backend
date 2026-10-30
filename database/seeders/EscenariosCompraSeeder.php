<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use RuntimeException;

class EscenariosCompraSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Tres casos de prueba:
         * A: compra confirmada, pago aprobado y ticket por emitir.
         * B: compra confirmada, pago aprobado y reembolso solicitado.
         * C: compra confirmada, pago aprobado y reembolso completado.
         *
         * Los reembolsos se crearán posteriormente con ReembolsoSeeder.
         */

        $escenarios = [
            [
                'caso' => 'A',
                'correo' => 'ana.martinez@example.com',
                'creada_en' => '2026-10-28 18:01:00',
                'referencia' => 'CINEPRIME-ESCENARIO-A',
                'metodo' => 'QR',
            ],
            [
                'caso' => 'B',
                'correo' => 'carlos.rojas@example.com',
                'creada_en' => '2026-10-28 18:02:00',
                'referencia' => 'CINEPRIME-ESCENARIO-B',
                'metodo' => 'TARJETA',
            ],
            [
                'caso' => 'C',
                'correo' => 'lucia.fernandez@example.com',
                'creada_en' => '2026-10-28 18:03:00',
                'referencia' => 'CINEPRIME-ESCENARIO-C',
                'metodo' => 'QR',
            ],
        ];

        $funcionesUsadas = [];
        $creadas = 0;
        $actualizadas = 0;

        foreach ($escenarios as $escenario) {
            DB::transaction(function () use (
                $escenario,
                &$funcionesUsadas,
                &$creadas,
                &$actualizadas
            ) {
                // 1. Localizar al comprador existente.
                $comprador = DB::table('comprador')
                    ->where('correo', $escenario['correo'])
                    ->first();

                if (! $comprador) {
                    throw new RuntimeException(
                        'No existe el comprador '
                            . $escenario['correo']
                            . '. Ejecuta CompradorSeeder primero.'
                    );
                }

                // 2. Crear o recuperar la compra de prueba.
                DB::table('compra')->updateOrInsert(
                    [
                        'id_comprador' => $comprador->id_comprador,
                        'creada_en' => $escenario['creada_en'],
                    ],
                    [
                        'vence_reserva' => null,
                        'moneda' => 'BOB',
                        'total' => 0,
                        'estado' => 'CONFIRMADA',
                        'cancelada_en' => null,
                        'motivo_cancelacion' => null,
                    ]
                );

                $compra = DB::table('compra')
                    ->where(
                        'id_comprador',
                        $comprador->id_comprador
                    )
                    ->where('creada_en', $escenario['creada_en'])
                    ->first();

                if (! $compra) {
                    throw new RuntimeException(
                        'No se pudo recuperar la compra del caso '
                            . $escenario['caso'] . '.'
                    );
                }

                // 3. Reutilizar la entrada si el seeder ya se ejecutó.
                $entrada = DB::table('entrada')
                    ->where('id_compra', $compra->id_compra)
                    ->orderBy('id_entrada')
                    ->first();

                if ($entrada) {
                    $asientoFuncion = DB::table(
                        'funcion_asiento'
                    )
                        ->where(
                            'id_funcion_asiento',
                            $entrada->id_funcion_asiento
                        )
                        ->first();

                    if (! $asientoFuncion) {
                        throw new RuntimeException(
                            'No se encontró el asiento de la compra '
                                . $compra->id_compra . '.'
                        );
                    }

                    $idFuncionAsiento =
                        $asientoFuncion->id_funcion_asiento;

                    $precio = $asientoFuncion->precio;
                    $actualizadas++;
                } else {
                    // 4. Buscar un asiento libre de una función futura.
                    $consulta = DB::table('funcion_asiento as fa')
                        ->join(
                            'funcion as f',
                            'f.id_funcion',
                            '=',
                            'fa.id_funcion'
                        )
                        ->where('f.estado', 'PROGRAMADA')
                        ->where('f.inicio', '>', now())
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
                        ->orderBy('f.id_funcion')
                        ->orderBy('fa.id_funcion_asiento');

                    if (! empty($funcionesUsadas)) {
                        $consulta->whereNotIn(
                            'f.id_funcion',
                            $funcionesUsadas
                        );
                    }

                    $asientoFuncion = $consulta
                        ->select([
                            'fa.id_funcion',
                            'fa.id_funcion_asiento',
                            'fa.precio',
                        ])
                        ->lockForUpdate()
                        ->first();

                    if (! $asientoFuncion) {
                        throw new RuntimeException(
                            'No hay suficientes asientos libres '
                                . 'en funciones futuras para completar '
                                . 'el caso ' . $escenario['caso'] . '.'
                        );
                    }

                    $idFuncionAsiento =
                        $asientoFuncion->id_funcion_asiento;

                    $precio = $asientoFuncion->precio;

                    $funcionesUsadas[] =
                        $asientoFuncion->id_funcion;

                    DB::table('entrada')->insert([
                        'id_compra' => $compra->id_compra,
                        'id_funcion_asiento' => $idFuncionAsiento,
                        'precio_venta' => $precio,
                        'estado' => 'CONFIRMADA',
                        'reservada_en' => now(),
                        'confirmada_en' => now(),
                        'utilizada_en' => null,
                        'cerrada_en' => null,
                    ]);

                    $creadas++;
                }

                // 5. Asegurar que la entrada existente esté confirmada.
                DB::table('entrada')
                    ->where('id_compra', $compra->id_compra)
                    ->update([
                        'precio_venta' => $precio,
                        'estado' => 'CONFIRMADA',
                        'confirmada_en' => now(),
                        'utilizada_en' => null,
                        'cerrada_en' => null,
                    ]);

                // 6. Actualizar la compra con el importe correcto.
                DB::table('compra')
                    ->where('id_compra', $compra->id_compra)
                    ->update([
                        'total' => $precio,
                        'estado' => 'CONFIRMADA',
                        'vence_reserva' => null,
                        'cancelada_en' => null,
                        'motivo_cancelacion' => null,
                    ]);

                // 7. Crear o actualizar el pago aprobado.
                DB::table('pago')->updateOrInsert(
                    [
                        'referencia_externa' =>
                        $escenario['referencia'],
                    ],
                    [
                        'id_compra' => $compra->id_compra,
                        'metodo' => $escenario['metodo'],
                        'importe' => $precio,
                        'estado' => 'APROBADO',
                        'creado_en' => now(),
                        'aprobado_en' => now(),
                    ]
                );

                $this->command?->info(
                    'Caso ' . $escenario['caso']
                        . ' preparado. Compra ID: '
                        . $compra->id_compra
                        . ', importe: ' . $precio
                );
            });
        }

        $this->command?->info(
            "Escenarios completados. "
                . "Entradas nuevas: {$creadas}; "
                . "entradas reutilizadas: {$actualizadas}."
        );
    }
}
