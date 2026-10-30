<?php

namespace Tests\Feature\Admin;

use App\Models\Pelicula;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeliculaCreditosEditorTest extends TestCase
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

    private function pelicula(): Pelicula
    {
        return Pelicula::query()->create([
            'titulo' => 'Película para créditos',
            'sinopsis' => 'Sinopsis para probar la pestaña de créditos.',
            'estado' => 'BORRADOR',
        ]);
    }

    private function persona(string $nombre = 'Persona de prueba'): int
    {
        return (int) DB::table('persona')->insertGetId([
            'nombre_completo' => $nombre,
            'nombre_busqueda' => $nombre,
            'foto' => null,
        ], 'id_persona');
    }

    public function test_la_pestana_creditos_se_muestra_dentro_del_editor(): void
    {
        $pelicula = $this->pelicula();
        $persona = $this->persona('Ana Actriz');
        DB::table('pelicula_credito')->insert([
            'id_pelicula' => $pelicula->id_pelicula,
            'id_persona' => $persona,
            'rol' => 'ACTOR',
            'personaje' => 'Luna',
            'orden' => 1,
        ]);

        $this->get(route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos']))
            ->assertOk()
            ->assertSee('Créditos de la película')
            ->assertSee('Ana Actriz')
            ->assertSee('Luna')
            ->assertSee('Asignar crédito');
    }

    public function test_editar_credito_se_presenta_en_un_modal_y_no_como_seccion(): void
    {
        $pelicula = $this->pelicula();
        $persona = $this->persona('Ana Actriz');
        DB::table('pelicula_credito')->insert([
            'id_pelicula' => $pelicula->id_pelicula,
            'id_persona' => $persona,
            'rol' => 'ACTOR',
            'personaje' => 'Luna',
            'orden' => 1,
        ]);

        $this->get(route('admin.peliculas.edit', [
            'pelicula' => $pelicula,
            'tab' => 'creditos',
            'credito_accion' => 'editar',
            'credito_persona' => $persona,
            'credito_rol' => 'ACTOR',
        ]))
            ->assertOk()
            ->assertSee('id="modalEditarCredito"', false)
            ->assertSee('Editar crédito')
            ->assertSee('value="Ana Actriz"', false)
            ->assertDontSee('class="form-cm mt-4"', false);
    }

    public function test_puede_asignar_un_credito_y_permanece_en_la_pestana(): void
    {
        $pelicula = $this->pelicula();
        $persona = $this->persona();

        $this->post(route('admin.peliculas.creditos.store', $pelicula), [
            'id_persona' => $persona,
            'rol' => 'ACTOR',
            'personaje' => 'Luna',
            'orden' => 2,
        ])->assertRedirect(route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pelicula_credito', [
            'id_pelicula' => $pelicula->id_pelicula,
            'id_persona' => $persona,
            'rol' => 'ACTOR',
            'personaje' => 'Luna',
            'orden' => 2,
        ]);
    }

    public function test_no_permite_repetir_la_misma_persona_y_rol(): void
    {
        $pelicula = $this->pelicula();
        $persona = $this->persona();
        DB::table('pelicula_credito')->insert([
            'id_pelicula' => $pelicula->id_pelicula,
            'id_persona' => $persona,
            'rol' => 'ACTOR',
            'personaje' => null,
            'orden' => 0,
        ]);

        $this->from(route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos', 'credito_accion' => 'crear']))
            ->post(route('admin.peliculas.creditos.store', $pelicula), [
                'id_persona' => $persona,
                'rol' => 'ACTOR',
                'personaje' => null,
                'orden' => 0,
            ])
            ->assertSessionHasErrors('rol');

        $this->assertDatabaseCount('pelicula_credito', 1);
    }

    public function test_puede_editar_y_eliminar_creditos_desde_el_editor(): void
    {
        $pelicula = $this->pelicula();
        $persona = $this->persona();
        DB::table('pelicula_credito')->insert([
            'id_pelicula' => $pelicula->id_pelicula,
            'id_persona' => $persona,
            'rol' => 'ACTOR',
            'personaje' => 'Anterior',
            'orden' => 0,
        ]);

        $this->put(route('admin.peliculas.creditos.update', [$pelicula, $persona, 'ACTOR']), [
            'id_persona' => $persona,
            'rol' => 'ACTOR',
            'personaje' => 'Actualizado',
            'orden' => 3,
        ])->assertRedirect(route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pelicula_credito', [
            'id_pelicula' => $pelicula->id_pelicula,
            'id_persona' => $persona,
            'rol' => 'ACTOR',
            'personaje' => 'Actualizado',
            'orden' => 3,
        ]);

        $this->delete(route('admin.peliculas.creditos.destroy', [$pelicula, $persona, 'ACTOR']))
            ->assertRedirect(route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos']));

        $this->assertDatabaseMissing('pelicula_credito', [
            'id_pelicula' => $pelicula->id_pelicula,
            'id_persona' => $persona,
            'rol' => 'ACTOR',
        ]);
    }

    public function test_busqueda_de_personas_devuelve_coincidencias_sin_cargar_todo_el_catalogo(): void
    {
        $pelicula = $this->pelicula();
        $persona = $this->persona('Ana Actriz');
        $this->persona('Bruno Director');

        $this->getJson(route('admin.peliculas.creditos.personas.buscar', [
            'pelicula' => $pelicula,
            'q' => 'Ana',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'resultados')
            ->assertJsonPath('resultados.0.id', $persona)
            ->assertJsonPath('resultados.0.nombre', 'Ana Actriz');
    }

    public function test_registro_rapido_crea_persona_y_credito_en_una_sola_operacion(): void
    {
        $pelicula = $this->pelicula();

        $respuesta = $this->postJson(
            route('admin.peliculas.creditos.personas.crear-rapido', $pelicula),
            [
                'nombre_completo' => '  Nueva Actriz  ',
                'nombre_busqueda' => 'Nueva A.',
                'url_foto' => '',
                'rol' => 'ACTOR',
                'personaje' => 'Luna',
                'orden' => 2,
            ]
        );

        $respuesta->assertCreated()
            ->assertJsonPath('message', 'Persona registrada y crédito asignado correctamente.')
            ->assertJsonPath('persona.nombre', 'Nueva Actriz')
            ->assertJsonPath('redirect', route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos']));

        $this->assertDatabaseHas('persona', [
            'nombre_completo' => 'Nueva Actriz',
            'nombre_busqueda' => 'Nueva A.',
            'foto' => null,
        ]);
        $this->assertDatabaseHas('pelicula_credito', [
            'id_pelicula' => $pelicula->id_pelicula,
            'id_persona' => $respuesta->json('persona.id'),
            'rol' => 'ACTOR',
            'personaje' => 'Luna',
            'orden' => 2,
        ]);
    }

    public function test_registro_rapido_exige_un_rol_valido(): void
    {
        $pelicula = $this->pelicula();

        $this->postJson(
            route('admin.peliculas.creditos.personas.crear-rapido', $pelicula),
            [
                'nombre_completo' => 'Persona sin rol',
                'rol' => '',
            ]
        )->assertUnprocessable()->assertJsonValidationErrors('rol');

        $this->assertDatabaseCount('persona', 0);
        $this->assertDatabaseCount('pelicula_credito', 0);
    }

    public function test_registro_rapido_advierte_si_ya_existe_el_nombre_y_no_duplica(): void
    {
        $pelicula = $this->pelicula();
        $persona = $this->persona('Ana Actriz');

        $this->postJson(
            route('admin.peliculas.creditos.personas.crear-rapido', $pelicula),
            [
                'nombre_completo' => ' ana actriz ',
                'rol' => 'ACTOR',
                'personaje' => 'Luna',
                'orden' => 0,
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath('persona_existente.id', $persona);

        $this->assertDatabaseCount('persona', 1);
        $this->assertDatabaseCount('pelicula_credito', 0);
    }
}
