<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeliculaSeeder extends Seeder
{
    public function run(): void
    {
        $peliculas = [
            [
                'titulo' => 'Zootopia 2',
                'sinopsis' => 'Judy Hopps y Nick Wilde investigan un nuevo caso que pone a prueba su trabajo en equipo en Zootopia.',
                'genero' => 'Animación',
                'clasificacion' => 'PG',
                'duracion_minutos' => 108,
                'fecha_estreno' => '2025-11-26',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'Wicked: For Good',
                'sinopsis' => 'Elphaba y Glinda enfrentan las consecuencias de sus decisiones en la conclusión de su historia en Oz.',
                'genero' => 'Fantasía',
                'clasificacion' => 'PG',
                'duracion_minutos' => 137,
                'fecha_estreno' => '2025-11-21',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'Avatar: Fire and Ash',
                'sinopsis' => 'La familia Sully conoce nuevos pueblos de Pandora mientras afronta conflictos que amenazan su hogar.',
                'genero' => 'Ciencia ficción',
                'clasificacion' => 'PG-13',
                'duracion_minutos' => 197,
                'fecha_estreno' => '2025-12-19',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'El agente secreto',
                'sinopsis' => 'Un hombre intenta reconstruir su vida mientras se ve envuelto en una red de vigilancia y secretos.',
                'genero' => 'Suspenso',
                'clasificacion' => 'R',
                'duracion_minutos' => 158,
                'fecha_estreno' => '2025-11-06',
                'idioma' => 'pt',
            ],
            [
                'titulo' => 'Hamnet',
                'sinopsis' => 'Una familia atraviesa una pérdida profunda que transforma sus relaciones y su manera de entender el mundo.',
                'genero' => 'Drama',
                'clasificacion' => 'PG-13',
                'duracion_minutos' => 125,
                'fecha_estreno' => '2025-11-27',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'Scream 7',
                'sinopsis' => 'Una nueva amenaza obliga a los sobrevivientes a enfrentarse a otro ciclo de crímenes y sospechas.',
                'genero' => 'Terror',
                'clasificacion' => 'R',
                'duracion_minutos' => 114,
                'fecha_estreno' => '2026-02-27',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'Hoppers',
                'sinopsis' => 'Una joven utiliza una tecnología experimental para comprender a los animales y proteger su entorno.',
                'genero' => 'Animación',
                'clasificacion' => 'PG',
                'duracion_minutos' => 105,
                'fecha_estreno' => '2026-03-06',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'The Super Mario Galaxy Movie',
                'sinopsis' => 'Mario y sus amigos emprenden una aventura espacial a través de mundos extraordinarios.',
                'genero' => 'Aventura',
                'clasificacion' => 'PG',
                'duracion_minutos' => 100,
                'fecha_estreno' => '2026-04-03',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'Toy Story 5',
                'sinopsis' => 'Woody, Buzz y sus amigos deben adaptarse a una nueva etapa en la vida de su familia.',
                'genero' => 'Animación',
                'clasificacion' => 'G',
                'duracion_minutos' => 105,
                'fecha_estreno' => '2026-06-19',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'Minions 3',
                'sinopsis' => 'Los Minions se embarcan en una misión caótica que pone a prueba su lealtad y su ingenio.',
                'genero' => 'Comedia',
                'clasificacion' => 'PG',
                'duracion_minutos' => 95,
                'fecha_estreno' => '2026-07-01',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'La última función',
                'sinopsis' => 'El equipo de un cine de barrio intenta salvar su sala mientras descubre una historia olvidada.',
                'genero' => 'Drama',
                'clasificacion' => 'PG-13',
                'duracion_minutos' => 112,
                'fecha_estreno' => '2026-08-14',
                'idioma' => 'es',
            ],
            [
                'titulo' => 'Código Andino',
                'sinopsis' => 'Una ingeniera boliviana descubre una señal en los Andes y debe descifrarla antes de que desaparezca.',
                'genero' => 'Ciencia ficción',
                'clasificacion' => 'PG-13',
                'duracion_minutos' => 118,
                'fecha_estreno' => '2026-09-04',
                'idioma' => 'es',
            ],
            [
                'titulo' => 'El último boleto',
                'sinopsis' => 'Un boleto extraviado conecta a varios desconocidos durante una noche decisiva en la ciudad.',
                'genero' => 'Suspenso',
                'clasificacion' => 'PG-13',
                'duracion_minutos' => 104,
                'fecha_estreno' => '2026-10-02',
                'idioma' => 'es',
            ],
            [
                'titulo' => 'Risas de medianoche',
                'sinopsis' => 'Dos amigos abren un pequeño negocio nocturno y se enfrentan a una sucesión de situaciones absurdas.',
                'genero' => 'Comedia',
                'clasificacion' => 'PG',
                'duracion_minutos' => 98,
                'fecha_estreno' => '2026-10-16',
                'idioma' => 'es',
            ],
            [
                'titulo' => 'La casa del lago',
                'sinopsis' => 'Una familia descubre secretos inquietantes al regresar a una antigua casa junto a un lago.',
                'genero' => 'Terror',
                'clasificacion' => 'R',
                'duracion_minutos' => 109,
                'fecha_estreno' => '2026-10-23',
                'idioma' => 'es',
            ],
            [
                'titulo' => 'Horizonte de fuego',
                'sinopsis' => 'Un equipo de rescate debe atravesar una región remota para ayudar a una comunidad aislada.',
                'genero' => 'Acción',
                'clasificacion' => 'PG-13',
                'duracion_minutos' => 121,
                'fecha_estreno' => '2026-11-13',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'Navidad en La Paz',
                'sinopsis' => 'Una familia se reúne en La Paz y descubre que una celebración puede cambiar sus planes.',
                'genero' => 'Romance',
                'clasificacion' => 'PG',
                'duracion_minutos' => 101,
                'fecha_estreno' => '2026-12-04',
                'idioma' => 'es',
            ],
            [
                'titulo' => 'Planeta de cristal',
                'sinopsis' => 'Una exploradora viaja a un planeta desconocido donde las formas de vida guardan un secreto.',
                'genero' => 'Ciencia ficción',
                'clasificacion' => 'PG-13',
                'duracion_minutos' => 132,
                'fecha_estreno' => '2026-12-18',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'El reino perdido',
                'sinopsis' => 'Dos hermanos buscan una ciudad legendaria y descubren el precio de cambiar la historia.',
                'genero' => 'Aventura',
                'clasificacion' => 'PG',
                'duracion_minutos' => 116,
                'fecha_estreno' => '2027-01-15',
                'idioma' => 'es',
            ],
            [
                'titulo' => 'Frecuencia cero',
                'sinopsis' => 'Una locutora recibe mensajes imposibles de explicar durante su turno en una emisora nocturna.',
                'genero' => 'Suspenso',
                'clasificacion' => 'PG-13',
                'duracion_minutos' => 107,
                'fecha_estreno' => '2027-01-29',
                'idioma' => 'es',
            ],
            [
                'titulo' => 'El jardín de las estrellas',
                'sinopsis' => 'Una niña y su abuelo construyen un observatorio para recuperar una tradición familiar.',
                'genero' => 'Drama',
                'clasificacion' => 'G',
                'duracion_minutos' => 96,
                'fecha_estreno' => '2027-02-05',
                'idioma' => 'es',
            ],
            [
                'titulo' => 'Operación Cóndor',
                'sinopsis' => 'Un grupo de agentes debe coordinar una operación internacional contra una red criminal.',
                'genero' => 'Acción',
                'clasificacion' => 'PG-13',
                'duracion_minutos' => 124,
                'fecha_estreno' => '2027-02-12',
                'idioma' => 'es',
            ],
            [
                'titulo' => 'El bosque susurrante',
                'sinopsis' => 'Un grupo de excursionistas busca refugio en un bosque donde cada sonido parece advertirles de algo.',
                'genero' => 'Terror',
                'clasificacion' => 'PG-13',
                'duracion_minutos' => 103,
                'fecha_estreno' => '2027-02-19',
                'idioma' => 'en',
            ],
            [
                'titulo' => 'Bajo el mismo cielo',
                'sinopsis' => 'Dos músicos de países distintos encuentran una oportunidad de colaborar y cambiar sus vidas.',
                'genero' => 'Romance',
                'clasificacion' => 'PG',
                'duracion_minutos' => 110,
                'fecha_estreno' => '2027-02-26',
                'idioma' => 'es',
            ],
        ];

        foreach ($peliculas as $pelicula) {
            $genero = DB::table('genero')
                ->where('nombre', $pelicula['genero'])
                ->value('id_genero');

            if ($genero === null) {
                throw new RuntimeException(
                    "No existe el género: {$pelicula['genero']}. Ejecuta GeneroSeeder primero."
                );
            }

            $idiomaExiste = DB::table('idioma')
                ->where('codigo', $pelicula['idioma'])
                ->exists();

            if (! $idiomaExiste) {
                throw new RuntimeException(
                    "No existe el idioma: {$pelicula['idioma']}. Ejecuta IdiomaSeeder primero."
                );
            }

            DB::table('pelicula')->updateOrInsert(
                ['titulo' => $pelicula['titulo']],
                [
                    'sinopsis' => $pelicula['sinopsis'],
                    'id_genero_principal' => $genero,
                    'clasificacion' => $pelicula['clasificacion'],
                    'duracion_minutos' => $pelicula['duracion_minutos'],
                    'fecha_estreno' => $pelicula['fecha_estreno'],
                    'idioma_original_codigo' => $pelicula['idioma'],
                    'estado' => 'ACTIVA',
                ]
            );
        }
    }
}
