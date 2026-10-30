<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'name' => 'Admin',
                'email' => 'admin@cineup.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Roxana',
                'email' => 'roxana@cineup.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Alejandra',
                'email' => 'alejandra@cineup.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Usuario de prueba 1',
                'email' => 'usuario1@cineup.test',
                'role' => 'user',
            ],
            [
                'name' => 'Usuario de prueba 2',
                'email' => 'usuario2@cineup.test',
                'role' => 'user',
            ],
        ];

        foreach ($usuarios as $usuario) {
            DB::table('users')->updateOrInsert(
                ['email' => $usuario['email']],
                [
                    'name' => $usuario['name'],
                    'role' => $usuario['role'],
                    'password' => Hash::make('Cineup2026'),
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
