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
            ->assertHasNoErrors()
            ->call('guardarDistribucion')
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
            ->assertHasNoErrors()
            ->call('guardarDistribucion')
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
            ->assertHasNoErrors()
            ->call('guardarDistribucion')
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
            ->assertHasNoErrors()
            ->call('guardarDistribucion')
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
            ->assertHasNoErrors()
            ->call('guardarDistribucion')
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

    public function test_editor_agrega_y_quita_pasillo_transversal_entre_filas_consecutivas(): void
    {
        $idSala = $this->crearSala();
        $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);
        $this->crearAsiento($idSala, ['fila' => 'B', 'numero' => 1]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->assertSee('Agregar pasillo transversal')
            ->call('alternarPasilloTransversal', 'A', 'B')
            ->assertHasNoErrors()
            ->assertSee('Pasillo transversal')
            ->call('guardarDistribucion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sala_pasillo', [
            'id_sala' => $idSala,
            'orientacion' => 'TRANSVERSAL',
            'fila' => 'A',
            'fila_siguiente' => 'B',
            'despues_de_numero' => null,
        ]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('alternarPasilloTransversal', 'A', 'B')
            ->assertHasNoErrors()
            ->call('guardarDistribucion')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sala_pasillo', [
            'id_sala' => $idSala,
            'orientacion' => 'TRANSVERSAL',
            'fila' => 'A',
            'fila_siguiente' => 'B',
        ]);
    }

    public function test_editor_rechaza_pasillo_transversal_si_las_filas_no_son_consecutivas(): void
    {
        $idSala = $this->crearSala();
        $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);
        $this->crearAsiento($idSala, ['fila' => 'B', 'numero' => 1]);
        $this->crearAsiento($idSala, ['fila' => 'C', 'numero' => 1]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('alternarPasilloTransversal', 'A', 'C')
            ->assertHasErrors('distribucion');

        $this->assertDatabaseCount('sala_pasillo', 0);
    }

    public function test_agrega_y_persiste_pasillo_entre_dos_posiciones_sin_renumerar_asientos(): void
    {
        $idSala = $this->crearSala();
        $idAsiento1 = $this->crearAsiento($idSala, ['numero' => 2]);
        $idAsiento2 = $this->crearAsiento($idSala, ['numero' => 3]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('alternarPasillo', 'A', 2)
            ->assertHasNoErrors()
            ->call('guardarDistribucion')
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
            ->assertHasNoErrors()
            ->call('guardarDistribucion')
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
            ->assertHasNoErrors()
            ->call('guardarDistribucion')
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
            ->assertHasNoErrors()
            ->call('guardarDistribucion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idProtegido, 'tipo' => 'ESTANDAR', 'activo' => 0]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idNormal, 'tipo' => 'ACCESIBLE', 'activo' => 0]);
        $this->assertDatabaseHas('funcion_asiento', ['id_asiento' => $idProtegido]);
    }

    public function test_intercambia_posiciones_de_dos_asientos_sin_persistir_hasta_guardar(): void
    {
        $idSala = $this->crearSala();
        $idAsientoA1 = $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);
        $idAsientoB2 = $this->crearAsiento($idSala, ['fila' => 'B', 'numero' => 2]);

        $component = Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('asientosSeleccionados', [$idAsientoA1, $idAsientoB2])
            ->call('intercambiarPosicionesSeleccionadas')
            ->assertHasNoErrors()
            ->assertSet('hayCambiosPendientes', true)
            ->assertSet('modoSeleccionMultiple', false)
            ->assertSet('borradorAsientos.0.fila', 'B')
            ->assertSet('borradorAsientos.0.numero', 2)
            ->assertSet('borradorAsientos.1.fila', 'A')
            ->assertSet('borradorAsientos.1.numero', 1);

        // El intercambio solo modifica el borrador hasta que se guarda.
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsientoA1, 'fila' => 'A', 'numero' => 1]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsientoB2, 'fila' => 'B', 'numero' => 2]);

        $component->call('guardarDistribucion')->assertHasNoErrors();

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsientoA1, 'fila' => 'B', 'numero' => 2]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsientoB2, 'fila' => 'A', 'numero' => 1]);
        $this->assertDatabaseCount('asiento', 2);
    }

    public function test_intercambio_rechaza_asientos_con_historial_y_no_cambia_el_borrador(): void
    {
        $idSala = $this->crearSala();
        $idNormal = $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);
        $idProtegido = $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 2]);
        $this->asociarAFuncion($idSala, $idProtegido);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('asientosSeleccionados', [$idNormal, $idProtegido])
            ->call('intercambiarPosicionesSeleccionadas')
            ->assertHasErrors('asientosSeleccionados')
            ->assertSet('borradorAsientos.0.fila', 'A')
            ->assertSet('borradorAsientos.0.numero', 1)
            ->assertSet('borradorAsientos.1.fila', 'A')
            ->assertSet('borradorAsientos.1.numero', 2)
            ->assertSet('hayCambiosPendientes', false);

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idNormal, 'fila' => 'A', 'numero' => 1]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idProtegido, 'fila' => 'A', 'numero' => 2]);
    }

    public function test_intercambio_exige_exactamente_dos_asientos(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('asientosSeleccionados', [$idAsiento])
            ->call('intercambiarPosicionesSeleccionadas')
            ->assertHasErrors('asientosSeleccionados')
            ->assertSet('hayCambiosPendientes', false);
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


    public function test_eliminacion_masiva_quita_asientos_del_borrador_y_respeta_el_historial(): void
    {
        $idSala = $this->crearSala();
        $idNormalA = $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);
        $idProtegido = $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 2]);
        $idNormalB = $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 3]);
        $this->asociarAFuncion($idSala, $idProtegido);

        $component = Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('asientosSeleccionados', [$idNormalA, $idProtegido, $idNormalB])
            ->call('eliminarAsientosMasivos')
            ->assertHasNoErrors()
            ->assertSet('hayCambiosPendientes', true)
            ->assertSet('modoSeleccionMultiple', false);

        // La operación modifica el borrador; no persiste hasta pulsar Guardar distribución.
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idNormalA]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idNormalB]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idProtegido]);

        $component->call('guardarDistribucion')->assertHasNoErrors();

        $this->assertDatabaseMissing('asiento', ['id_asiento' => $idNormalA]);
        $this->assertDatabaseMissing('asiento', ['id_asiento' => $idNormalB]);
        $this->assertDatabaseHas('asiento', ['id_asiento' => $idProtegido]);
        $this->assertDatabaseHas('funcion_asiento', ['id_asiento' => $idProtegido]);
    }

    public function test_los_cambios_de_asientos_y_pasillos_no_se_persisten_hasta_guardar(): void
    {
        $idSala = $this->crearSala();
        $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);
        $component = Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('seleccionarCelda', 'A', 2)
            ->set('fila', 'A')->set('numero', 2)->set('tipo', 'ACCESIBLE')
            ->call('guardarAsiento')
            ->call('alternarPasillo', 'A', 1)
            ->assertSet('hayCambiosPendientes', true);

        $this->assertDatabaseMissing('asiento', ['id_sala' => $idSala, 'fila' => 'A', 'numero' => 2]);
        $this->assertDatabaseCount('sala_pasillo', 0);

        $component->call('guardarDistribucion')->assertHasNoErrors()->assertSet('hayCambiosPendientes', false);
        $this->assertDatabaseHas('asiento', ['id_sala' => $idSala, 'fila' => 'A', 'numero' => 2, 'tipo' => 'ACCESIBLE']);
        $this->assertDatabaseHas('sala_pasillo', ['id_sala' => $idSala, 'fila' => 'A', 'despues_de_numero' => 1]);
    }

    public function test_cancelar_distribucion_descarta_todos_los_cambios_del_borrador(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);
        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('seleccionarCelda', 'A', 1)->set('activo', '0')->call('guardarAsiento')
            ->call('alternarPasillo', 'A', 1)
            ->assertSet('hayCambiosPendientes', true)
            ->call('cancelarDistribucion')
            ->assertHasNoErrors()->assertSet('hayCambiosPendientes', false);

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento, 'activo' => 1]);
        $this->assertDatabaseCount('sala_pasillo', 0);
    }

    public function test_rechaza_asiento_protegido_antes_de_escribir_y_no_persiste_cambios_parciales(): void
    {
        $idSala = $this->crearSala();
        $idAsientoMovible = $this->crearAsiento($idSala, ['numero' => 1]);
        $idAsientoProtegido = $this->crearAsiento($idSala, ['numero' => 2]);
        $this->asociarAFuncion($idSala, $idAsientoProtegido);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos', [
                ['id' => $idAsientoMovible, 'fila' => 'B', 'numero' => 1, 'tipo' => 'ESTANDAR', 'activo' => 1, 'protegido' => false],
                // El componente no debe permitir cambiar la ubicación de un asiento con historial.
                ['id' => $idAsientoProtegido, 'fila' => 'A', 'numero' => 3, 'tipo' => 'ESTANDAR', 'activo' => 1, 'protegido' => true],
            ])
            ->call('guardarDistribucion')
            ->assertHasErrors('distribucion');

        // La validación del asiento protegido debe ocurrir antes de la primera escritura.
        // Por tanto, el rechazo no necesita provocar un rollback de savepoint para
        // garantizar que no haya cambios parciales.
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsientoMovible,
            'id_sala' => $idSala,
            'fila' => 'A',
            'numero' => 1,
        ]);
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsientoProtegido,
            'id_sala' => $idSala,
            'fila' => 'A',
            'numero' => 2,
        ]);
        $this->assertDatabaseCount('asiento', 2);
    }

    public function test_guardado_rechaza_un_id_positivo_ajeno_a_la_sala_sin_escrituras(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos', [
                ['id' => $idAsiento, 'fila' => 'A', 'numero' => 1, 'tipo' => 'ACCESIBLE', 'activo' => 0, 'protegido' => false],
                ['id' => 987654, 'fila' => 'B', 'numero' => 1, 'tipo' => 'ESTANDAR', 'activo' => 1, 'protegido' => false],
            ])
            ->call('guardarDistribucion')
            ->assertHasErrors('distribucion');

        $this->assertDatabaseCount('asiento', 1);
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'fila' => 'A',
            'numero' => 1,
            'tipo' => 'ESTANDAR',
            'activo' => 1,
        ]);
    }

    public function test_guardado_rechaza_ids_repetidos_aunque_las_posiciones_sean_distintas(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos', [
                ['id' => $idAsiento, 'fila' => 'A', 'numero' => 1, 'tipo' => 'ESTANDAR', 'activo' => 1, 'protegido' => false],
                ['id' => $idAsiento, 'fila' => 'B', 'numero' => 1, 'tipo' => 'ACCESIBLE', 'activo' => 0, 'protegido' => false],
            ])
            ->call('guardarDistribucion')
            ->assertHasErrors('distribucion');

        $this->assertDatabaseCount('asiento', 1);
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'fila' => 'A',
            'numero' => 1,
            'tipo' => 'ESTANDAR',
            'activo' => 1,
        ]);
    }

    public function test_guardado_rechaza_ids_temporales_repetidos(): void
    {
        $idSala = $this->crearSala();

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos', [
                ['id' => -1, 'fila' => 'A', 'numero' => 1, 'tipo' => 'ESTANDAR', 'activo' => 1, 'protegido' => false],
                ['id' => -1, 'fila' => 'B', 'numero' => 1, 'tipo' => 'ESTANDAR', 'activo' => 1, 'protegido' => false],
            ])
            ->call('guardarDistribucion')
            ->assertHasErrors('distribucion');

        $this->assertDatabaseCount('asiento', 0);
    }

    public function test_guardado_rechaza_duplicados_sin_persistir_parte_del_borrador(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);
        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos', [
                // Este cambio sería válido por sí solo y no debe persistirse
                // si la distribución completa resulta inválida.
                ['id' => $idAsiento, 'fila' => 'A', 'numero' => 1, 'tipo' => 'ACCESIBLE', 'activo' => 0, 'protegido' => false],
                // La posición duplicada debe invalidar todo el borrador.
                ['id' => -1, 'fila' => 'A', 'numero' => 1, 'tipo' => 'ESTANDAR', 'activo' => 1, 'protegido' => false],
            ])
            ->call('guardarDistribucion')->assertHasErrors('distribucion');

        $this->assertDatabaseCount('asiento', 1);
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'id_sala' => $idSala,
            'fila' => 'A',
            'numero' => 1,
            'tipo' => 'ESTANDAR',
            'activo' => 1,
        ]);
        $this->assertDatabaseMissing('asiento', ['id_sala' => $idSala, 'numero' => 2]);
        $this->assertDatabaseCount('sala_pasillo', 0);
    }

    public function test_guardado_rechaza_cambios_concurrentes_en_asientos_sin_sobrescribirlos(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala, ['numero' => 1]);

        $component = Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos.0.activo', 0);

        // Simula una modificación realizada por otra sesión después de abrir el editor.
        DB::table('asiento')->where('id_asiento', $idAsiento)->update(['tipo' => 'PREFERENCIAL']);

        $component->call('guardarDistribucion')->assertHasErrors('distribucion');

        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'tipo' => 'PREFERENCIAL',
            'activo' => 1,
        ]);
    }

    public function test_guardado_rechaza_cambios_concurrentes_en_pasillos_sin_persistir_el_borrador(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala, ['numero' => 1]);
        DB::table('sala_pasillo')->insert([
            'id_sala' => $idSala,
            'fila' => 'A',
            'despues_de_numero' => 1,
            'orientacion' => 'LONGITUDINAL',
            'fila_siguiente' => null,
        ]);

        $component = Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos.0.activo', 0);

        // Otra sesión cambia el pasillo mientras el borrador sigue abierto.
        DB::table('sala_pasillo')->where('id_sala', $idSala)->update(['despues_de_numero' => 2]);

        $component->call('guardarDistribucion')->assertHasErrors('distribucion');

        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'activo' => 1,
        ]);
        $this->assertDatabaseHas('sala_pasillo', [
            'id_sala' => $idSala,
            'fila' => 'A',
            'despues_de_numero' => 2,
        ]);
    }

    public function test_cancelar_reinicia_el_borrador_y_los_datos_de_edicion_masiva(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos.0.activo', 0)
            ->set('filaMasiva', 'Z')
            ->set('numeroInicioMasivo', 4)
            ->set('numeroFinMasivo', 8)
            ->set('tipoMasivo', 'ACCESIBLE')
            ->set('activoMasivo', '0')
            ->call('cancelarDistribucion')
            ->assertHasNoErrors()
            ->assertSet('hayCambiosPendientes', false)
            ->assertSet('filaMasiva', 'A')
            ->assertSet('numeroInicioMasivo', 1)
            ->assertSet('numeroFinMasivo', 10)
            ->assertSet('tipoMasivo', 'ESTANDAR')
            ->assertSet('activoMasivo', '1')
            ->assertSet('borradorAsientos.0.activo', 1);

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento, 'activo' => 1]);
    }


    public function test_guardado_rechaza_pasillos_duplicados_sin_persistir_cambios_de_asientos(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos.0.activo', 0)
            ->set('borradorPasillos', [
                ['id_pasillo' => null, 'id_sala' => $idSala, 'fila' => 'A', 'despues_de_numero' => 1, 'orientacion' => 'LONGITUDINAL', 'fila_siguiente' => null],
                ['id_pasillo' => null, 'id_sala' => $idSala, 'fila' => 'A', 'despues_de_numero' => 1, 'orientacion' => 'LONGITUDINAL', 'fila_siguiente' => null],
            ])
            ->call('guardarDistribucion')
            ->assertHasErrors('distribucion');

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento, 'activo' => 1]);
        $this->assertDatabaseCount('sala_pasillo', 0);
    }

    public function test_guardado_rechaza_filas_no_canonicas_en_el_borrador(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos.0.fila', ' a ')
            ->call('guardarDistribucion')
            ->assertHasErrors('distribucion');

        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'fila' => 'A',
            'numero' => 1,
        ]);
    }

    public function test_guardado_rechaza_ubicacion_de_pasillo_no_entera(): void
    {
        $idSala = $this->crearSala();
        $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorPasillos', [
                ['id_pasillo' => null, 'id_sala' => $idSala, 'fila' => 'A', 'despues_de_numero' => 1.5, 'orientacion' => 'LONGITUDINAL', 'fila_siguiente' => null],
            ])
            ->call('guardarDistribucion')
            ->assertHasErrors('distribucion');

        $this->assertDatabaseCount('sala_pasillo', 0);
    }


    public function test_guardado_rechaza_un_asiento_de_otra_sala_sin_modificar_ninguna_sala(): void
    {
        $idSala = $this->crearSala('Sala principal');
        $idAsiento = $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);
        $idOtraSala = $this->crearSala('Sala ajena');
        $idAsientoAjeno = $this->crearAsiento($idOtraSala, ['fila' => 'Z', 'numero' => 9]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos', [
                ['id' => $idAsiento, 'fila' => 'A', 'numero' => 1, 'tipo' => 'ACCESIBLE', 'activo' => 0, 'protegido' => false],
                ['id' => $idAsientoAjeno, 'fila' => 'Z', 'numero' => 9, 'tipo' => 'ESTANDAR', 'activo' => 1, 'protegido' => false],
            ])
            ->call('guardarDistribucion')
            ->assertHasErrors('distribucion');

        $this->assertDatabaseCount('asiento', 2);
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'id_sala' => $idSala,
            'fila' => 'A',
            'numero' => 1,
            'tipo' => 'ESTANDAR',
            'activo' => 1,
        ]);
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsientoAjeno,
            'id_sala' => $idOtraSala,
            'fila' => 'Z',
            'numero' => 9,
        ]);
    }

    public function test_guardado_rechaza_registro_de_asiento_mal_formado_sin_escrituras(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos', [
                ['id' => $idAsiento, 'fila' => 'A', 'numero' => 1, 'tipo' => 'ACCESIBLE', 'activo' => 0, 'protegido' => false],
                ['id' => -1],
            ])
            ->call('guardarDistribucion')
            ->assertHasErrors('distribucion');

        $this->assertDatabaseCount('asiento', 1);
        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'fila' => 'A',
            'numero' => 1,
            'tipo' => 'ESTANDAR',
            'activo' => 1,
        ]);
    }

    public function test_cancelar_descarta_pasillos_longitudinales_y_transversales_del_borrador(): void
    {
        $idSala = $this->crearSala();
        $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);
        $this->crearAsiento($idSala, ['fila' => 'B', 'numero' => 1]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('alternarPasillo', 'A', 1)
            ->assertHasNoErrors()
            ->call('alternarPasilloTransversal', 'A', 'B')
            ->assertHasNoErrors()
            ->assertSet('hayCambiosPendientes', true)
            ->call('cancelarDistribucion')
            ->assertHasNoErrors()
            ->assertSet('hayCambiosPendientes', false)
            ->assertSet('borradorPasillos', []);

        $this->assertDatabaseCount('sala_pasillo', 0);
        $this->assertDatabaseCount('asiento', 2);
    }

    public function test_guardado_valido_actualiza_el_estado_original_y_permite_una_siguiente_edicion(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        $component = Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->set('borradorAsientos.0.activo', 0)
            ->call('guardarDistribucion')
            ->assertHasNoErrors()
            ->assertSet('hayCambiosPendientes', false)
            ->assertSet('asientosOriginales.0.activo', 0)
            ->assertSet('borradorAsientos.0.activo', 0);

        $component->set('borradorAsientos.0.tipo', 'ACCESIBLE')
            ->call('guardarDistribucion')
            ->assertHasNoErrors()
            ->assertSet('hayCambiosPendientes', false);

        $this->assertDatabaseHas('asiento', [
            'id_asiento' => $idAsiento,
            'tipo' => 'ACCESIBLE',
            'activo' => 0,
        ]);
    }

    public function test_muestra_barra_contextual_para_un_asiento_seleccionado(): void
    {
        $idSala = $this->crearSala();
        $this->crearAsiento($idSala, ['fila' => 'B', 'numero' => 4]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('seleccionarCelda', 'B', 4)
            ->assertSee('Acciones contextuales del asiento seleccionado')
            ->assertSee('Tipo')
            ->assertSee('Disponibilidad')
            ->assertSee('Más detalles');
    }

    public function test_muestra_barra_contextual_para_seleccion_multiple(): void
    {
        $idSala = $this->crearSala();
        $this->crearAsiento($idSala, ['fila' => 'A', 'numero' => 1]);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('activarSeleccionMultiple')
            ->call('seleccionarCelda', 'A', 1)
            ->assertSee('Acciones contextuales de la selección múltiple')
            ->assertSee('Editar selección')
            ->assertSee('Terminar selección');
    }

    public function test_pincel_cambia_el_tipo_en_el_borrador_y_solo_persiste_al_guardar(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('pintarAsiento', $idAsiento, 'tipo', 'PREFERENCIAL')
            ->assertHasNoErrors()
            ->assertSet('hayCambiosPendientes', true)
            ->assertSet('borradorAsientos.0.tipo', 'PREFERENCIAL');

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento, 'tipo' => 'ESTANDAR']);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('pintarAsiento', $idAsiento, 'tipo', 'PREFERENCIAL')
            ->call('guardarDistribucion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento, 'tipo' => 'PREFERENCIAL']);
    }

    public function test_pincel_no_permite_cambiar_tipo_de_asiento_con_historial(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);
        $this->asociarAFuncion($idSala, $idAsiento);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('pintarAsiento', $idAsiento, 'tipo', 'ACCESIBLE')
            ->assertHasErrors(['distribucion']);

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento, 'tipo' => 'ESTANDAR']);
    }

    public function test_pincel_si_permite_cambiar_disponibilidad_de_asiento_con_historial(): void
    {
        $idSala = $this->crearSala();
        $idAsiento = $this->crearAsiento($idSala);
        $this->asociarAFuncion($idSala, $idAsiento);

        Livewire::test(EditorDistribucionSala::class, ['salaId' => $idSala])
            ->call('pintarAsiento', $idAsiento, 'activo', '0')
            ->assertHasNoErrors()
            ->assertSet('borradorAsientos.0.activo', 0)
            ->call('guardarDistribucion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asiento', ['id_asiento' => $idAsiento, 'activo' => 0]);
    }



    public function test_eliminar_asiento_del_borrador_no_requiere_confirmacion_adicional(): void
    {
        $view = file_get_contents(resource_path('views/livewire/admin/salas/editor-distribucion-sala.blade.php'));

        $this->assertStringContainsString('wire:click="eliminarAsiento"', $view);
        $this->assertStringNotContainsString('wire:click="eliminarAsiento" wire:confirm=', $view);
        $this->assertStringContainsString('Quitar del borrador', $view);
    }

    public function test_cancelar_toda_la_distribucion_conserva_confirmacion_por_ser_destructivo(): void
    {
        $view = file_get_contents(resource_path('views/livewire/admin/salas/editor-distribucion-sala.blade.php'));

        $this->assertStringContainsString('wire:click="cancelarDistribucion"', $view);
        $this->assertStringContainsString('wire:confirm="¿Descartar todos los cambios pendientes', $view);
    }

    public function test_mensajes_contextuales_se_muestran_cerca_del_estado_de_guardado(): void
    {
        $view = file_get_contents(resource_path('views/livewire/admin/salas/editor-distribucion-sala.blade.php'));

        $this->assertStringContainsString('editor-mensaje.window', $view);
        $this->assertStringContainsString('dist-feedback--visible', $view);
        $this->assertStringContainsString('Aplicar al borrador', $view);
        $this->assertStringContainsString('Crear en el borrador', $view);
    }

}
