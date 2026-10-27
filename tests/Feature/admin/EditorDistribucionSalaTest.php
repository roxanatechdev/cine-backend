<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Salas\EditorDistribucionSala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class EditorDistribucionSalaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Auth\Middleware\Authenticate::class,
            \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    }

    private function crearSala(string $nombre = 'Sala editor'): int
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

    private function asociarAFuncion(int $idSala, int $idAsiento): void
    {
        $idPelicula = (int) DB::table('pelicula')->insertGetId([
            'titulo' => 'Película para distribución',
            'sinopsis' => 'Registro de prueba para historial del asiento.',
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
    }

    public function test_muestra_la_pagina_de_distribucion_visual_de_una_sala(): void
    {
        $idSala = $this->crearSala();

        $this->get(route('admin.salas.distribucion', $idSala))
            ->assertOk()
            ->assertSee('Distribución visual de Sala editor')
            ->assertSee('Plano de asientos');
    }

    public function test_agrega_un_asiento_desde_una_celda_vacia(): void
    {
        $idSala = $this->crearSala();

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('seleccionarCelda', 'A', 1)
            ->set('fila', 'A')
            ->set('numero', 1)
            ->set('tipo', 'ACCESIBLE')
            ->call('guardarAsiento')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asiento', [
            'id_sala' => $idSala,
            'fila' => 'A',
            'numero' => 1,
            'tipo' => 'ACCESIBLE',
            'activo' => 1,
        ]);
    }

    public function test_previsualiza_y_crea_asientos_faltantes_en_lote_sin_modificar_existentes(): void
    {
        $idSala = $this->crearSala();
        $idExistente = $this->crearAsiento($idSala, ['fila' => 'B', 'numero' => 2, 'tipo' => 'ACCESIBLE', 'activo' => 0]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('filaMasiva', 'B')
            ->set('numeroInicioMasivo', 1)
            ->set('numeroFinMasivo', 4)
            ->set('tipoMasivo', 'PREFERENCIAL')
            ->set('activoMasivo', '1')
            ->call('previsualizarCreacionMasiva')
            ->assertHasNoErrors()
            ->assertSet('vistaPreviaMasiva.faltantes', 3)
            ->assertSet('vistaPreviaMasiva.existentes', 1)
            ->call('crearAsientosMasivos')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('asiento', 4);
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idExistente,
            'fila' => 'B',
            'numero' => 2,
            'tipo' => 'ACCESIBLE',
            'activo' => 0,
        ]);
        foreach ([1, 3, 4] as $numero) {
            $this->assertDatabaseHas('asiento', [
                'id_sala' => $idSala,
                'fila' => 'B',
                'numero' => $numero,
                'tipo' => 'PREFERENCIAL',
                'activo' => 1,
            ]);
        }
    }

    public function test_rechaza_rango_masivo_invertido_y_mas_de_cien_posiciones(): void
    {
        $idSala = $this->crearSala();

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('numeroInicioMasivo', 10)
            ->set('numeroFinMasivo', 2)
            ->call('previsualizarCreacionMasiva')
            ->assertHasErrors('numeroFinMasivo');

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('numeroInicioMasivo', 1)
            ->set('numeroFinMasivo', 101)
            ->call('previsualizarCreacionMasiva')
            ->assertHasErrors('numeroFinMasivo');

        $this->assertDatabaseCount('asiento', 0);
    }

    public function test_impide_cambiar_tipo_de_asiento_con_historial_pero_permite_desactivarlo(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);
        $this->asociarAFuncion($idSala, $idAsiento);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('seleccionarCelda', 'A', 1)
            ->set('tipo', 'PREFERENCIAL')
            ->set('activo', '0')
            ->call('guardarAsiento')
            ->assertHasErrors('tipo');

        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'tipo' => 'ESTANDAR',
            'activo' => 1,
        ]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('seleccionarCelda', 'A', 1)
            ->set('activo', '0')
            ->call('guardarAsiento')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'tipo' => 'ESTANDAR',
            'activo' => 0,
        ]);
        $this->assertDatabaseHas('funcion_asiento', ['id_asiento' => $idAsiento]);
    }

    public function test_mueve_asiento_sin_historial_a_otra_ubicacion(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('seleccionarCelda', 'A', 1)
            ->set('fila', 'B')
            ->set('numero', 3)
            ->call('guardarAsiento')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'id_sala' => $idSala,
            'fila' => 'B',
            'numero' => 3,
        ]);
        $this->assertDatabaseMissing('asiento', [
            'id_sala' => $idSala,
            'fila' => 'A',
            'numero' => 1,
        ]);
    }

    public function test_impide_mover_asiento_con_historial(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);
        $this->asociarAFuncion($idSala, $idAsiento);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('seleccionarCelda', 'A', 1)
            ->set('fila', 'B')
            ->set('numero', 2)
            ->call('guardarAsiento')
            ->assertHasErrors('tipo');

        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'fila' => 'A',
            'numero' => 1,
        ]);
    }

    public function test_elimina_asiento_sin_historial(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('seleccionarCelda', 'A', 1)
            ->call('eliminarAsiento')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('asiento', ['id_asiento' => $idAsiento]);
    }

    public function test_impide_eliminar_asiento_con_historial(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);
        $this->asociarAFuncion($idSala, $idAsiento);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('seleccionarCelda', 'A', 1)
            ->call('eliminarAsiento')
            ->assertHasErrors('asientoSeleccionadoId');

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento]);
        $this->assertDatabaseHas('funcion_asiento', ['id_asiento' => $idAsiento]);
    }


    public function test_migracion_conserva_pasillos_existentes_como_longitudinales(): void
    {
        $idSala = $this->crearSala();

        $idPasillo = DB::table('sala_pasillo')->insertGetId([
            'id_sala' => $idSala,
            'fila' => 'A',
            'despues_de_numero' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_pasillo');

        $this->assertDatabaseHas('sala_pasillo', [
            'id_pasillo' => $idPasillo,
            'orientacion' => 'LONGITUDINAL',
            'fila_siguiente' => null,
        ]);
    }

    public function test_permite_registrar_pasillo_transversal_entre_dos_filas(): void
    {
        $idSala = $this->crearSala();

        DB::table('sala_pasillo')->insert([
            'id_sala' => $idSala,
            'orientacion' => 'TRANSVERSAL',
            'fila' => 'B',
            'fila_siguiente' => 'C',
            'despues_de_numero' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('sala_pasillo', [
            'id_sala' => $idSala,
            'orientacion' => 'TRANSVERSAL',
            'fila' => 'B',
            'fila_siguiente' => 'C',
        ]);
    }

    public function test_indice_unico_impide_duplicar_el_mismo_pasillo_transversal(): void
    {
        $idSala = $this->crearSala();
        $pasillo = [
            'id_sala' => $idSala,
            'orientacion' => 'TRANSVERSAL',
            'fila' => 'B',
            'fila_siguiente' => 'C',
            'despues_de_numero' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('sala_pasillo')->insert($pasillo);

        try {
            DB::table('sala_pasillo')->insert($pasillo);
            $this->fail('La base de datos debía rechazar el pasillo transversal duplicado.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertDatabaseCount('sala_pasillo', 1);
        }
    }

    public function test_agrega_y_persiste_pasillo_entre_dos_posiciones_sin_renumerar_asientos(): void
    {
        $idSala = $this->crearSala();
        $idAsiento1 = $this->crearAsiento($idSala, ['numero' => 2]);
        $idAsiento2 = $this->crearAsiento($idSala, ['numero' => 3]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('alternarPasillo', 'A', 2)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sala_pasillo', [
            'id_sala' => $idSala,
            'fila' => 'A',
            'despues_de_numero' => 2,
        ]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento1, 'numero' => 2]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento2, 'numero' => 3]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->assertSet('salaId', $idSala);
    }

    public function test_quita_pasillo_sin_afectar_asientos_con_historial(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala, ['numero' => 2]);
        $this->asociarAFuncion($idSala, $idAsiento);
        DB::table('sala_pasillo')->insert([
            'id_sala' => $idSala,
            'fila' => 'A',
            'despues_de_numero' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('alternarPasillo', 'A', 2)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sala_pasillo', ['id_sala' => $idSala, 'fila' => 'A', 'despues_de_numero' => 2]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento, 'numero' => 2]);
        $this->assertDatabaseHas('funcion_asiento', ['id_asiento' => $idAsiento]);
    }

    public function test_rechaza_pasillo_fuera_del_rango_permitido(): void
    {
        $idSala = $this->crearSala();

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('alternarPasillo', 'A', 0)
            ->assertHasErrors('despuesDeNumero');

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('alternarPasillo', 'A', 65535)
            ->assertHasErrors('despuesDeNumero');

        $this->assertDatabaseCount('sala_pasillo', 0);
    }


    public function test_muestra_resumen_de_asientos_pasillos_y_advertencias_de_huecos(): void
    {
        $idSala = $this->crearSala();
        $idActivo = $this->crearAsiento($idSala, ['numero' => 1, 'activo' => 1]);
        $idInactivo = $this->crearAsiento($idSala, ['numero' => 3, 'activo' => 0]);
        $this->asociarAFuncion($idSala, $idActivo);
        DB::table('sala_pasillo')->insert([
            'id_sala' => $idSala,
            'fila' => 'A',
            'despues_de_numero' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->assertSee('Validación de la distribución')
            ->assertSee('Asientos registrados')
            ->assertSee('Con historial')
            ->assertSee('Pasillos')
            ->assertSee('La fila A tiene posiciones vacías dentro de su rango: 2.')
            ->assertSee('1 advertencia(s)');

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idActivo, 'activo' => 1]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idInactivo, 'activo' => 0]);
    }

    public function test_no_advierte_huecos_en_una_fila_consecutiva(): void
    {
        $idSala = $this->crearSala();
        $this->crearAsiento($idSala, ['numero' => 1]);
        $this->crearAsiento($idSala, ['numero' => 2]);
        $this->crearAsiento($idSala, ['numero' => 3]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->assertSee('Sin huecos internos detectados')
            ->assertDontSee('posiciones vacías dentro de su rango');
    }


    public function test_edicion_masiva_cambia_tipo_solo_en_asientos_seleccionados_y_no_toca_otros(): void
    {
        $idSala = $this->crearSala();
        $idA = $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);
        $idB = $this->crearAsiento($idSala, ['fila' => 'B', 'numero' => 2]);
        $idC = $this->crearAsiento($idSala, ['fila' => 'C', 'numero' => 3]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('asientosSeleccionados', [$idA, $idB])
            ->set('tipoEdicionMasiva', 'PREFERENCIAL')
            ->call('previsualizarEdicionMasiva')
            ->assertHasNoErrors()
            ->assertSet('vistaPreviaEdicionMasiva.seleccionados', 2)
            ->call('aplicarEdicionMasiva')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idA, 'tipo' => 'PREFERENCIAL']);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idB, 'tipo' => 'PREFERENCIAL']);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idC, 'tipo' => 'ESTANDAR']);
    }

    public function test_edicion_masiva_preserva_tipo_de_asiento_protegido_pero_permite_cambiar_disponibilidad(): void
    {
        $idSala = $this->crearSala();
        $idProtegido = $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);
        $idNormal = $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 2]);
        $this->asociarAFuncion($idSala, $idProtegido);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('asientosSeleccionados', [$idProtegido, $idNormal])
            ->set('tipoEdicionMasiva', 'ACCESIBLE')
            ->set('activoEdicionMasiva', '0')
            ->call('previsualizarEdicionMasiva')
            ->assertHasNoErrors()
            ->assertSet('vistaPreviaEdicionMasiva.protegidos', 1)
            ->assertSet('vistaPreviaEdicionMasiva.modificables_tipo', 1)
            ->call('aplicarEdicionMasiva')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idProtegido, 'tipo' => 'ESTANDAR', 'activo' => 0]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idNormal, 'tipo' => 'ACCESIBLE', 'activo' => 0]);
        $this->assertDatabaseHas('funcion_asiento', ['id_asiento' => $idProtegido]);
    }

    public function test_edicion_masiva_rechaza_seleccion_vacia_sin_modificar_asientos(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('tipoEdicionMasiva', 'ACCESIBLE')
            ->call('previsualizarEdicionMasiva')
            ->assertHasErrors('asientosSeleccionados');

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento, 'tipo' => 'ESTANDAR']);
    }

}
