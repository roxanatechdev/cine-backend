<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AsientoAdminControllerProteccionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Aísla las reglas del controlador de la autenticación y el middleware admin.
        $this->withoutMiddleware([
            \Illuminate\Auth\Middleware\Authenticate::class,
            \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    }

    private function crearSala(string $nombre): int
    {
        return (int) DB::table('sala')->insertGetId([
            'nombre' => $nombre,
            'capacidad' => null,
            'margen_limpieza_minutos' => 30,
            'estado' => 'ACTIVA',
        ], 'id_sala');
    }

    private function crearAsiento(int $idSala, array $cambios = []): int
    {
        return (int) DB::table('asiento')->insertGetId(array_merge([
            'id_sala' => $idSala,
            'fila' => 'A',
            'numero' => 1,
            'tipo' => 'ESTANDAR',
            'activo' => 1,
        ], $cambios), 'id_asiento');
    }

    private function crearAsientoConFuncion(int $idSala, array $cambios = []): int
    {
        $idAsiento = $this->crearAsiento($idSala, $cambios);

        $idPelicula = (int) DB::table('pelicula')->insertGetId([
            'titulo' => 'Película para prueba de asiento',
            'sinopsis' => 'Registro mínimo para probar las referencias históricas.',
            'estado' => 'ACTIVA',
        ], 'id_pelicula');

        $idFuncion = (int) DB::table('funcion')->insertGetId([
            'id_pelicula' => $idPelicula,
            'id_sala' => $idSala,
            'inicio' => '2026-11-15 18:00:00',
            'formato' => '2D',
            'precio_base' => 10.00,
            'estado' => 'PROGRAMADA',
        ], 'id_funcion');

        DB::table('funcion_asiento')->insert([
            'id_funcion' => $idFuncion,
            'id_asiento' => $idAsiento,
            'precio' => 10.00,
        ]);

        return $idAsiento;
    }

    private function datosAsiento(int $idSala, array $cambios = []): array
    {
        return array_merge([
            'id_sala' => $idSala,
            'fila' => 'A',
            'numero' => 1,
            'tipo' => 'ESTANDAR',
            'activo' => 1,
        ], $cambios);
    }

    public function test_bloquea_cambio_de_sala_fila_numero_y_tipo_si_el_asiento_esta_referenciado(): void
    {
        $idSala = $this->crearSala('Sala origen');
        $idSalaDestino = $this->crearSala('Sala destino');
        $idAsiento = $this->crearAsientoConFuncion($idSala);

        $casos = [
            ['id_sala', $idSalaDestino],
            ['fila', 'B'],
            ['numero', 2],
            ['tipo', 'PREFERENCIAL'],
        ];

        foreach ($casos as [$campo, $valor]) {
            $respuesta = $this->put(
                route('admin.asientos.update', $idAsiento),
                $this->datosAsiento($idSala, [$campo => $valor])
            );

            $respuesta->assertSessionHasErrors([$campo]);

            $this->assertDatabaseHas('asiento', [
                'id_asiento' => $idAsiento,
                'id_sala' => $idSala,
                'fila' => 'A',
                'numero' => 1,
                'tipo' => 'ESTANDAR',
            ]);
        }
    }

    public function test_muestra_las_funciones_asociadas_y_las_entradas_de_un_asiento_protegido(): void
    {
        $idSala = $this->crearSala('Sala consulta protegida');
        $idAsiento = $this->crearAsientoConFuncion($idSala);

        $respuesta = $this->get(route('admin.asientos.edit', $idAsiento));

        $respuesta->assertOk();
        $respuesta->assertSee('Asiento protegido por historial de funciones');
        $respuesta->assertSee('Película para prueba de asiento');
        $respuesta->assertSee('Funciones asociadas:');
        $respuesta->assertSee('Entradas vinculadas: 0');
        $respuesta->assertSee('No puedes cambiar su sala, fila, número ni tipo');
    }

    public function test_no_muestra_aviso_de_proteccion_si_el_asiento_no_tiene_funciones(): void
    {
        $idSala = $this->crearSala('Sala consulta libre');
        $idAsiento = $this->crearAsiento($idSala);

        $respuesta = $this->get(route('admin.asientos.edit', $idAsiento));

        $respuesta->assertOk();
        $respuesta->assertDontSee('Asiento protegido por historial de funciones');
    }

    public function test_permite_cambiar_estado_sin_alterar_la_referencia_historica(): void
    {
        $idSala = $this->crearSala('Sala estado');
        $idAsiento = $this->crearAsientoConFuncion($idSala);

        $respuesta = $this->put(
            route('admin.asientos.update', $idAsiento),
            $this->datosAsiento($idSala, ['activo' => 0])
        );

        $respuesta->assertSessionHasNoErrors();
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'activo' => 0,
            'fila' => 'A',
            'numero' => 1,
            'tipo' => 'ESTANDAR',
        ]);
        $this->assertDatabaseHas('funcion_asiento', [
            'id_asiento' => $idAsiento,
        ]);
    }

    public function test_permite_modificar_estructura_de_asiento_sin_referencias(): void
    {
        $idSala = $this->crearSala('Sala sin referencias');
        $idSalaDestino = $this->crearSala('Sala sin referencias destino');
        $idAsiento = $this->crearAsiento($idSala);

        $respuesta = $this->put(
            route('admin.asientos.update', $idAsiento),
            $this->datosAsiento($idSalaDestino, [
                'fila' => 'C',
                'numero' => 7,
                'tipo' => 'ACCESIBLE',
            ])
        );

        $respuesta->assertSessionHasNoErrors();
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'id_sala' => $idSalaDestino,
            'fila' => 'C',
            'numero' => 7,
            'tipo' => 'ACCESIBLE',
        ]);
    }

    public function test_bloquea_eliminacion_de_asiento_referenciado(): void
    {
        $idSala = $this->crearSala('Sala protegida');
        $idAsiento = $this->crearAsientoConFuncion($idSala);

        $respuesta = $this->delete(route('admin.asientos.destroy', $idAsiento));

        $respuesta->assertSessionHas('error');
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento]);
        $this->assertDatabaseHas('funcion_asiento', ['id_asiento' => $idAsiento]);
    }

    public function test_permite_eliminar_asiento_sin_referencias(): void
    {
        $idSala = $this->crearSala('Sala eliminable');
        $idAsiento = $this->crearAsiento($idSala);

        $respuesta = $this->delete(route('admin.asientos.destroy', $idAsiento));

        $respuesta->assertSessionHas('success');
        $this->assertDatabaseMissing('asiento', ['id_asiento' => $idAsiento]);
    }
}
