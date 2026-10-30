<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Datos de demostración de CinePrime v2.
 * Ejecución: php artisan db:seed --class=CinePrimeDemoSeeder
 *
 * No toca users, migrations ni tablas de Laravel/Fortify. No genera reembolsos.
 * Zona horaria: la de la app (config('app.timezone')), vía Carbon::now().
 */
class CinePrimeDemoSeeder extends Seeder
{
    // ===================== CONFIGURACIÓN =====================
    /** true: vacía las tablas de dominio y vuelve a sembrar. false: aborta si ya hay películas. */
    public const LIMPIAR_ANTES = true;
    public const SEMILLA = 2026;
    /** Total aproximado de compras. Sube este valor para tener más funciones con ventas. */
    public const NUM_COMPRAS = 600;
    public const NUM_COMPRADORES = 80;
    /** Porcentaje de compras con productos de dulcería. */
    public const PROB_DETALLE = 55;
    public const MINUTOS_RESERVA = 15;
    public const LOTE = 800;
    public const URL_VIDEO = 'https://www.youtube.com/watch?v=jNQXAC9IVRw';
    public const MONEDA = 'BOB';

    private const TABLAS_DOMINIO = [
        'reembolso', 'ticket', 'pago', 'detalle_compra', 'entrada', 'compra', 'comprador',
        'funcion_asiento', 'funcion', 'sala_pasillo', 'asiento', 'sala',
        'producto', 'categoria_producto',
        'pelicula_credito', 'persona', 'pelicula_multimedia', 'pelicula_pais', 'pelicula', 'genero',
    ];

    private const NOMBRES = ['Luis', 'María', 'Carlos', 'Ana', 'Jorge', 'Lucía', 'Diego', 'Carla', 'Marcelo', 'Valeria', 'Fernando', 'Daniela', 'Rodrigo', 'Camila', 'Andrés', 'Paola', 'Gustavo', 'Sofía', 'Pablo', 'Gabriela', 'Javier', 'Alejandra', 'Mauricio', 'Natalia', 'Hugo', 'Rosario', 'Sergio', 'Patricia', 'Ricardo', 'Verónica', 'Álvaro', 'Fabiola', 'Iván', 'Jimena', 'Óscar', 'Mónica', 'Rafael', 'Karen', 'Wilson', 'Silvia'];
    private const APELLIDOS = ['Mamani', 'Quispe', 'Condori', 'Flores', 'Rojas', 'Vargas', 'Gutiérrez', 'Choque', 'Cruz', 'Mendoza', 'Salazar', 'Torrico', 'Ayala', 'Camacho', 'Suárez', 'Paz', 'Arce', 'Villca', 'Limachi', 'Rivera', 'Herrera', 'Molina', 'Peña', 'Castro', 'Ríos', 'Soliz', 'Zambrana', 'Aguilar', 'Ortiz', 'Vega', 'Mercado', 'Guzmán', 'Terrazas', 'Orellana', 'Montaño', 'Calle', 'Tarqui', 'Espinoza', 'Ramos', 'Lara'];
    private const MOTIVOS_CANCELACION = ['Cancelada por el cliente', 'Pago rechazado por la entidad', 'Cambio de función solicitado', 'Compra duplicada'];

    private Carbon $ahora;
    private Carbon $inicioRango;
    private Carbon $finRango;
    private string $extPelicula = 'svg';
    private string $extProducto = 'svg';
    private array $mapaGenero = [];
    private array $peliculas = [];
    private array $salas = [];
    private array $productos = [];
    private array $funciones = [];
    private int $totalDias = 0;

    // ===================== EJECUCIÓN =====================
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('CinePrimeDemoSeeder no puede ejecutarse en producción.');
        }

        DB::disableQueryLog();
        mt_srand(self::SEMILLA);

        $this->ahora = Carbon::now();
        $this->inicioRango = $this->ahora->copy()->startOfMonth();
        $this->finRango = $this->ahora->copy()->startOfMonth()->addMonthNoOverflow()->endOfMonth()->startOfDay();
        for ($d = $this->inicioRango->copy(); $d <= $this->finRango; $d->addDay()) {
            $this->totalDias++;
        }

        $this->msg('CinePrime demo · zona horaria: ' . config('app.timezone') . ' · ahora: ' . $this->ahora->toDateTimeString());
        $this->msg('Rango de funciones: ' . $this->inicioRango->toDateString() . ' → ' . $this->finRango->toDateString());

        $this->preparar();
        $this->etapa('Catálogos', fn () => $this->sembrarCatalogos());
        $this->etapa('Películas, multimedia y créditos', fn () => $this->sembrarPeliculas());
        $this->etapa('Salas, asientos y pasillos', fn () => $this->sembrarSalas());
        $this->etapa('Productos', fn () => $this->sembrarProductos());
        $this->etapa('Funciones y asientos por función', fn () => $this->sembrarFunciones());
        $this->etapa('Compradores, compras y transacciones', fn () => $this->sembrarTransacciones());
        $this->etapa('Verificación', fn () => $this->verificar());
        $this->resumen();
    }

    private function msg(string $texto, string $nivel = 'info'): void
    {
        if ($this->command) {
            $nivel === 'warn' ? $this->command->warn($texto) : $this->command->info($texto);
        }
    }

    private function etapa(string $nombre, callable $fn): void
    {
        $t = microtime(true);
        $this->msg("▶ {$nombre}…");
        $fn();
        $this->msg(sprintf('  ✓ %s (%.1f s)', $nombre, microtime(true) - $t));
    }

    private function dt(Carbon $c): string
    {
        return $c->format('Y-m-d H:i:s');
    }

    private function dec(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }

    private function insertar(string $tabla, array $filas): void
    {
        foreach (array_chunk($filas, self::LOTE) as $lote) {
            DB::table($tabla)->insert($lote);
        }
    }

    private function siguienteId(string $tabla, string $pk): int
    {
        return ((int) DB::table($tabla)->max($pk)) + 1;
    }

    // ===================== PREPARACIÓN =====================
    private function preparar(): void
    {
        if (self::LIMPIAR_ANTES) {
            $this->msg('⚠ LIMPIAR_ANTES=true: se BORRAN los datos de dominio actuales (users y migrations no se tocan).', 'warn');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach (self::TABLAS_DOMINIO as $tabla) {
                    DB::table($tabla)->truncate(); // reinicia AUTO_INCREMENT
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        } elseif (DB::table('pelicula')->exists()) {
            throw new RuntimeException('Ya existen películas y LIMPIAR_ANTES=false. Abortando para no duplicar datos.');
        }

        $this->extPelicula = $this->prepararPlaceholder('peliculas', 400, 600);
        $this->extProducto = $this->prepararPlaceholder('productos', 400, 400);
    }

    /** Devuelve la extensión de storage/app/public/<carpeta>/placeholder.*; crea un SVG si no existe. */
    private function prepararPlaceholder(string $carpeta, int $ancho, int $alto): string
    {
        $dir = storage_path('app/public/' . $carpeta);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'] as $ext) {
            if (is_file("{$dir}/placeholder.{$ext}")) {
                return $ext;
            }
        }
        $cx = $ancho / 2;
        $cy = $alto / 2;
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$ancho}" height="{$alto}" viewBox="0 0 {$ancho} {$alto}" role="img" aria-label="Imagen no disponible">
  <rect width="{$ancho}" height="{$alto}" fill="#11131c"/>
  <g transform="translate({$cx} {$cy}) translate(-80 -70)" fill="none" stroke="#e50914" stroke-width="10" stroke-linejoin="round" stroke-linecap="round">
    <rect x="0" y="50" width="160" height="110" rx="8"/>
    <path d="M0 50 L22 0 H62 L40 50 M62 50 L84 0 H124 L102 50 M124 50 L146 0 H160 V50"/>
  </g>
</svg>
SVG;
        file_put_contents("{$dir}/placeholder.svg", $svg);

        return 'svg';
    }

    // ===================== CATÁLOGOS =====================
    private function sembrarCatalogos(): void
    {
        $cat = require __DIR__ . '/data/catalogos.php';

        DB::table('idioma')->upsert($cat['idiomas'], ['codigo'], ['nombre', 'activo']);
        DB::table('pais')->upsert($cat['paises'], ['codigo'], ['nombre']);
        $this->insertar('genero', $cat['generos']);
        $this->insertar('categoria_producto', $cat['categorias']);

        $this->mapaGenero = DB::table('genero')->pluck('id_genero', 'nombre')->all();
    }

    // ===================== PELÍCULAS =====================
    private function sembrarPeliculas(): void
    {
        $datos = require __DIR__ . '/data/peliculas.php';
        $baseId = $this->siguienteId('pelicula', 'id_pelicula');
        $basePersona = $this->siguienteId('persona', 'id_persona');
        $img = '/storage/peliculas/placeholder.' . $this->extPelicula;

        $filas = $paises = $multimedia = $filasPersona = $creditos = [];
        $personas = [];
        $claves = [];

        $idPersona = function (string $nombre) use (&$personas, &$filasPersona, $basePersona, $img): int {
            if (!isset($personas[$nombre])) {
                $id = $basePersona + count($personas);
                $personas[$nombre] = $id;
                $filasPersona[] = [
                    'id_persona' => $id,
                    'nombre_completo' => $nombre,
                    'nombre_busqueda' => Str::lower(Str::ascii($nombre)),
                    'foto' => $img,
                ];
            }

            return $personas[$nombre];
        };

        foreach ($datos as $i => $p) {
            $id = $baseId + $i;
            $fecha = isset($p['estreno_dias'])
                ? $this->ahora->copy()->addDays($p['estreno_dias'])->toDateString()
                : $p['estreno'];

            $filas[] = [
                'id_pelicula' => $id,
                'titulo' => $p['titulo'],
                'sinopsis' => $p['sinopsis'],
                'id_genero_principal' => $this->mapaGenero[$p['genero']] ?? throw new RuntimeException("Género inexistente: {$p['genero']}"),
                'clasificacion' => $p['clasificacion'],
                'duracion_minutos' => $p['duracion'],
                'fecha_estreno' => $fecha,
                'idioma_original_codigo' => $p['idioma'],
                'estado' => $p['estado'],
            ];

            $this->peliculas[$id] = [
                'id' => $id, 'titulo' => $p['titulo'], 'duracion' => $p['duracion'], 'tipo' => $p['tipo'],
                'popularidad' => $p['popularidad'], 'tiene3d' => $p['tiene3d'],
            ];

            foreach ($p['paises'] as $pais) {
                $paises[] = ['id_pelicula' => $id, 'codigo_pais' => $pais];
            }

            $titulo = $p['titulo'];
            foreach ([
                ['POSTER', $img, "Póster de {$titulo}", 1],
                ['TRAILER', self::URL_VIDEO, "Tráiler de {$titulo}", 1],
                ['IMAGEN', $img, "Imagen promocional de {$titulo}", 0],
                ['VIDEO', self::URL_VIDEO, "Video de {$titulo}", 0],
            ] as $orden => [$tipo, $url, $alt, $principal]) {
                $multimedia[] = [
                    'id_pelicula' => $id, 'tipo' => $tipo, 'url' => $url, 'texto_alternativo' => $alt,
                    'es_principal' => $principal, 'orden' => $orden + 1, 'activo' => 1,
                ];
            }

            $agregar = function (string $rol, string $nombre, ?string $personaje, int $orden) use (&$creditos, &$claves, $id, $idPersona): void {
                $idP = $idPersona($nombre);
                $clave = "{$id}|{$idP}|{$rol}";
                if (isset($claves[$clave])) {
                    return;
                }
                $claves[$clave] = true;
                $creditos[] = ['id_pelicula' => $id, 'id_persona' => $idP, 'rol' => $rol, 'personaje' => $personaje, 'orden' => $orden];
            };

            foreach ($p['directores'] as $k => $n) { $agregar('DIRECTOR', $n, null, $k + 1); }
            foreach ($p['actores'] as $k => [$n, $personaje]) { $agregar('ACTOR', $n, $personaje, $k + 1); }
            foreach ($p['guionistas'] as $k => $n) { $agregar('GUIONISTA', $n, null, $k + 1); }
            foreach ($p['productores'] as $k => $n) { $agregar('PRODUCTOR', $n, null, $k + 1); }
            foreach ($p['compositores'] as $k => $n) { $agregar('COMPOSITOR', $n, null, $k + 1); }
        }

        $this->insertar('pelicula', $filas);
        $this->insertar('pelicula_pais', $paises);
        $this->insertar('pelicula_multimedia', $multimedia);
        $this->insertar('persona', $filasPersona);
        $this->insertar('pelicula_credito', $creditos);
    }

    // ===================== SALAS =====================
    private function sembrarSalas(): void
    {
        $defs = [
            ['nombre' => 'Sala 1', 'estado' => 'ACTIVA', 'margen' => 20, 'filas' => 8, 'cols' => 12, 'trans' => [3]],
            ['nombre' => 'Sala 2', 'estado' => 'ACTIVA', 'margen' => 30, 'filas' => 10, 'cols' => 14, 'trans' => [2, 6]],
            ['nombre' => 'Sala 3D', 'estado' => 'ACTIVA', 'margen' => 45, 'filas' => 10, 'cols' => 12, 'trans' => [4], 'es3d' => true],
            ['nombre' => 'Sala VIP', 'estado' => 'ACTIVA', 'margen' => 30, 'filas' => 5, 'cols' => 12, 'trans' => [1], 'vip' => true],
            // Capacidad informada (90) distinta a propósito de los 80 asientos reales: prueba el aviso.
            ['nombre' => 'Sala 5 (Mantenimiento)', 'estado' => 'MANTENIMIENTO', 'margen' => 30, 'filas' => 8, 'cols' => 10, 'trans' => [3], 'capacidad' => 90],
            ['nombre' => 'Sala 6 (Nueva)', 'estado' => 'ACTIVA', 'margen' => 30, 'filas' => 0, 'cols' => 0, 'capacidad' => 96],
            ['nombre' => 'Sala 7 (En obras)', 'estado' => 'INACTIVA', 'margen' => 30, 'filas' => 0, 'cols' => 0, 'capacidad' => null],
        ];

        $idAsiento = $this->siguienteId('asiento', 'id_asiento');
        $filasAsiento = $pasillos = [];
        $letras = range('A', 'Z');
        $ts = $this->dt($this->ahora);

        foreach ($defs as $d) {
            $nFilas = $d['filas'];
            $cols = $d['cols'];
            $vip = $d['vip'] ?? false;
            $capacidad = array_key_exists('capacidad', $d) ? $d['capacidad'] : $nFilas * $cols;

            $idSala = (int) DB::table('sala')->insertGetId([
                'nombre' => $d['nombre'],
                'capacidad' => $capacidad,
                'margen_limpieza_minutos' => $d['margen'],
                'estado' => $d['estado'],
            ], 'id_sala');

            $activos = [];
            for ($f = 0; $f < $nFilas; $f++) {
                for ($c = 1; $c <= $cols; $c++) {
                    if ($f < 2 && ($c === 1 || $c === $cols)) {
                        $tipo = 'ACCESIBLE';
                    } elseif ($vip) {
                        $tipo = 'PREFERENCIAL';
                    } elseif ($f >= intdiv($nFilas, 3) && $f < $nFilas - intdiv($nFilas, 3) && $c >= 3 && $c <= $cols - 2) {
                        $tipo = 'PREFERENCIAL';
                    } else {
                        $tipo = 'ESTANDAR';
                    }
                    $activo = ($tipo !== 'ACCESIBLE' && mt_rand(1, 1000) <= 15) ? 0 : 1;

                    $filasAsiento[] = [
                        'id_asiento' => $idAsiento, 'id_sala' => $idSala, 'fila' => $letras[$f],
                        'numero' => $c, 'tipo' => $tipo, 'activo' => $activo,
                    ];
                    if ($activo === 1) {
                        $activos[] = ['id' => $idAsiento, 'fila' => $letras[$f], 'numero' => $c, 'tipo' => $tipo];
                    }
                    $idAsiento++;
                }

                if ($cols > 0) {
                    foreach ([(int) ceil($cols / 3), (int) ceil(2 * $cols / 3)] as $despues) {
                        $pasillos[] = [
                            'id_sala' => $idSala, 'fila' => $letras[$f], 'despues_de_numero' => $despues,
                            'orientacion' => 'LONGITUDINAL', 'fila_siguiente' => null,
                            'created_at' => $ts, 'updated_at' => $ts,
                        ];
                    }
                }
            }
            foreach ($d['trans'] ?? [] as $k) {
                $pasillos[] = [
                    'id_sala' => $idSala, 'fila' => $letras[$k], 'despues_de_numero' => null,
                    'orientacion' => 'TRANSVERSAL', 'fila_siguiente' => $letras[$k + 1],
                    'created_at' => $ts, 'updated_at' => $ts,
                ];
            }

            $this->salas[$idSala] = [
                'nombre' => $d['nombre'], 'estado' => $d['estado'], 'margen' => $d['margen'],
                'es3d' => $d['es3d'] ?? false, 'vip' => $vip, 'activos' => $activos,
            ];
        }

        $this->insertar('asiento', $filasAsiento);
        $this->insertar('sala_pasillo', $pasillos);
    }

    // ===================== PRODUCTOS =====================
    private function sembrarProductos(): void
    {
        $datos = require __DIR__ . '/data/productos.php';
        $cats = DB::table('categoria_producto')->pluck('id_categoria_producto', 'nombre')->all();
        $base = $this->siguienteId('producto', 'id_producto');
        $img = '/storage/productos/placeholder.' . $this->extProducto;
        $filas = [];

        foreach ($datos as $i => $p) {
            $id = $base + $i;
            $esPromo = $p['promo'] !== null;
            $ini = $fin = null;
            if ($esPromo) {
                $ini = $this->ahora->copy()->addDays($p['promo'][1])->startOfDay();
                $fin = $this->ahora->copy()->addDays($p['promo'][2])->setTime(23, 59, 59);
            }

            $filas[] = [
                'id_producto' => $id,
                'id_categoria_producto' => $cats[$p['categoria']] ?? throw new RuntimeException("Categoría inexistente: {$p['categoria']}"),
                'nombre' => $p['nombre'], 'descripcion' => $p['descripcion'],
                'precio_actual' => number_format($p['precio'], 2, '.', ''),
                'imagen' => $img, 'stock' => $p['stock'], 'estado' => $p['estado'],
                'tamano' => $p['tamano'], 'tipo_variante' => $p['tipo_variante'],
                'contenido_ml' => $p['contenido_ml'], 'peso_gramos' => $p['peso_gramos'],
                'es_promocion' => $esPromo ? 1 : 0,
                'precio_promocional' => $esPromo ? number_format($p['promo'][0], 2, '.', '') : null,
                'promocion_inicio' => $ini ? $this->dt($ini) : null,
                'promocion_fin' => $fin ? $this->dt($fin) : null,
            ];

            $this->productos[$id] = [
                'id' => $id, 'estado' => $p['estado'], 'centavos' => (int) round($p['precio'] * 100),
                'promo_centavos' => $esPromo ? (int) round($p['promo'][0] * 100) : null,
                'promo_ini' => $ini, 'promo_fin' => $fin,
            ];
        }

        $this->insertar('producto', $filas);
    }

    private function centavosProducto(array $p, Carbon $momento): int
    {
        if ($p['promo_centavos'] !== null && $p['promo_ini'] <= $momento && $momento <= $p['promo_fin']) {
            return $p['promo_centavos'];
        }

        return $p['centavos'];
    }

    // ===================== FUNCIONES =====================
    private function precioAsiento(float $base, string $tipo): float
    {
        return $tipo === 'PREFERENCIAL' ? round($base * 1.25, 2) : round($base, 2);
    }

    private function elegirPelicula(array $pool, array $conteoBloque): array
    {
        $deficit = array_values(array_filter($pool, fn ($p) => ($conteoBloque[$p['id']] ?? 0) < 3));
        if ($deficit && mt_rand(1, 100) <= 90) {
            return $deficit[mt_rand(0, count($deficit) - 1)];
        }
        $total = 0;
        foreach ($pool as $p) {
            $total += 2 * $p['popularidad'] - 1;
        }
        $r = mt_rand(1, $total);
        foreach ($pool as $p) {
            $r -= 2 * $p['popularidad'] - 1;
            if ($r <= 0) {
                return $p;
            }
        }

        return end($pool);
    }

    private function sembrarFunciones(): void
    {
        $cartelera = array_values(array_filter($this->peliculas, fn ($p) => $p['tipo'] === 'cartelera'));
        $salasProg = array_filter($this->salas, fn ($s) => $s['estado'] === 'ACTIVA' && count($s['activos']) > 0);
        $bloques = max(1, intdiv($this->totalDias, 7)); // el último bloque absorbe los días sobrantes

        $conteo = [];
        $prog = [];
        $dia = $this->inicioRango->copy();
        $idxDia = 0;

        while ($dia <= $this->finRango) {
            $bloque = min(intdiv($idxDia, 7), $bloques - 1);
            $finde = $dia->isWeekend();

            foreach ($salasProg as $idSala => $sala) {
                $n = $finde ? mt_rand(4, 5) : (mt_rand(1, 10) <= 2 ? 5 : mt_rand(3, 4));
                $minuto = 12 * 60 + 5 * mt_rand(0, 8);
                $previa = null;

                for ($s = 0; $s < $n; $s++) {
                    if ($minuto > 22 * 60 + 30) {
                        break;
                    }
                    $pool = array_values(array_filter(
                        $cartelera,
                        fn ($p) => $p['id'] !== $previa && $minuto + $p['duracion'] <= 25 * 60
                    ));
                    if (!$pool) {
                        break;
                    }
                    $formato = '2D';
                    if ($sala['es3d'] && mt_rand(1, 100) <= 55) {
                        $p3 = array_values(array_filter($pool, fn ($p) => $p['tiene3d']));
                        if ($p3) {
                            $pool = $p3;
                            $formato = '3D';
                        }
                    }
                    $peli = $this->elegirPelicula($pool, $conteo[$bloque] ?? []);

                    $inicio = $dia->copy()->startOfDay()->addMinutes($minuto);
                    $fin = $inicio->copy()->addMinutes($peli['duracion']);

                    if ($formato === '3D') {
                        $base = mt_rand(42, 55);
                    } elseif ($sala['vip']) {
                        $base = mt_rand(36, 40);
                    } else {
                        $base = mt_rand(30, 40);
                    }
                    if ($minuto < 15 * 60 || $dia->dayOfWeek === Carbon::WEDNESDAY) {
                        $base *= (mt_rand(0, 1) ? 0.85 : 0.80);
                    }

                    $creado = $inicio->copy()->subDays(mt_rand(3, 10))->subMinutes(mt_rand(0, 600));
                    if ($creado > $this->ahora) {
                        $creado = $this->ahora->copy()->subHours(mt_rand(1, 48));
                    }

                    if ($fin <= $this->ahora) {
                        $estado = 'FINALIZADA';
                    } elseif ($inicio <= $this->ahora) {
                        $estado = 'EN_CURSO';
                    } else {
                        $estado = 'PROGRAMADA';
                    }

                    $prog[] = [
                        'pelicula' => $peli['id'], 'sala' => $idSala, 'inicio' => $inicio, 'fin' => $fin,
                        'formato' => $formato, 'base' => round($base, 2), 'estado' => $estado,
                        'creado' => $creado, 'bloque' => $bloque,
                    ];
                    $conteo[$bloque][$peli['id']] = ($conteo[$bloque][$peli['id']] ?? 0) + 1;
                    $previa = $peli['id'];

                    $gap = 5 * mt_rand(0, $finde ? 2 : 3);
                    $minuto = intdiv($minuto + $peli['duracion'] + $sala['margen'] + $gap + 4, 5) * 5;
                }
            }

            $dia->addDay();
            $idxDia++;
        }

        // 3 a 5 funciones futuras canceladas (sin compras: se excluyen luego).
        $futuras = [];
        foreach ($prog as $i => $f) {
            if ($f['inicio'] > $this->ahora->copy()->addDay()) {
                $futuras[] = $i;
            }
        }
        shuffle($futuras);
        foreach (array_slice($futuras, 0, mt_rand(3, 5)) as $i) {
            $prog[$i]['estado'] = 'CANCELADA';
        }

        // Inserción de funciones y de su inventario de asientos.
        $idFuncion = $this->siguienteId('funcion', 'id_funcion');
        $idFa = $this->siguienteId('funcion_asiento', 'id_funcion_asiento');
        $filasFuncion = [];
        $bufferFa = [];

        foreach ($prog as $f) {
            $activos = $this->salas[$f['sala']]['activos'];
            $m = count($activos);

            $filasFuncion[] = [
                'id_funcion' => $idFuncion, 'id_pelicula' => $f['pelicula'], 'id_sala' => $f['sala'],
                'inicio' => $this->dt($f['inicio']), 'formato' => $f['formato'],
                'precio_base' => number_format($f['base'], 2, '.', ''), 'estado' => $f['estado'],
                'creado_en' => $this->dt($f['creado']),
            ];

            foreach ($activos as $k => $a) {
                $bufferFa[] = [
                    'id_funcion_asiento' => $idFa + $k, 'id_funcion' => $idFuncion, 'id_asiento' => $a['id'],
                    'precio' => number_format($this->precioAsiento($f['base'], $a['tipo']), 2, '.', ''),
                ];
            }

            $this->funciones[] = $f + ['id' => $idFuncion, 'fa0' => $idFa, 'm' => $m];
            $idFuncion++;
            $idFa += $m;
        }

        // Las funciones deben existir antes que su inventario (FK).
        $this->insertar('funcion', $filasFuncion);
        $this->insertar('funcion_asiento', $bufferFa);
    }

    // ===================== COMPRAS Y TRANSACCIONES =====================
    private function sembrarTransacciones(): void
    {
        if (!$this->funciones) {
            throw new RuntimeException('No hay funciones; no se pueden generar compras.');
        }
        $plan = $this->planificarOcupacion();
        $compras = $this->construirCompras($plan, $primerActivo);
        $this->agregarCompras($compras, $plan, $primerActivo);
        $this->escribir($compras);
    }

    private function tamanoCompra(): int
    {
        $r = mt_rand(1, 100);

        return match (true) {
            $r <= 10 => 1, $r <= 40 => 2, $r <= 60 => 3, $r <= 80 => 4, $r <= 90 => 5, default => 6,
        };
    }

    private function tamanos(int $meta, bool $grandes): array
    {
        $out = [];
        $resto = $meta;
        while ($resto > 0) {
            $k = min($grandes ? mt_rand(4, 6) : $this->tamanoCompra(), $resto);
            $out[] = $k;
            $resto -= $k;
        }

        return $out;
    }

    /** Decide qué funciones tendrán ventas y cuántos asientos se ocupan en cada una. */
    private function planificarOcupacion(): array
    {
        $ahora = $this->ahora;
        $vip = $s1 = null;
        foreach ($this->salas as $id => $s) {
            if ($s['nombre'] === 'Sala VIP') { $vip = $id; }
            if ($s['nombre'] === 'Sala 1') { $s1 = $id; }
        }

        $umbralFut = $ahora->copy()->addHours(3);
        $maxFut = $ahora->copy()->addDays(14);
        $umbralPas = $ahora->copy()->subHours(3);
        $minPas = $ahora->copy()->subDays(14);

        $cands = function (?int $sala, string $cuando) use ($umbralFut, $maxFut, $umbralPas, $minPas): array {
            $out = [];
            foreach ($this->funciones as $i => $f) {
                if ($f['sala'] !== $sala || $f['estado'] === 'CANCELADA' || $f['m'] < 10) {
                    continue;
                }
                if ($cuando === 'fut' && $f['inicio'] > $umbralFut && $f['inicio'] < $maxFut) { $out[] = $i; }
                if ($cuando === 'pas' && $f['fin'] < $umbralPas && $f['inicio'] > $minPas) { $out[] = $i; }
            }
            shuffle($out);

            return $out;
        };

        $plan = [];
        $tomar = function (array $lista, int $n, float $frac) use (&$plan): void {
            foreach (array_slice($lista, 0, $n) as $fi) {
                $m = $this->funciones[$fi]['m'];
                $meta = $frac >= 1.0 ? $m : (int) ceil($m * $frac);
                $plan[$fi] = ['especial' => true, 'tamanos' => $this->tamanos($meta, true)];
            }
        };
        // 2 funciones llenas (VIP, futuras) y 4 casi llenas ≥ 95 % (Sala 1: 2 futuras y 2 pasadas).
        $tomar($cands($vip, 'fut'), 2, 1.0);
        $tomar($cands($s1, 'fut'), 2, 0.96);
        $tomar($cands($s1, 'pas'), 2, 0.96);

        $porPeli = [];
        $limite = $ahora->copy()->addMinutes(30);
        foreach ($this->funciones as $i => $f) {
            if (isset($plan[$i]) || $f['estado'] === 'CANCELADA' || $f['m'] < 10) {
                continue;
            }
            if ($f['inicio'] <= $ahora) {
                $porPeli[$f['pelicula']]['p'][] = $i;
            } elseif ($f['inicio'] > $limite) {
                $porPeli[$f['pelicula']]['f'][] = $i;
            }
        }
        foreach ($porPeli as &$listas) {
            foreach ($listas as &$l) { shuffle($l); }
            unset($l);
        }
        unset($listas);
        $ids = array_keys($porPeli);
        shuffle($ids);

        $objetivo = (int) round(self::NUM_COMPRAS * 0.82);
        $activas = 0;
        foreach ($plan as $pl) { $activas += count($pl['tamanos']); }

        for ($ronda = 0; $ronda < 200 && $activas < $objetivo; $ronda++) {
            $avance = false;
            foreach ($ids as $pid) {
                if ($activas >= $objetivo) { break; }
                $pref = mt_rand(1, 100) <= 45 ? 'p' : 'f';
                $otro = $pref === 'p' ? 'f' : 'p';
                $lista = !empty($porPeli[$pid][$pref]) ? $pref : (!empty($porPeli[$pid][$otro]) ? $otro : null);
                if ($lista === null) { continue; }
                $fi = array_pop($porPeli[$pid][$lista]);
                $m = $this->funciones[$fi]['m'];
                $frac = $lista === 'p' ? mt_rand(30, 85) / 100 : mt_rand(5, 40) / 100;
                $meta = max(1, (int) round($m * $frac));
                $plan[$fi] = ['especial' => false, 'tamanos' => $this->tamanos($meta, false)];
                $activas += count($plan[$fi]['tamanos']);
                $avance = true;
            }
            if (!$avance) { break; }
        }

        return $plan;
    }

    private function elegirAsientos(array &$libre, int &$nLibres, array $filas, int $k): array
    {
        $k = min($k, $nLibres);
        if ($k <= 0) {
            return [];
        }
        $m = count($libre);
        $elegidos = [];
        for ($intento = 0; $intento < 20 && !$elegidos; $intento++) {
            $ini = mt_rand(0, $m - 1);
            if (!$libre[$ini]) { continue; }
            $blk = [$ini];
            for ($p = $ini + 1; count($blk) < $k && $p < $m && $libre[$p] && $filas[$p] === $filas[$ini]; $p++) { $blk[] = $p; }
            for ($p = $ini - 1; count($blk) < $k && $p >= 0 && $libre[$p] && $filas[$p] === $filas[$ini]; $p--) { $blk[] = $p; }
            if (count($blk) === $k) { $elegidos = $blk; }
        }
        if (!$elegidos) {
            $frees = array_keys(array_filter($libre));
            shuffle($frees);
            $elegidos = array_slice($frees, 0, $k);
        }
        foreach ($elegidos as $p) {
            $libre[$p] = false;
            $nLibres--;
        }
        sort($elegidos);

        return $elegidos;
    }

    private function instanteCompra(array $f): Carbon
    {
        $lower = max($f['creado']->timestamp, $this->ahora->timestamp - 30 * 86400);
        $upper = min($f['inicio']->timestamp - 900, $this->ahora->timestamp - 300);
        if ($upper <= $lower) {
            $lower = $upper - 3600;
        }
        $u = sqrt(mt_rand() / mt_getrandmax());

        return $this->ahora->copy()->setTimestamp((int) ($lower + ($upper - $lower) * $u));
    }

    /** Crea las compras "activas" (CONFIRMADA / PENDIENTE_PAGO / INICIADA). */
    private function construirCompras(array $plan, ?array &$primerActivo): array
    {
        $primerActivo = [];
        $slots = [];
        $limite = $this->ahora->copy()->addMinutes(30);
        foreach ($plan as $fi => $pl) {
            foreach ($pl['tamanos'] as $k) {
                $slots[] = ['fi' => $fi, 'k' => $k, 'estado' => 'CONFIRMADA', 'esp' => $pl['especial']];
            }
        }

        $nTot = (int) round(count($slots) / 0.82);
        $nPend = (int) round(0.08 * $nTot);
        $nIni = (int) round(0.04 * $nTot);
        $elegibles = [];
        foreach ($slots as $i => $s) {
            if (!$s['esp'] && $this->funciones[$s['fi']]['inicio'] > $limite) { $elegibles[] = $i; }
        }
        shuffle($elegibles);
        $nPend = min($nPend, (int) floor(count($elegibles) * 0.4));
        $nIni = min($nIni, (int) floor(count($elegibles) * 0.2));
        foreach (array_slice($elegibles, 0, $nPend) as $i) { $slots[$i]['estado'] = 'PENDIENTE_PAGO'; }
        foreach (array_slice($elegibles, $nPend, $nIni) as $i) { $slots[$i]['estado'] = 'INICIADA'; }

        $porFuncion = [];
        foreach ($slots as $i => $s) { $porFuncion[$s['fi']][] = $i; }

        $compras = [];
        foreach ($porFuncion as $fi => $indices) {
            $f = $this->funciones[$fi];
            $libre = array_fill(0, $f['m'], true);
            $nLibres = $f['m'];
            $filas = array_column($this->salas[$f['sala']]['activos'], 'fila');

            foreach ($indices as $si) {
                $slot = $slots[$si];
                $pos = $this->elegirAsientos($libre, $nLibres, $filas, $slot['k']);
                if (!$pos) { continue; }

                $estado = $slot['estado'];
                if ($estado === 'CONFIRMADA') {
                    $creada = $this->instanteCompra($f);
                    $vence = $creada->copy()->addMinutes(self::MINUTOS_RESERVA);
                } else {
                    $vence = $this->ahora->copy()->addSeconds(mt_rand(120, 840));
                    $creada = $vence->copy()->subMinutes(self::MINUTOS_RESERVA);
                }
                $aprobado = $estado === 'CONFIRMADA' ? $creada->copy()->addSeconds(mt_rand(20, 180)) : null;

                $compras[] = [
                    'estado' => $estado, 'fi' => $fi, 'creada' => $creada, 'vence' => $vence,
                    'cancelada' => null, 'motivo' => null, 'pos' => $pos, 'aprobado' => $aprobado,
                    'usada' => $estado === 'CONFIRMADA' && $f['inicio'] <= $this->ahora && mt_rand(1, 100) > 8,
                    'metodo' => mt_rand(0, 1) ? 'QR' : 'TARJETA', 'rechazado' => false,
                ];
                foreach ($pos as $p) {
                    $primerActivo[$fi][$p] = $creada->timestamp;
                }
            }
        }

        return $compras;
    }

    /** Añade compras CANCELADA / EXPIRADA en asientos libres en ese instante (historial de reintentos). */
    private function agregarCompras(array &$compras, array $plan, array $primerActivo): void
    {
        $activas = count($compras);
        $nTot = (int) round($activas / 0.82);
        $tipos = array_merge(
            array_fill(0, (int) round(0.08 * $nTot), 'CANCELADA'),
            array_fill(0, (int) round(0.10 * $nTot), 'EXPIRADA')
        );
        shuffle($tipos);
        $usadas = array_keys($plan);
        if (!$usadas) { return; }

        foreach ($tipos as $tipo) {
            for ($intento = 0; $intento < 15; $intento++) {
                $fi = $usadas[mt_rand(0, count($usadas) - 1)];
                $f = $this->funciones[$fi];
                $lower = max($f['creado']->timestamp, $this->ahora->timestamp - 28 * 86400);
                $upper = min($f['inicio']->timestamp - 1800, $this->ahora->timestamp - 1200);
                if ($upper - $lower < 3600) { continue; }

                $ts = mt_rand($lower, $upper);
                $dur = $tipo === 'EXPIRADA' ? self::MINUTOS_RESERVA * 60 : mt_rand(120, 720);
                $cierre = $ts + $dur;
                $k = mt_rand(1, 4);

                $vendidosDespues = $libres = [];
                for ($p = 0; $p < $f['m']; $p++) {
                    $pa = $primerActivo[$fi][$p] ?? null;
                    if ($pa === null) { $libres[] = $p; }
                    elseif ($pa > $cierre + 60) { $vendidosDespues[] = $p; }
                }
                if (count($vendidosDespues) + count($libres) < $k) { continue; }
                shuffle($vendidosDespues);
                shuffle($libres);
                $pool = mt_rand(1, 100) <= 50 ? array_merge($vendidosDespues, $libres) : array_merge($libres, $vendidosDespues);
                $pos = array_slice($pool, 0, $k);
                sort($pos);

                $creada = $this->ahora->copy()->setTimestamp($ts);
                $compras[] = [
                    'estado' => $tipo, 'fi' => $fi, 'creada' => $creada,
                    'vence' => $creada->copy()->addMinutes(self::MINUTOS_RESERVA),
                    'cancelada' => $tipo === 'CANCELADA' ? $this->ahora->copy()->setTimestamp($cierre) : null,
                    'motivo' => $tipo === 'CANCELADA' ? self::MOTIVOS_CANCELACION[mt_rand(0, 3)] : null,
                    'pos' => $pos, 'aprobado' => null, 'usada' => false,
                    'metodo' => mt_rand(0, 1) ? 'QR' : 'TARJETA',
                    'rechazado' => $tipo === 'CANCELADA' && mt_rand(1, 100) <= 60,
                ];
                break;
            }
        }
    }

    private function escribir(array $compras): void
    {
        usort($compras, fn ($a, $b) => $a['creada']->timestamp <=> $b['creada']->timestamp);
        $n = count($compras);

        // Compradores (en memoria): cada uno con al menos una compra si hay suficientes.
        $orden = range(0, max(0, $n - 1));
        shuffle($orden);
        $asignado = [];
        foreach ($orden as $pos => $ci) {
            $asignado[$ci] = $pos < self::NUM_COMPRADORES ? $pos : mt_rand(0, self::NUM_COMPRADORES - 1);
        }
        $primera = [];
        foreach ($compras as $ci => $c) {
            $b = $asignado[$ci];
            $primera[$b] = isset($primera[$b]) ? min($primera[$b], $c['creada']->timestamp) : $c['creada']->timestamp;
        }

        $baseComprador = $this->siguienteId('comprador', 'id_comprador');
        $filasComprador = [];
        for ($b = 0; $b < self::NUM_COMPRADORES; $b++) {
            $nombre = self::NOMBRES[mt_rand(0, count(self::NOMBRES) - 1)];
            $ap = self::APELLIDOS[mt_rand(0, count(self::APELLIDOS) - 1)];
            $ap2 = self::APELLIDOS[mt_rand(0, count(self::APELLIDOS) - 1)];
            $correo = Str::lower(Str::ascii("{$nombre}.{$ap}")) . ($b + 1) . '@' . (mt_rand(0, 1) ? 'example.com' : 'example.org');
            $r = mt_rand(1, 100);
            $tel = $r <= 12 ? null : ($r <= 70 ? '+591 7' . sprintf('%07d', mt_rand(0, 9999999)) : '6' . sprintf('%07d', mt_rand(0, 9999999)));
            $ref = $primera[$b] ?? $this->ahora->timestamp;
            $filasComprador[] = [
                'id_comprador' => $baseComprador + $b, 'nombre' => "{$nombre} {$ap} {$ap2}", 'correo' => $correo,
                'telefono' => $tel, 'tipo_comprador' => mt_rand(1, 100) <= 55 ? 'REGISTRADO' : 'INVITADO',
                'creado_en' => $this->dt($this->ahora->copy()->setTimestamp($ref - mt_rand(5, 20 * 24 * 60) * 60)),
            ];
        }
        $this->insertar('comprador', $filasComprador);

        $prodActivos = array_values(array_filter($this->productos, fn ($p) => $p['estado'] === 'ACTIVO'));
        $baseCompra = $this->siguienteId('compra', 'id_compra');
        $fCompra = $fEntrada = $fPago = $fTicket = $fDetalle = [];
        $codigos = [];
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        foreach ($compras as $ci => $c) {
            $idC = $baseCompra + $ci;
            $f = $this->funciones[$c['fi']];
            $sala = $this->salas[$f['sala']];
            $estado = $c['estado'];
            $muerta = in_array($estado, ['CANCELADA', 'EXPIRADA'], true);

            // Entradas
            $centEntradas = 0;
            $utilizadas = [];
            foreach ($c['pos'] as $p) {
                $asiento = $sala['activos'][$p];
                $cent = (int) round($this->precioAsiento($f['base'], $asiento['tipo']) * 100);
                $centEntradas += $cent;

                $confirmada = $utilizada = $cerrada = null;
                if ($estado === 'CONFIRMADA') {
                    $confirmada = $c['aprobado'];
                    if ($c['usada']) {
                        $utilizada = $f['inicio']->copy()->subMinutes(mt_rand(10, 20));
                        $utilizadas[] = $utilizada;
                    }
                    $est = $c['usada'] ? 'UTILIZADA' : 'CONFIRMADA';
                } elseif ($estado === 'CANCELADA') {
                    $est = 'CANCELADA';
                    $cerrada = $c['cancelada'];
                } elseif ($estado === 'EXPIRADA') {
                    $est = 'EXPIRADA';
                    $cerrada = $c['vence'];
                } else {
                    $est = 'RESERVADA';
                }

                $fEntrada[] = [
                    'id_compra' => $idC, 'id_funcion_asiento' => $f['fa0'] + $p, 'precio_venta' => $this->dec($cent),
                    'estado' => $est, 'reservada_en' => $this->dt($c['creada']),
                    'confirmada_en' => $confirmada ? $this->dt($confirmada) : null,
                    'utilizada_en' => $utilizada ? $this->dt($utilizada) : null,
                    'cerrada_en' => $cerrada ? $this->dt($cerrada) : null,
                ];
            }

            // Detalle de dulcería
            $centDetalle = 0;
            if ($prodActivos && mt_rand(1, 100) <= self::PROB_DETALLE) {
                $ix = array_keys($prodActivos);
                shuffle($ix);
                foreach (array_slice($ix, 0, mt_rand(1, 4)) as $k) {
                    $prod = $prodActivos[$k];
                    $cant = mt_rand(1, 3);
                    $cu = $this->centavosProducto($prod, $c['creada']);
                    $centDetalle += $cant * $cu;
                    $fDetalle[] = ['id_compra' => $idC, 'id_producto' => $prod['id'], 'cantidad' => $cant, 'precio_unitario' => $this->dec($cu)];
                }
            }

            $total = $centEntradas + $centDetalle; // en compras canceladas/expiradas conserva el monto original

            $fCompra[] = [
                'id_compra' => $idC, 'id_comprador' => $baseComprador + $asignado[$ci],
                'creada_en' => $this->dt($c['creada']), 'vence_reserva' => $this->dt($c['vence']),
                'moneda' => self::MONEDA, 'total' => $this->dec($total), 'estado' => $estado,
                'cancelada_en' => $c['cancelada'] ? $this->dt($c['cancelada']) : null,
                'motivo_cancelacion' => $c['motivo'],
            ];

            // Pago
            $ref = ($c['metodo'] === 'QR' ? 'QR-' : 'TRJ-') . $c['creada']->format('Ymd') . '-' . str_pad((string) $idC, 6, '0', STR_PAD_LEFT);
            if ($estado === 'CONFIRMADA') {
                $fPago[] = ['id_compra' => $idC, 'metodo' => $c['metodo'], 'importe' => $this->dec($total), 'estado' => 'APROBADO',
                    'referencia_externa' => $ref, 'creado_en' => $this->dt($c['creada']), 'aprobado_en' => $this->dt($c['aprobado'])];
            } elseif ($estado === 'PENDIENTE_PAGO') {
                $fPago[] = ['id_compra' => $idC, 'metodo' => $c['metodo'], 'importe' => $this->dec($total), 'estado' => 'PENDIENTE',
                    'referencia_externa' => null, 'creado_en' => $this->dt($c['creada']), 'aprobado_en' => null];
            } elseif ($estado === 'CANCELADA' && $c['rechazado']) {
                $fPago[] = ['id_compra' => $idC, 'metodo' => $c['metodo'], 'importe' => $this->dec($total), 'estado' => 'RECHAZADO',
                    'referencia_externa' => $ref, 'creado_en' => $this->dt($c['creada']), 'aprobado_en' => null];
            }

            // Ticket
            if ($estado === 'CONFIRMADA') {
                do {
                    $suf = '';
                    for ($i = 0; $i < 8; $i++) { $suf .= $alfabeto[mt_rand(0, strlen($alfabeto) - 1)]; }
                    $codigo = 'CP-' . $c['aprobado']->format('Ymd') . '-' . $suf;
                } while (isset($codigos[$codigo]));
                $codigos[$codigo] = true;

                $usado = $c['usada'] && $utilizadas;
                $fTicket[] = [
                    'id_compra' => $idC, 'codigo_validacion' => $codigo, 'emitido_en' => $this->dt($c['aprobado']),
                    'estado' => $usado ? 'UTILIZADO' : 'EMITIDO',
                    'utilizado_en' => $usado ? $this->dt(min($utilizadas)) : null,
                ];
            }
        }

        $this->insertar('compra', $fCompra);
        $this->insertar('entrada', $fEntrada);
        $this->insertar('pago', $fPago);
        $this->insertar('ticket', $fTicket);
        $this->insertar('detalle_compra', $fDetalle);
    }

    // ===================== VERIFICACIÓN =====================
    private function verificar(): void
    {
        $hoy = $this->ahora->toDateString();
        $ahoraStr = $this->dt($this->ahora);
        $checks = [
            ['1a. Películas en cartelera sin funciones',
                "SELECT COUNT(*) n FROM pelicula p WHERE p.estado='ACTIVA' AND p.fecha_estreno <= ? AND NOT EXISTS (SELECT 1 FROM funcion f WHERE f.id_pelicula=p.id_pelicula)", [$hoy]],
            ['1b. Películas «próximamente» con funciones',
                "SELECT COUNT(*) n FROM pelicula p WHERE p.fecha_estreno > ? AND EXISTS (SELECT 1 FROM funcion f WHERE f.id_pelicula=p.id_pelicula)", [$hoy]],
            ['2. Funciones solapadas en la misma sala (con margen de limpieza)',
                "SELECT COUNT(*) n FROM funcion a JOIN funcion b ON b.id_sala=a.id_sala AND b.id_funcion<>a.id_funcion AND b.inicio>=a.inicio JOIN pelicula pa ON pa.id_pelicula=a.id_pelicula JOIN sala s ON s.id_sala=a.id_sala WHERE b.inicio < DATE_ADD(a.inicio, INTERVAL (pa.duracion_minutos + s.margen_limpieza_minutos) MINUTE)", []],
            ['3. funcion_asiento con más de una entrada activa',
                "SELECT COUNT(*) n FROM (SELECT id_funcion_asiento FROM entrada WHERE estado IN ('RESERVADA','CONFIRMADA','UTILIZADA') GROUP BY id_funcion_asiento HAVING COUNT(*)>1) x", []],
            ['4. compra.total ≠ entradas vigentes + detalles',
                "SELECT COUNT(*) n FROM compra c WHERE c.total <> (SELECT COALESCE(SUM(e.precio_venta),0) FROM entrada e WHERE e.id_compra=c.id_compra AND (c.estado IN ('CANCELADA','EXPIRADA') OR e.estado NOT IN ('CANCELADA','EXPIRADA'))) + (SELECT COALESCE(SUM(d.cantidad*d.precio_unitario),0) FROM detalle_compra d WHERE d.id_compra=c.id_compra)", []],
            ['5. pago.importe ≠ compra.total',
                "SELECT COUNT(*) n FROM pago p JOIN compra c ON c.id_compra=p.id_compra WHERE p.importe <> c.total", []],
            ['6. Películas sin créditos mínimos (1 dir., 3 act., 1 guion., 1 prod., 1 comp.)',
                "SELECT COUNT(*) n FROM pelicula p WHERE (SELECT COUNT(*) FROM pelicula_credito c WHERE c.id_pelicula=p.id_pelicula AND c.rol='DIRECTOR')<1 OR (SELECT COUNT(*) FROM pelicula_credito c WHERE c.id_pelicula=p.id_pelicula AND c.rol='ACTOR')<3 OR (SELECT COUNT(*) FROM pelicula_credito c WHERE c.id_pelicula=p.id_pelicula AND c.rol='GUIONISTA')<1 OR (SELECT COUNT(*) FROM pelicula_credito c WHERE c.id_pelicula=p.id_pelicula AND c.rol='PRODUCTOR')<1 OR (SELECT COUNT(*) FROM pelicula_credito c WHERE c.id_pelicula=p.id_pelicula AND c.rol='COMPOSITOR')<1", []],
            ['7. Películas sin los 4 tipos de multimedia',
                "SELECT COUNT(*) n FROM pelicula p WHERE (SELECT COUNT(DISTINCT m.tipo) FROM pelicula_multimedia m WHERE m.id_pelicula=p.id_pelicula)<4", []],
            ['8. Funciones fuera del rango (mes actual y siguiente)',
                "SELECT COUNT(*) n FROM funcion WHERE DATE(inicio) < ? OR DATE(inicio) > ?", [$this->inicioRango->toDateString(), $this->finRango->toDateString()]],
            ['9. Compras creadas en el futuro',
                "SELECT COUNT(*) n FROM compra WHERE creada_en > ?", [$ahoraStr]],
            ['10. Compras confirmadas creadas después del inicio de la función',
                "SELECT COUNT(*) n FROM compra c JOIN (SELECT e.id_compra, MIN(f.inicio) inicio FROM entrada e JOIN funcion_asiento fa ON fa.id_funcion_asiento=e.id_funcion_asiento JOIN funcion f ON f.id_funcion=fa.id_funcion GROUP BY e.id_compra) x ON x.id_compra=c.id_compra WHERE c.estado='CONFIRMADA' AND c.creada_en >= x.inicio", []],
            ['11. entrada.precio_venta ≠ funcion_asiento.precio',
                "SELECT COUNT(*) n FROM entrada e JOIN funcion_asiento fa ON fa.id_funcion_asiento=e.id_funcion_asiento WHERE e.precio_venta <> fa.precio", []],
            ['12. Compras confirmadas sin ticket',
                "SELECT COUNT(*) n FROM compra c LEFT JOIN ticket t ON t.id_compra=c.id_compra WHERE c.estado='CONFIRMADA' AND t.id_ticket IS NULL", []],
        ];

        $fallos = [];
        foreach ($checks as [$etiqueta, $sql, $bind]) {
            $n = (int) DB::selectOne($sql, $bind)->n;
            $this->msg(sprintf('  [%s] %s: %d', $n === 0 ? 'OK' : 'FALLA', $etiqueta, $n));
            if ($n > 0) { $fallos[] = "{$etiqueta} ({$n})"; }
        }
        if ($fallos) {
            throw new RuntimeException('Verificación fallida: ' . implode('; ', $fallos));
        }

        // Avisos informativos (no bloquean).
        $con3d = (int) DB::selectOne("SELECT COUNT(DISTINCT id_pelicula) n FROM funcion WHERE formato='3D'")->n;
        $ambos = (int) DB::selectOne("SELECT COUNT(*) n FROM (SELECT id_pelicula FROM funcion GROUP BY id_pelicula HAVING SUM(formato='3D')>0 AND SUM(formato='2D')>0) x")->n;
        $this->msg("  Películas con funciones 3D: {$con3d} (mín. 10) · con ambos formatos: {$ambos} (mín. 4)", $con3d >= 10 && $ambos >= 4 ? 'info' : 'warn');

        $conteo = [];
        foreach ($this->funciones as $f) {
            $conteo[$f['bloque']][$f['pelicula']] = ($conteo[$f['bloque']][$f['pelicula']] ?? 0) + 1;
        }
        $bajos = 0;
        foreach (array_keys(array_filter($this->peliculas, fn ($p) => $p['tipo'] === 'cartelera')) as $idPeli) {
            foreach ($conteo as $porPeli) {
                if (($porPeli[$idPeli] ?? 0) < 3) { $bajos++; }
            }
        }
        $this->msg("  Combinaciones película/semana con menos de 3 funciones: {$bajos}", $bajos === 0 ? 'info' : 'warn');
    }

    // ===================== RESUMEN =====================
    private function resumen(): void
    {
        $this->msg('═══ Resumen de filas ═══');
        foreach (array_reverse(self::TABLAS_DOMINIO) as $t) {
            $this->msg(sprintf('  %-22s %7d', $t, DB::table($t)->count()));
        }
        foreach ([['compra', 'estado'], ['entrada', 'estado'], ['pago', 'estado'], ['ticket', 'estado'], ['funcion', 'estado'], ['pelicula', 'estado'], ['producto', 'estado']] as [$t, $col]) {
            $filas = DB::table($t)->select($col, DB::raw('COUNT(*) n'))->groupBy($col)->pluck('n', $col)->all();
            $txt = implode(', ', array_map(fn ($k, $v) => "{$k}={$v}", array_keys($filas), $filas));
            $this->msg("  {$t} por {$col}: {$txt}");
        }
        $this->msg('Datos de demostración generados correctamente.');
    }
}
