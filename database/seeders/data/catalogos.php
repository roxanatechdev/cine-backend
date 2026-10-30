<?php

/**
 * Catálogos base de CinePrime v2.
 * Columnas según cineprime_v2_schema.sql / migraciones.txt:
 *   idioma(codigo, nombre, activo) · pais(codigo, nombre)
 *   genero(nombre, descripcion, activo) · categoria_producto(nombre, descripcion, activo)
 */
return [
    'idiomas' => [
        ['codigo' => 'es',    'nombre' => 'Español',            'activo' => 1],
        ['codigo' => 'en',    'nombre' => 'Inglés',             'activo' => 1],
        ['codigo' => 'pt',    'nombre' => 'Portugués',          'activo' => 1],
        ['codigo' => 'pt-BR', 'nombre' => 'Portugués (Brasil)', 'activo' => 1],
        ['codigo' => 'ja',    'nombre' => 'Japonés',            'activo' => 1],
        ['codigo' => 'ko',    'nombre' => 'Coreano',            'activo' => 1],
        ['codigo' => 'fr',    'nombre' => 'Francés',            'activo' => 1],
        ['codigo' => 'it',    'nombre' => 'Italiano',           'activo' => 1],
        ['codigo' => 'de',    'nombre' => 'Alemán',             'activo' => 1],
        ['codigo' => 'es-MX', 'nombre' => 'Español (México)',   'activo' => 1],
        ['codigo' => 'zh',    'nombre' => 'Chino mandarín',     'activo' => 1],
    ],

    'paises' => [
        ['codigo' => 'BO', 'nombre' => 'Bolivia'],
        ['codigo' => 'US', 'nombre' => 'Estados Unidos'],
        ['codigo' => 'GB', 'nombre' => 'Reino Unido'],
        ['codigo' => 'FR', 'nombre' => 'Francia'],
        ['codigo' => 'JP', 'nombre' => 'Japón'],
        ['codigo' => 'KR', 'nombre' => 'Corea del Sur'],
        ['codigo' => 'MX', 'nombre' => 'México'],
        ['codigo' => 'AR', 'nombre' => 'Argentina'],
        ['codigo' => 'BR', 'nombre' => 'Brasil'],
        ['codigo' => 'ES', 'nombre' => 'España'],
        ['codigo' => 'CA', 'nombre' => 'Canadá'],
        ['codigo' => 'NZ', 'nombre' => 'Nueva Zelanda'],
        ['codigo' => 'DE', 'nombre' => 'Alemania'],
        ['codigo' => 'IT', 'nombre' => 'Italia'],
        ['codigo' => 'AU', 'nombre' => 'Australia'],
        ['codigo' => 'CL', 'nombre' => 'Chile'],
        ['codigo' => 'CO', 'nombre' => 'Colombia'],
        ['codigo' => 'PE', 'nombre' => 'Perú'],
        ['codigo' => 'UY', 'nombre' => 'Uruguay'],
        ['codigo' => 'CN', 'nombre' => 'China'],
        ['codigo' => 'IN', 'nombre' => 'India'],
    ],

    // Aventura y Documental quedan sin películas a propósito (filtros del panel).
    'generos' => [
        ['nombre' => 'Acción',          'descripcion' => 'Persecuciones, combates y secuencias de alto impacto.', 'activo' => 1],
        ['nombre' => 'Aventura',        'descripcion' => 'Viajes, exploración y misiones fuera de lo común.',      'activo' => 1],
        ['nombre' => 'Animación',       'descripcion' => 'Películas animadas para todo público.',                  'activo' => 1],
        ['nombre' => 'Ciencia ficción', 'descripcion' => 'Futuros posibles, tecnología y mundos alternativos.',    'activo' => 1],
        ['nombre' => 'Comedia',         'descripcion' => 'Historias con humor como protagonista.',                 'activo' => 1],
        ['nombre' => 'Drama',           'descripcion' => 'Conflictos humanos y emocionales.',                      'activo' => 1],
        ['nombre' => 'Terror',          'descripcion' => 'Suspenso, miedo y lo sobrenatural.',                     'activo' => 1],
        ['nombre' => 'Thriller',        'descripcion' => 'Tensión sostenida e intriga.',                           'activo' => 1],
        ['nombre' => 'Romance',         'descripcion' => 'Historias de amor.',                                     'activo' => 1],
        ['nombre' => 'Fantasía',        'descripcion' => 'Mundos mágicos y criaturas imaginarias.',                'activo' => 1],
        ['nombre' => 'Musical',         'descripcion' => 'La música y el baile cuentan la historia.',              'activo' => 1],
        ['nombre' => 'Documental',      'descripcion' => 'No ficción y relatos de la vida real.',                  'activo' => 1],
        ['nombre' => 'Crimen',          'descripcion' => 'Mafias, delitos e investigaciones.',                     'activo' => 1],
    ],

    'categorias' => [
        ['nombre' => 'Palomitas',            'descripcion' => 'Palomitas saladas y de caramelo.',       'activo' => 1],
        ['nombre' => 'Bebidas',              'descripcion' => 'Gaseosas, aguas, jugos y café.',         'activo' => 1],
        ['nombre' => 'Dulces y snacks',      'descripcion' => 'Confitería y snacks para compartir.',    'activo' => 1],
        ['nombre' => 'Combos',               'descripcion' => 'Paquetes de palomitas y bebidas.',       'activo' => 1],
        ['nombre' => 'Comida caliente',      'descripcion' => 'Nachos, hot dogs y platos calientes.',   'activo' => 1],
        ['nombre' => 'Helados y postres',    'descripcion' => 'Helados y postres fríos.',               'activo' => 1],
        ['nombre' => 'Temporada (inactiva)', 'descripcion' => 'Categoría desactivada para probar filtros.', 'activo' => 0],
    ],
];
