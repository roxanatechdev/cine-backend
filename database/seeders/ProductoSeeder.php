<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            // Combos
            [
                'categoria' => 'Combos',
                'nombre' => 'Combo Clásico',
                'descripcion' => 'Cabritas medianas y gaseosa mediana.',
                'precio_actual' => 35.00,
                'stock' => 40,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Combos',
                'nombre' => 'Combo Familiar',
                'descripcion' => 'Cabritas grandes y cuatro gaseosas.',
                'precio_actual' => 75.00,
                'stock' => 20,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Combos',
                'nombre' => 'Combo Premium',
                'descripcion' => 'Cabritas grandes, dos bebidas y dos snacks.',
                'precio_actual' => 65.00,
                'stock' => 15,
                'estado' => 'ACTIVO',
            ],

            // Bebidas
            [
                'categoria' => 'Bebidas',
                'nombre' => 'Agua Mineral 500 ml',
                'descripcion' => 'Botella de agua mineral de 500 ml.',
                'precio_actual' => 8.00,
                'stock' => 100,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Bebidas',
                'nombre' => 'Gaseosa Mediana 500 ml',
                'descripcion' => 'Gaseosa en botella de 500 ml.',
                'precio_actual' => 12.00,
                'stock' => 80,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Bebidas',
                'nombre' => 'Gaseosa Grande 1 litro',
                'descripcion' => 'Gaseosa en botella de un litro.',
                'precio_actual' => 18.00,
                'stock' => 50,
                'estado' => 'ACTIVO',
            ],

            // Cabritas
            [
                'categoria' => 'Cabritas',
                'nombre' => 'Cabritas Pequeñas',
                'descripcion' => 'Porción individual de cabritas.',
                'precio_actual' => 12.00,
                'stock' => 60,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Cabritas',
                'nombre' => 'Cabritas Medianas',
                'descripcion' => 'Porción mediana de cabritas.',
                'precio_actual' => 20.00,
                'stock' => 50,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Cabritas',
                'nombre' => 'Cabritas Grandes',
                'descripcion' => 'Porción grande para compartir.',
                'precio_actual' => 30.00,
                'stock' => 35,
                'estado' => 'ACTIVO',
            ],

            // Snacks
            [
                'categoria' => 'Snacks',
                'nombre' => 'Chocolate',
                'descripcion' => 'Barra de chocolate individual.',
                'precio_actual' => 12.00,
                'stock' => 40,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Snacks',
                'nombre' => 'Gomitas',
                'descripcion' => 'Bolsa individual de gomitas.',
                'precio_actual' => 8.00,
                'stock' => 45,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Snacks',
                'nombre' => 'Nachos Empaquetados',
                'descripcion' => 'Porción individual de nachos empaquetados.',
                'precio_actual' => 15.00,
                'stock' => 30,
                'estado' => 'ACTIVO',
            ],

            // Comida rápida
            [
                'categoria' => 'Comida rápida',
                'nombre' => 'Hot Dog',
                'descripcion' => 'Pan con salchicha y acompañamientos.',
                'precio_actual' => 20.00,
                'stock' => 30,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Comida rápida',
                'nombre' => 'Nachos con Queso',
                'descripcion' => 'Nachos servidos con salsa de queso.',
                'precio_actual' => 28.00,
                'stock' => 25,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Comida rápida',
                'nombre' => 'Hot Dog con Bebida',
                'descripcion' => 'Hot dog acompañado de una gaseosa mediana.',
                'precio_actual' => 30.00,
                'stock' => 25,
                'estado' => 'ACTIVO',
            ],

            // Promociones
            [
                'categoria' => 'Promociones',
                'nombre' => 'Combo Pareja',
                'descripcion' => 'Cabritas grandes y dos bebidas medianas.',
                'precio_actual' => 45.00,
                'stock' => 15,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Promociones',
                'nombre' => 'Combo Infantil',
                'descripcion' => 'Cabritas pequeñas, bebida y golosina.',
                'precio_actual' => 30.00,
                'stock' => 20,
                'estado' => 'ACTIVO',
            ],
            [
                'categoria' => 'Promociones',
                'nombre' => 'Combo de Medianoche',
                'descripcion' => 'Cabritas medianas y dos snacks.',
                'precio_actual' => 32.00,
                'stock' => 0,
                'estado' => 'AGOTADO',
            ],
        ];

        foreach ($productos as $producto) {
            $categoria = DB::table('categoria_producto')
                ->where('nombre', $producto['categoria'])
                ->first();

            if (! $categoria) {
                throw new RuntimeException(
                    'No existe la categoría "'
                        . $producto['categoria']
                        . '". Ejecuta CategoriaProductoSeeder primero.'
                );
            }

            DB::table('producto')->updateOrInsert(
                [
                    'nombre' => $producto['nombre'],
                ],
                [
                    'id_categoria_producto' =>
                    $categoria->id_categoria_producto,
                    'descripcion' => $producto['descripcion'],
                    'precio_actual' => $producto['precio_actual'],
                    'imagen' => null,
                    'stock' => $producto['stock'],
                    'estado' => $producto['estado'],
                ]
            );
        }

        $this->command?->info(
            'Productos procesados: ' . count($productos) . '.'
        );
    }
}
