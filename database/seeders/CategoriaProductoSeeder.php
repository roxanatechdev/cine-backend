<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaProductoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Combos',
                'descripcion' => 'Combinaciones de alimentos y bebidas.',
                'activo' => 1,
            ],
            [
                'nombre' => 'Bebidas',
                'descripcion' => 'Gaseosas, agua y otras bebidas.',
                'activo' => 1,
            ],
            [
                'nombre' => 'Cabritas',
                'descripcion' => 'Cabritas dulces, saladas y de distintos tamaños.',
                'activo' => 1,
            ],
            [
                'nombre' => 'Snacks',
                'descripcion' => 'Chocolates, dulces y golosinas.',
                'activo' => 1,
            ],
            [
                'nombre' => 'Comida rápida',
                'descripcion' => 'Hot dogs, nachos y alimentos preparados.',
                'activo' => 1,
            ],
            [
                'nombre' => 'Promociones',
                'descripcion' => 'Paquetes y ofertas especiales.',
                'activo' => 1,
            ],
        ];

        foreach ($categorias as $categoria) {
            DB::table('categoria_producto')->updateOrInsert(
                [
                    'nombre' => $categoria['nombre'],
                ],
                [
                    'descripcion' => $categoria['descripcion'],
                    'activo' => $categoria['activo'],
                ]
            );
        }

        $this->command?->info(
            'Categorías de productos procesadas: '
                . count($categorias) . '.'
        );
    }
}
