<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            /*
             * 1. Usuarios del sistema
             */
            UsuarioSeeder::class,

            /*
             * 2. Catálogos de películas
             */
            IdiomaSeeder::class,
            PaisSeeder::class,
            GeneroSeeder::class,
            PeliculaSeeder::class,
            PersonaSeeder::class,
            PeliculaCreditoSeeder::class,
            PeliculaPaisSeeder::class,
            PeliculaMultimediaSeeder::class,

            /*
             * 3. Salas, asientos y programación
             */
            SalaSeeder::class,
            AsientoSeeder::class,
            SalaPasilloSeeder::class,
            FuncionSeeder::class,
            FuncionAsientoSeeder::class,

            /*
             * 4. Compradores y confitería
             */
            CompradorSeeder::class,
            CategoriaProductoSeeder::class,
            ProductoSeeder::class,

            /*
             * 5. Compras, entradas y pagos de prueba
             */
            CompraSeeder::class,
            EntradaSeeder::class,
            PagoSeeder::class,

            /*
             * 6. Escenarios confirmados y comprobantes
             */
            EscenariosCompraSeeder::class,
            TicketSeeder::class,
            ReembolsoSeeder::class,

            /*
             * 7. Productos asociados a las compras originales
             */
            DetalleCompraSeeder::class,
        ]);
    }
}
