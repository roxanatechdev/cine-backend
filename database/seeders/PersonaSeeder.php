<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonaSeeder extends Seeder
{
    public function run(): void
    {
        $personas = [
            ['nombre_completo' => 'Jared Bush', 'nombre_busqueda' => 'Jared Bush'],
            ['nombre_completo' => 'Byron Howard', 'nombre_busqueda' => 'Byron Howard'],
            ['nombre_completo' => 'Jennifer Lee', 'nombre_busqueda' => 'Jennifer Lee'],
            ['nombre_completo' => 'Jon M. Chu', 'nombre_busqueda' => 'Jon M Chu'],
            ['nombre_completo' => 'James Cameron', 'nombre_busqueda' => 'James Cameron'],
            ['nombre_completo' => 'Sam Mendes', 'nombre_busqueda' => 'Sam Mendes'],
            ['nombre_completo' => 'Chloé Zhao', 'nombre_busqueda' => 'Chloe Zhao'],
            ['nombre_completo' => 'Matt Bettinelli-Olpin', 'nombre_busqueda' => 'Matt Bettinelli-Olpin'],
            ['nombre_completo' => 'Tyler Gillett', 'nombre_busqueda' => 'Tyler Gillett'],
            ['nombre_completo' => 'Daniel Chong', 'nombre_busqueda' => 'Daniel Chong'],
            ['nombre_completo' => 'Aaron Horvath', 'nombre_busqueda' => 'Aaron Horvath'],
            ['nombre_completo' => 'Michael Jelenic', 'nombre_busqueda' => 'Michael Jelenic'],
            ['nombre_completo' => 'Andrew Stanton', 'nombre_busqueda' => 'Andrew Stanton'],
            ['nombre_completo' => 'Angus MacLane', 'nombre_busqueda' => 'Angus MacLane'],
            ['nombre_completo' => 'Pierre Coffin', 'nombre_busqueda' => 'Pierre Coffin'],
            ['nombre_completo' => 'Greta Gerwig', 'nombre_busqueda' => 'Greta Gerwig'],
            ['nombre_completo' => 'Denis Villeneuve', 'nombre_busqueda' => 'Denis Villeneuve'],
            ['nombre_completo' => 'Christopher Nolan', 'nombre_busqueda' => 'Christopher Nolan'],
            ['nombre_completo' => 'Sofia Coppola', 'nombre_busqueda' => 'Sofia Coppola'],
            ['nombre_completo' => 'Pedro Almodóvar', 'nombre_busqueda' => 'Pedro Almodovar'],
            ['nombre_completo' => 'Rodrigo Bellott', 'nombre_busqueda' => 'Rodrigo Bellott'],
            ['nombre_completo' => 'Alejandro Loayza Grisi', 'nombre_busqueda' => 'Alejandro Loayza Grisi'],
            ['nombre_completo' => 'Carla Ortiz', 'nombre_busqueda' => 'Carla Ortiz'],
            ['nombre_completo' => 'Luis Bredow', 'nombre_busqueda' => 'Luis Bredow'],
            ['nombre_completo' => 'María Laura Quispe', 'nombre_busqueda' => 'Maria Laura Quispe'],
            ['nombre_completo' => 'Diego Salvatierra', 'nombre_busqueda' => 'Diego Salvatierra'],
            ['nombre_completo' => 'Valentina Rojas', 'nombre_busqueda' => 'Valentina Rojas'],
            ['nombre_completo' => 'Gabriel Mendoza', 'nombre_busqueda' => 'Gabriel Mendoza'],
            ['nombre_completo' => 'Lucía Fernández', 'nombre_busqueda' => 'Lucia Fernandez'],
            ['nombre_completo' => 'Tomás Villarroel', 'nombre_busqueda' => 'Tomas Villarroel'],
        ];

        foreach ($personas as $persona) {
            DB::table('persona')->updateOrInsert(
                ['nombre_completo' => $persona['nombre_completo']],
                [
                    'nombre_busqueda' => $persona['nombre_busqueda'],
                    'foto' => null,
                ]
            );
        }
    }
}
