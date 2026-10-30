<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetalleCompraSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Detalles de productos para las 10 compras originales.
         * La clave es el ID de la compra y cada producto aparece
         * una sola vez por compra.
         */
        $detalles = [
            1  => [
                ['producto' => 'Cabritas Medianas', 'cantidad' => 1],
                ['producto' => 'Agua Mineral 500 ml', 'cantidad' => 2],
            ],
            2  => [
                ['producto' => 'Combo Clásico', 'cantidad' => 1],
            ],
            3  => [
                ['producto' => 'Gaseosa Mediana 500 ml', 'cantidad' => 2],
                ['producto' => 'Chocolate', 'cantidad' => 1],
            ],
            4  => [
                ['producto' => 'Cabritas Grandes', 'cantidad' => 1],
            ],
            5  => [
                ['producto' => 'Combo Familiar', 'cantidad' => 1],
            ],
            6  => [
                ['producto' => 'Agua Mineral 500 ml', 'cantidad' => 1],
                ['producto' => 'Gomitas', 'cantidad' => 2],
            ],
            7  => [
                ['producto' => 'Hot Dog', 'cantidad' => 1],
            ],
            8  => [
                ['producto' => 'Nachos con Queso', 'cantidad' => 1],
                ['producto' => 'Gaseosa Grande 1 litro', 'cantidad' => 1],
            ],
            9  => [
                ['producto' => 'Combo Pareja', 'cantidad' => 1],
            ],
            10 => [
                ['producto' => 'Cabritas Pequeñas', 'cantidad' => 2],
            ],
        ];

        DB::transaction(function () use ($detalles) {
            foreach ($detalles as $idCompra => $productos) {
                $compra = DB::table('compra')
                    ->where('id_compra', $idCompra)
                    ->first();

                if (!$compra) {
                    $this->command?->warn(
                        "No existe la compra {$idCompra}; se omite."
                    );

                    continue;
                }

                // No tocar compras confirmadas ni las de otros escenarios.
                if ($compra->estado !== 'PENDIENTE_PAGO') {
                    $this->command?->warn(
                        "La compra {$idCompra} no está pendiente de pago; "
                            . 'se omite para proteger sus datos.'
                    );

                    continue;
                }

                foreach ($productos as $item) {
                    $producto = DB::table('producto')
                        ->where('nombre', $item['producto'])
                        ->first();

                    if (!$producto) {
                        throw new \RuntimeException(
                            "No existe el producto: {$item['producto']}. "
                                . 'Ejecuta ProductoSeeder primero.'
                        );
                    }

                    if ($producto->estado !== 'ACTIVO') {
                        throw new \RuntimeException(
                            "El producto {$item['producto']} no está activo."
                        );
                    }

                    DB::table('detalle_compra')->updateOrInsert(
                        [
                            'id_compra' => $idCompra,
                            'id_producto' => $producto->id_producto,
                        ],
                        [
                            'cantidad' => $item['cantidad'],
                            'precio_unitario' => $producto->precio_actual,
                        ]
                    );
                }

                // Sumar entradas y productos para mantener el total coherente.
                $totalEntradas = DB::table('entrada')
                    ->where('id_compra', $idCompra)
                    ->whereNotIn('estado', ['CANCELADA', 'EXPIRADA'])
                    ->sum('precio_venta');

                $totalProductos = DB::table('detalle_compra')
                    ->where('id_compra', $idCompra)
                    ->selectRaw(
                        'COALESCE(SUM(cantidad * precio_unitario), 0) AS total'
                    )
                    ->value('total');

                $total = round(
                    (float) $totalEntradas + (float) $totalProductos,
                    2
                );

                DB::table('compra')
                    ->where('id_compra', $idCompra)
                    ->update(['total' => $total]);

                // Mantener el importe del pago alineado con el total.
                DB::table('pago')
                    ->where('id_compra', $idCompra)
                    ->update(['importe' => $total]);
            }
        });

        $this->command?->info(
            'DetalleCompraSeeder finalizado. '
                . 'Se procesaron las compras originales elegibles.'
        );
    }
}
