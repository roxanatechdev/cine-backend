<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompradorSeeder extends Seeder
{
    public function run(): void
    {
        $compradores = [
            [
                'nombre' => 'Ana Martínez',
                'correo' => 'ana.martinez@example.com',
                'telefono' => '70010001',
                'tipo_comprador' => 'REGISTRADO',
            ],
            [
                'nombre' => 'Carlos Rojas',
                'correo' => 'carlos.rojas@example.com',
                'telefono' => '70010002',
                'tipo_comprador' => 'REGISTRADO',
            ],
            [
                'nombre' => 'Lucía Fernández',
                'correo' => 'lucia.fernandez@example.com',
                'telefono' => '70010003',
                'tipo_comprador' => 'REGISTRADO',
            ],
            [
                'nombre' => 'Diego Vargas',
                'correo' => 'diego.vargas@example.com',
                'telefono' => null,
                'tipo_comprador' => 'REGISTRADO',
            ],
            [
                'nombre' => 'Mariana López',
                'correo' => 'mariana.lopez@example.com',
                'telefono' => '70010005',
                'tipo_comprador' => 'REGISTRADO',
            ],
            [
                'nombre' => 'Javier Morales',
                'correo' => 'javier.morales@example.com',
                'telefono' => null,
                'tipo_comprador' => 'INVITADO',
            ],
            [
                'nombre' => 'Sofía Castillo',
                'correo' => 'sofia.castillo@example.com',
                'telefono' => '70010007',
                'tipo_comprador' => 'INVITADO',
            ],
            [
                'nombre' => 'Andrés Herrera',
                'correo' => 'andres.herrera@example.com',
                'telefono' => '70010008',
                'tipo_comprador' => 'INVITADO',
            ],
            [
                'nombre' => 'Valentina Cruz',
                'correo' => 'valentina.cruz@example.com',
                'telefono' => null,
                'tipo_comprador' => 'INVITADO',
            ],
            [
                'nombre' => 'Mateo Aguilar',
                'correo' => 'mateo.aguilar@example.com',
                'telefono' => '70010010',
                'tipo_comprador' => 'INVITADO',
            ],
        ];

        foreach ($compradores as $comprador) {
            DB::table('comprador')->updateOrInsert(
                [
                    'correo' => $comprador['correo'],
                ],
                [
                    'nombre' => $comprador['nombre'],
                    'telefono' => $comprador['telefono'],
                    'tipo_comprador' => $comprador['tipo_comprador'],
                ]
            );
        }
    }
}
