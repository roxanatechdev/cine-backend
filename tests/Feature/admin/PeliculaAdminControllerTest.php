<?php

namespace Tests\Feature\Admin;

use App\Models\Pelicula;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeliculaAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Estas pruebas aíslan las reglas del controlador.
        // No prueban la autenticación ni el middleware admin.
        $this->withoutMiddleware([
            \Illuminate\Auth\Middleware\Authenticate::class,
            \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    }

    private function crearGenero(
        string $nombre = 'Drama',
        int $activo = 1
    ): int {
        return (int) DB::table('genero')->insertGetId([
            'nombre' => $nombre,
            'descripcion' => null,
            'activo' => $activo,
        ], 'id_genero');
    }

    private function crearIdioma(
        string $codigo = 'es',
        int $activo = 1
    ): string {
        DB::table('idioma')->insert([
            'codigo' => $codigo,
            'nombre' => 'Idioma ' . $codigo,
            'activo' => $activo,
        ]);

        return $codigo;
    }

    private function datosValidos(array $sobrescrituras = []): array
    {
        return array_merge([
            'titulo' => 'Película de prueba',
            'sinopsis' => 'Sinopsis de prueba para el catálogo.',
            'id_genero_principal' => null,
            'clasificacion' => 'PG-13',
            'duracion_minutos' => 120,
            'fecha_estreno' => '2026-11-20',
            'idioma_original_codigo' => null,
            'estado' => 'BORRADOR',
        ], $sobrescrituras);
    }

    private function crearPelicula(
        array $sobrescrituras = []
    ): Pelicula {
        return Pelicula::query()->create(
            $this->datosValidos($sobrescrituras)
        );
    }

    private function agregarPosterPrincipal(
        Pelicula $pelicula,
        int $activo = 1,
        int $principal = 1
    ): void {
        DB::table('pelicula_multimedia')->insert([
            'id_pelicula' => $pelicula->id_pelicula,
            'tipo' => 'POSTER',
            'url' => 'https://cineprime.test/poster.jpg',
            'texto_alternativo' => 'Póster de prueba',
            'es_principal' => $principal,
            'orden' => 0,
            'activo' => $activo,
            'creado_en' => now(),
        ]);
    }

    public function test_crea_una_pelicula_como_borrador(): void
    {
        $response = $this->post(
            route('admin.peliculas.store'),
            $this->datosValidos()
        );

        $response->assertSessionHasNoErrors();

        $pelicula = Pelicula::query()->where('titulo', 'Película de prueba')->firstOrFail();
        $response->assertRedirect(route('admin.peliculas.edit', $pelicula));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pelicula', [
            'titulo' => 'Película de prueba',
            'estado' => 'BORRADOR',
            'id_genero_principal' => null,
            'idioma_original_codigo' => null,
        ]);
    }

    public function test_no_permite_crear_una_pelicula_activa(): void
    {
        $response = $this->post(
            route('admin.peliculas.store'),
            $this->datosValidos([
                'estado' => 'ACTIVA',
            ])
        );

        $response->assertSessionHasErrors('estado');

        $this->assertDatabaseMissing('pelicula', [
            'titulo' => 'Película de prueba',
        ]);
    }

    public function test_valida_campos_principales(): void
    {
        $response = $this->post(
            route('admin.peliculas.store'),
            $this->datosValidos([
                'titulo' => '',
                'sinopsis' => '',
                'clasificacion' => 'CLASIFICACION_INVALIDA',
                'duracion_minutos' => 0,
                'fecha_estreno' => '20-11-2026',
            ])
        );

        $response->assertSessionHasErrors([
            'titulo',
            'sinopsis',
            'clasificacion',
            'duracion_minutos',
            'fecha_estreno',
        ]);

        $this->assertDatabaseCount('pelicula', 0);
    }

    public function test_no_permite_asignar_un_genero_inactivo_nuevo(): void
    {
        $generoInactivo = $this->crearGenero(
            'Género inactivo',
            0
        );

        $response = $this->post(
            route('admin.peliculas.store'),
            $this->datosValidos([
                'id_genero_principal' => $generoInactivo,
            ])
        );

        $response->assertSessionHasErrors('id_genero_principal');

        $this->assertDatabaseCount('pelicula', 0);
    }

    public function test_no_permite_asignar_un_idioma_inactivo_nuevo(): void
    {
        $idiomaInactivo = $this->crearIdioma('xx', 0);

        $response = $this->post(
            route('admin.peliculas.store'),
            $this->datosValidos([
                'idioma_original_codigo' => $idiomaInactivo,
            ])
        );

        $response->assertSessionHasErrors('idioma_original_codigo');

        $this->assertDatabaseCount('pelicula', 0);
    }

    public function test_no_permite_publicar_sin_genero_e_idioma(): void
    {
        $pelicula = $this->crearPelicula();

        $response = $this->put(
            route('admin.peliculas.update', $pelicula),
            $this->datosValidos([
                'estado' => 'ACTIVA',
            ])
        );

        $response->assertSessionHasErrors([
            'id_genero_principal',
            'idioma_original_codigo',
        ]);

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'BORRADOR',
        ]);
    }

    public function test_no_permite_publicar_con_genero_inactivo(): void
    {
        $genero = $this->crearGenero('Drama inactivo', 0);
        $idioma = $this->crearIdioma();

        $pelicula = $this->crearPelicula([
            'id_genero_principal' => $genero,
            'idioma_original_codigo' => $idioma,
        ]);

        $this->agregarPosterPrincipal($pelicula);

        $response = $this->put(
            route('admin.peliculas.update', $pelicula),
            $this->datosValidos([
                'id_genero_principal' => $genero,
                'idioma_original_codigo' => $idioma,
                'estado' => 'ACTIVA',
            ])
        );

        $response->assertSessionHasErrors('id_genero_principal');

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'BORRADOR',
        ]);
    }

    public function test_no_permite_publicar_sin_poster_principal_activo(): void
    {
        $genero = $this->crearGenero();
        $idioma = $this->crearIdioma();

        $pelicula = $this->crearPelicula([
            'id_genero_principal' => $genero,
            'idioma_original_codigo' => $idioma,
        ]);

        // No se registra ningún póster para esta película.
        $response = $this->put(
            route('admin.peliculas.update', $pelicula),
            $this->datosValidos([
                'id_genero_principal' => $genero,
                'idioma_original_codigo' => $idioma,
                'estado' => 'ACTIVA',
            ])
        );

        $response->assertSessionHasErrors('estado');

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'BORRADOR',
        ]);
    }

    public function test_no_permite_publicar_con_poster_inactivo(): void
    {
        $genero = $this->crearGenero();
        $idioma = $this->crearIdioma();

        $pelicula = $this->crearPelicula([
            'id_genero_principal' => $genero,
            'idioma_original_codigo' => $idioma,
        ]);

        $this->agregarPosterPrincipal(
            $pelicula,
            activo: 0,
            principal: 1
        );

        $response = $this->put(
            route('admin.peliculas.update', $pelicula),
            $this->datosValidos([
                'id_genero_principal' => $genero,
                'idioma_original_codigo' => $idioma,
                'estado' => 'ACTIVA',
            ])
        );

        $response->assertSessionHasErrors('estado');

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'BORRADOR',
        ]);
    }

    public function test_permite_publicar_con_datos_y_poster_validos(): void
    {
        $genero = $this->crearGenero();
        $idioma = $this->crearIdioma();

        $pelicula = $this->crearPelicula([
            'id_genero_principal' => $genero,
            'idioma_original_codigo' => $idioma,
        ]);

        $this->agregarPosterPrincipal($pelicula);

        $response = $this->put(
            route('admin.peliculas.update', $pelicula),
            $this->datosValidos([
                'id_genero_principal' => $genero,
                'idioma_original_codigo' => $idioma,
                'estado' => 'ACTIVA',
            ])
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.peliculas.edit', $pelicula));

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'ACTIVA',
        ]);
    }
}
