<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeliculaCreditoSeeder extends Seeder
{
    public function run(): void
    {
        $creditos = [
            // Zootopia 2
            ['pelicula' => 'Zootopia 2', 'persona' => 'Jared Bush', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Zootopia 2', 'persona' => 'Byron Howard', 'rol' => 'PRODUCTOR'],
            ['pelicula' => 'Zootopia 2', 'persona' => 'Jennifer Lee', 'rol' => 'GUIONISTA'],

            // Wicked: For Good
            ['pelicula' => 'Wicked: For Good', 'persona' => 'Jon M. Chu', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Wicked: For Good', 'persona' => 'Jennifer Lee', 'rol' => 'PRODUCTOR'],

            // Avatar: Fire and Ash
            ['pelicula' => 'Avatar: Fire and Ash', 'persona' => 'James Cameron', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Avatar: Fire and Ash', 'persona' => 'Sam Mendes', 'rol' => 'COMPOSITOR'],

            // El agente secreto
            ['pelicula' => 'El agente secreto', 'persona' => 'Rodrigo Bellott', 'rol' => 'PRODUCTOR'],
            ['pelicula' => 'El agente secreto', 'persona' => 'Alejandro Loayza Grisi', 'rol' => 'GUIONISTA'],

            // Hamnet
            ['pelicula' => 'Hamnet', 'persona' => 'Chloé Zhao', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Hamnet', 'persona' => 'Greta Gerwig', 'rol' => 'PRODUCTOR'],

            // Scream 7
            ['pelicula' => 'Scream 7', 'persona' => 'Matt Bettinelli-Olpin', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Scream 7', 'persona' => 'Tyler Gillett', 'rol' => 'PRODUCTOR'],

            // Hoppers
            ['pelicula' => 'Hoppers', 'persona' => 'Daniel Chong', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Hoppers', 'persona' => 'Andrew Stanton', 'rol' => 'GUIONISTA'],

            // The Super Mario Galaxy Movie
            ['pelicula' => 'The Super Mario Galaxy Movie', 'persona' => 'Aaron Horvath', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'The Super Mario Galaxy Movie', 'persona' => 'Michael Jelenic', 'rol' => 'GUIONISTA'],

            // Toy Story 5
            ['pelicula' => 'Toy Story 5', 'persona' => 'Andrew Stanton', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Toy Story 5', 'persona' => 'Angus MacLane', 'rol' => 'PRODUCTOR'],

            // Minions 3
            ['pelicula' => 'Minions 3', 'persona' => 'Pierre Coffin', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Minions 3', 'persona' => 'Andrew Stanton', 'rol' => 'PRODUCTOR'],

            // La última función
            ['pelicula' => 'La última función', 'persona' => 'Rodrigo Bellott', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'La última función', 'persona' => 'María Laura Quispe', 'rol' => 'ACTOR', 'personaje' => 'Elena'],

            // Código Andino
            ['pelicula' => 'Código Andino', 'persona' => 'Alejandro Loayza Grisi', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Código Andino', 'persona' => 'Carla Ortiz', 'rol' => 'ACTOR', 'personaje' => 'Ingeniera Valeria'],

            // El último boleto
            ['pelicula' => 'El último boleto', 'persona' => 'Diego Salvatierra', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'El último boleto', 'persona' => 'Lucía Fernández', 'rol' => 'ACTOR', 'personaje' => 'Clara'],

            // Risas de medianoche
            ['pelicula' => 'Risas de medianoche', 'persona' => 'Tomás Villarroel', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Risas de medianoche', 'persona' => 'Gabriel Mendoza', 'rol' => 'ACTOR', 'personaje' => 'Mateo'],

            // La casa del lago
            ['pelicula' => 'La casa del lago', 'persona' => 'Sofia Coppola', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'La casa del lago', 'persona' => 'Valentina Rojas', 'rol' => 'ACTOR', 'personaje' => 'Laura'],

            // Horizonte de fuego
            ['pelicula' => 'Horizonte de fuego', 'persona' => 'Denis Villeneuve', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Horizonte de fuego', 'persona' => 'Gabriel Mendoza', 'rol' => 'ACTOR', 'personaje' => 'Comandante Ríos'],

            // Navidad en La Paz
            ['pelicula' => 'Navidad en La Paz', 'persona' => 'Pedro Almodóvar', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Navidad en La Paz', 'persona' => 'Carla Ortiz', 'rol' => 'ACTOR', 'personaje' => 'Isabel'],

            // Planeta de cristal
            ['pelicula' => 'Planeta de cristal', 'persona' => 'Christopher Nolan', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Planeta de cristal', 'persona' => 'María Laura Quispe', 'rol' => 'ACTOR', 'personaje' => 'Exploradora Ana'],

            // El reino perdido
            ['pelicula' => 'El reino perdido', 'persona' => 'Greta Gerwig', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'El reino perdido', 'persona' => 'Diego Salvatierra', 'rol' => 'ACTOR', 'personaje' => 'Tomás'],

            // Frecuencia cero
            ['pelicula' => 'Frecuencia cero', 'persona' => 'Sofia Coppola', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Frecuencia cero', 'persona' => 'Lucía Fernández', 'rol' => 'ACTOR', 'personaje' => 'La locutora'],

            // El jardín de las estrellas
            ['pelicula' => 'El jardín de las estrellas', 'persona' => 'Alejandro Loayza Grisi', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'El jardín de las estrellas', 'persona' => 'Luis Bredow', 'rol' => 'ACTOR', 'personaje' => 'El abuelo'],

            // Operación Cóndor
            ['pelicula' => 'Operación Cóndor', 'persona' => 'Rodrigo Bellott', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Operación Cóndor', 'persona' => 'Valentina Rojas', 'rol' => 'ACTOR', 'personaje' => 'Agente Vega'],

            // El bosque susurrante
            ['pelicula' => 'El bosque susurrante', 'persona' => 'Denis Villeneuve', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'El bosque susurrante', 'persona' => 'Tomás Villarroel', 'rol' => 'ACTOR', 'personaje' => 'Andrés'],

            // Bajo el mismo cielo
            ['pelicula' => 'Bajo el mismo cielo', 'persona' => 'Pedro Almodóvar', 'rol' => 'DIRECTOR'],
            ['pelicula' => 'Bajo el mismo cielo', 'persona' => 'María Laura Quispe', 'rol' => 'ACTOR', 'personaje' => 'Sofía'],
        ];

        foreach ($creditos as $credito) {
            $idPelicula = DB::table('pelicula')
                ->where('titulo', $credito['pelicula'])
                ->value('id_pelicula');

            if ($idPelicula === null) {
                throw new RuntimeException(
                    "No existe la película: {$credito['pelicula']}. Ejecuta PeliculaSeeder primero."
                );
            }

            $idPersona = DB::table('persona')
                ->where('nombre_completo', $credito['persona'])
                ->value('id_persona');

            if ($idPersona === null) {
                throw new RuntimeException(
                    "No existe la persona: {$credito['persona']}. Ejecuta PersonaSeeder primero."
                );
            }

            DB::table('pelicula_credito')->updateOrInsert(
                [
                    'id_pelicula' => $idPelicula,
                    'id_persona' => $idPersona,
                    'rol' => $credito['rol'],
                ],
                [
                    'personaje' => $credito['personaje'] ?? null,
                    'orden' => $credito['orden'] ?? 0,
                ]
            );
        }
    }
}
