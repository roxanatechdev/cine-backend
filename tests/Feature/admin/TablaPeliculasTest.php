<?php

namespace Tests\Feature\Admin;

use App\livewire\admin\peliculas\TablaPeliculas;
use App\Models\Pelicula;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Models\Genero;
use App\Models\Idioma;


class TablaPeliculasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea una película para las pruebas.
     */
    private function crearPelicula(
        string $titulo,
        string $estado = 'BORRADOR',
        ?string $clasificacion = 'PG-13'
    ): Pelicula {
        return Pelicula::query()->create([
            'titulo' => $titulo,
            'sinopsis' => "Sinopsis de {$titulo}",
            'estado' => $estado,
            'clasificacion' => $clasificacion,
        ]);
    }

    public function test_renderiza_el_componente_y_muestra_las_peliculas(): void
    {
        $this->crearPelicula('Película de prueba');

        Livewire::test(TablaPeliculas::class)
            ->assertStatus(200)
            ->assertSee('Película de prueba');
    }

    public function test_busqueda_filtra_por_titulo(): void
    {
        $this->crearPelicula('Aventura espacial');
        $this->crearPelicula('Drama familiar');

        Livewire::test(TablaPeliculas::class)
            ->set('q', 'Aventura')
            ->assertSee('Aventura espacial')
            ->assertDontSee('Drama familiar');
    }

    public function test_filtra_por_estado_y_clasificacion(): void
    {
        $this->crearPelicula(
            'Película activa PG',
            'ACTIVA',
            'PG'
        );

        $this->crearPelicula(
            'Película activa R',
            'ACTIVA',
            'R'
        );

        $this->crearPelicula(
            'Película borrador PG',
            'BORRADOR',
            'PG'
        );

        Livewire::test(TablaPeliculas::class)
            ->set('estado', 'ACTIVA')
            ->set('clasificacion', 'PG')
            ->assertSee('Película activa PG')
            ->assertDontSee('Película activa R')
            ->assertDontSee('Película borrador PG');
    }

    public function test_limpiar_filtros_restablece_los_valores(): void
    {
        $this->crearPelicula('Película activa', 'ACTIVA', 'PG');
        $this->crearPelicula('Película borrador', 'BORRADOR', 'R');

        Livewire::test(TablaPeliculas::class)
            ->set('q', 'inexistente')
            ->set('estado', 'ACTIVA')
            ->set('clasificacion', 'PG')
            ->call('limpiarFiltros')
            ->assertSet('q', '')
            ->assertSet('estado', '')
            ->assertSet('clasificacion', '')
            ->assertSee('Película activa')
            ->assertSee('Película borrador');
    }

    public function test_permite_ordenar_por_titulo_en_ambas_direcciones(): void
    {
        $this->crearPelicula('Zeta');
        $this->crearPelicula('Alfa');

        Livewire::test(TablaPeliculas::class)
            ->call('sortBy', 'titulo')
            ->assertSet('sort', 'titulo')
            ->assertSet('direction', 'asc')
            ->assertSeeInOrder(['Alfa', 'Zeta'])
            ->call('sortBy', 'titulo')
            ->assertSet('direction', 'desc')
            ->assertSeeInOrder(['Zeta', 'Alfa']);
    }

    public function test_rechaza_columnas_de_ordenamiento_no_permitidas(): void
    {
        Livewire::test(TablaPeliculas::class)
            ->call('sortBy', 'columna_invalida')
            ->assertSet('sort', 'id_pelicula')
            ->assertSet('direction', 'desc');
    }



    public function test_cambiar_tamano_de_pagina_reinicia_la_paginacion(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->crearPelicula(sprintf('Película %02d', $i));
        }

        Livewire::test(TablaPeliculas::class)
            ->assertSet('perPage', 10)
            ->call('setPage', 2)
            ->assertSee('Película 20')
            ->assertSee('Película 11')
            ->assertDontSee('Película 30')
            ->set('perPage', 25)
            ->assertSet('perPage', 25)
            ->assertSee('Película 30')
            ->assertSee('Película 06')
            ->assertDontSee('Película 05');
    }

    public function test_desactiva_una_pelicula_activa(): void
    {
        $pelicula = $this->crearPelicula(
            'Película para desactivar',
            'ACTIVA'
        );

        Livewire::test(TablaPeliculas::class)
            ->call('desactivarPelicula', $pelicula->id_pelicula)
            ->assertSee(
                'La película «Película para desactivar» ha sido desactivada correctamente.'
            );

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'INACTIVA',
        ]);
    }


    public function test_no_modifica_una_pelicula_que_no_esta_activa(): void
    {
        $pelicula = $this->crearPelicula(
            'Película en borrador',
            'BORRADOR'
        );

        Livewire::test(TablaPeliculas::class)
            ->call('desactivarPelicula', $pelicula->id_pelicula);

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'BORRADOR',
        ]);
    }

    public function test_combina_busqueda_estado_y_clasificacion(): void
    {
        $this->crearPelicula(
            'Aventura espacial',
            'ACTIVA',
            'PG-13'
        );

        $this->crearPelicula(
            'Aventura familiar',
            'BORRADOR',
            'PG-13'
        );

        $this->crearPelicula(
            'Drama espacial',
            'ACTIVA',
            'PG-13'
        );

        $this->crearPelicula(
            'Aventura nocturna',
            'ACTIVA',
            'R'
        );

        Livewire::test(TablaPeliculas::class)
            ->set('q', 'Aventura')
            ->set('estado', 'ACTIVA')
            ->set('clasificacion', 'PG-13')
            ->assertSee('Aventura espacial')
            ->assertDontSee('Aventura familiar')
            ->assertDontSee('Drama espacial')
            ->assertDontSee('Aventura nocturna');
    }

    public function test_rechaza_valores_invalidos_en_los_filtros(): void
    {
        $this->crearPelicula(
            'Película de prueba',
            'ACTIVA',
            'PG'
        );

        Livewire::test(TablaPeliculas::class)
            ->set('estado', 'ESTADO_INVALIDO')
            ->assertSet('estado', '')
            ->set('clasificacion', 'CLASIFICACION_INVALIDA')
            ->assertSet('clasificacion', '')
            ->assertSee('Película de prueba');
    }

    public function test_cambiar_filtros_reinicia_la_paginacion(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->crearPelicula(
                sprintf('Película %02d', $i),
                $i <= 15 ? 'ACTIVA' : 'BORRADOR',
                'PG'
            );
        }

        Livewire::test(TablaPeliculas::class)
            ->call('setPage', 2)
            ->assertSee('Película 20')
            ->set('estado', 'ACTIVA')
            ->assertSee('Película 15')
            ->assertSee('Película 06')
            ->assertDontSee('Película 20');
    }


    public function test_no_activa_una_pelicula_sin_genero_principal_activo(): void
    {
        $pelicula = $this->crearPelicula(
            'Película sin género',
            'BORRADOR'
        );

        Livewire::test(TablaPeliculas::class)
            ->call('activarPelicula', $pelicula->id_pelicula)
            ->assertSee(
                'No se puede activar la película: Asigna un género principal activo.'
            );

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'BORRADOR',
        ]);
    }


    public function test_activa_una_pelicula_que_cumple_los_requisitos(): void
    {
        $genero = Genero::query()->create([
            'nombre' => 'Drama',
            'activo' => 1,
        ]);

        $idioma = Idioma::query()->create([
            'codigo' => 'es',
            'nombre' => 'Español',
            'activo' => 1,
        ]);

        $pelicula = $this->crearPelicula(
            'Película lista para publicar',
            'BORRADOR'
        );

        $pelicula->update([
            'id_genero_principal' => $genero->id_genero,
            'idioma_original_codigo' => $idioma->codigo,
        ]);

        $pelicula->multimedia()->create([
            'tipo' => 'POSTER',
            'url' => 'posters/pelicula-prueba.jpg',
            'es_principal' => 1,
            'activo' => 1,
            'orden' => 0,
        ]);

        Livewire::test(TablaPeliculas::class)
            ->call('activarPelicula', $pelicula->id_pelicula)
            ->assertSee(
                'La película «Película lista para publicar» está activa.'
            );

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'ACTIVA',
        ]);
    }


    public function test_ordenamiento_por_genero_es_alfabetico(): void
    {
        $accion = Genero::query()->create([
            'nombre' => 'Acción',
            'activo' => 1,
        ]);

        $drama = Genero::query()->create([
            'nombre' => 'Drama',
            'activo' => 1,
        ]);

        $peliculaDrama = $this->crearPelicula('Película Drama');
        $peliculaDrama->update([
            'id_genero_principal' => $drama->id_genero,
        ]);

        $peliculaAccion = $this->crearPelicula('Película Acción');
        $peliculaAccion->update([
            'id_genero_principal' => $accion->id_genero,
        ]);

        $componente = Livewire::test(TablaPeliculas::class)
            ->call('sortBy', 'genero');

        $componente
            ->assertSee('Película Acción')
            ->assertSee('Película Drama');

        $html = $componente->html();

        $posicionAccion = strpos($html, 'Película Acción');
        $posicionDrama = strpos($html, 'Película Drama');

        $this->assertNotFalse($posicionAccion);
        $this->assertNotFalse($posicionDrama);
        $this->assertLessThan($posicionDrama, $posicionAccion);
    }


    public function test_ordenamiento_por_fecha_de_estreno_es_correcto(): void
    {
        $posterior = $this->crearPelicula('Estreno posterior');
        $posterior->update(['fecha_estreno' => '2026-12-01']);

        $anterior = $this->crearPelicula('Estreno anterior');
        $anterior->update(['fecha_estreno' => '2026-01-15']);

        Livewire::test(TablaPeliculas::class)
            ->call('sortBy', 'fecha_estreno')
            ->assertSet('direction', 'asc')
            ->assertSeeInOrder(['Estreno anterior', 'Estreno posterior']);
    }

    public function test_busqueda_reinicia_la_paginacion(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->crearPelicula(sprintf('Película %02d', $i));
        }

        Livewire::test(TablaPeliculas::class)
            ->call('setPage', 2)
            ->set('q', 'Película 01')
            ->assertSee('Película 01');
    }

    public function test_no_activa_una_pelicula_con_genero_inactivo(): void
    {
        $genero = Genero::query()->create([
            'nombre' => 'Género inactivo',
            'activo' => 0,
        ]);

        $pelicula = $this->crearPelicula('Película con género inactivo');
        $pelicula->update(['id_genero_principal' => $genero->id_genero]);

        Livewire::test(TablaPeliculas::class)
            ->call('activarPelicula', $pelicula->id_pelicula)
            ->assertSee('No se puede activar la película: Asigna un género principal activo.');

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'BORRADOR',
        ]);
    }

    public function test_no_activa_una_pelicula_sin_idioma_original_activo(): void
    {
        $genero = Genero::query()->create([
            'nombre' => 'Género válido',
            'activo' => 1,
        ]);
        $idioma = Idioma::query()->create([
            'codigo' => 'fr',
            'nombre' => 'Francés',
            'activo' => 0,
        ]);

        $pelicula = $this->crearPelicula('Película con idioma inactivo');
        $pelicula->update([
            'id_genero_principal' => $genero->id_genero,
            'idioma_original_codigo' => $idioma->codigo,
        ]);

        Livewire::test(TablaPeliculas::class)
            ->call('activarPelicula', $pelicula->id_pelicula)
            ->assertSee('No se puede activar la película: Asigna un idioma original activo.');

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'BORRADOR',
        ]);
    }

    public function test_no_activa_una_pelicula_sin_poster_principal_activo(): void
    {
        $genero = Genero::query()->create([
            'nombre' => 'Género para póster',
            'activo' => 1,
        ]);
        $idioma = Idioma::query()->create([
            'codigo' => 'pt',
            'nombre' => 'Portugués',
            'activo' => 1,
        ]);

        $pelicula = $this->crearPelicula('Película sin póster principal');
        $pelicula->update([
            'id_genero_principal' => $genero->id_genero,
            'idioma_original_codigo' => $idioma->codigo,
        ]);

        $pelicula->multimedia()->create([
            'tipo' => 'POSTER',
            'url' => 'posters/poster-inactivo.jpg',
            'es_principal' => 1,
            'activo' => 0,
            'orden' => 0,
        ]);

        Livewire::test(TablaPeliculas::class)
            ->call('activarPelicula', $pelicula->id_pelicula)
            ->assertSee('No se puede activar la película: Registra un póster activo y principal.');

        $this->assertDatabaseHas('pelicula', [
            'id_pelicula' => $pelicula->id_pelicula,
            'estado' => 'BORRADOR',
        ]);
    }
}
