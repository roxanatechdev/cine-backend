<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pelicula;
use App\Models\Persona;
use App\Models\PeliculaCredito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PeliculaCreditoAdminController extends Controller
{
    private const ROLES = [
        'DIRECTOR',
        'ACTOR',
        'GUIONISTA',
        'PRODUCTOR',
        'COMPOSITOR',
    ];

    /**
     * Listar los créditos de una película.
     */
    public function index(Pelicula $pelicula)
    {
        $creditos = PeliculaCredito::query()
            ->with('persona')
            ->where('id_pelicula', $pelicula->id_pelicula)
            ->orderBy('rol')
            ->orderBy('orden')
            ->orderBy('id_persona')
            ->paginate(15);

        return view('admin.creditos.index', compact(
            'pelicula',
            'creditos'
        ));
    }

    /**
     * Mostrar el formulario de registro.
     */
    public function create(Pelicula $pelicula)
    {
        $personas = Persona::query()
            ->orderBy('nombre_completo')
            ->get(['id_persona', 'nombre_completo']);

        return view('admin.creditos.form', [
            'pelicula' => $pelicula,
            'personas' => $personas,
            'credito' => new PeliculaCredito(),
            'modo' => 'crear',
            'personaSeleccionada' => null,
            'rolOriginal' => null,
        ]);
    }

    /**
     * Validación común de los datos del crédito.
     */
    private function reglas(): array
    {
        return [
            'id_persona' => [
                'required',
                'integer',
                'min:1',
                'exists:persona,id_persona',
            ],
            'rol' => [
                'required',
                'string',
                Rule::in(self::ROLES),
            ],
            'personaje' => [
                'nullable',
                'string',
                'max:150',
            ],
            'orden' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],
        ];
    }

    /**
     * Registrar un crédito.
     */
    public function store(Request $request, Pelicula $pelicula)
    {
        $datos = $request->validate($this->reglas());

        $existe = PeliculaCredito::query()
            ->where('id_pelicula', $pelicula->id_pelicula)
            ->where('id_persona', $datos['id_persona'])
            ->where('rol', $datos['rol'])
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'rol' => 'Esta persona ya tiene ese rol asignado a la película.',
            ]);
        }

        DB::transaction(function () use ($datos, $pelicula) {
            PeliculaCredito::query()->create([
                'id_pelicula' => $pelicula->id_pelicula,
                'id_persona' => (int) $datos['id_persona'],
                'rol' => $datos['rol'],
                'personaje' => $datos['personaje'] ?? null,
                'orden' => (int) ($datos['orden'] ?? 0),
            ]);
        });

        return redirect()
            ->route('admin.peliculas.creditos.index', $pelicula)
            ->with('success', 'Crédito registrado correctamente.');
    }

    /**
     * Mostrar el formulario de edición.
     */
    public function edit(
        Pelicula $pelicula,
        int $persona,
        string $rol
    ) {
        $credito = $this->buscarCredito(
            $pelicula,
            $persona,
            $rol
        );

        $personas = Persona::query()
            ->orderBy('nombre_completo')
            ->get(['id_persona', 'nombre_completo']);

        return view('admin.creditos.form', [
            'pelicula' => $pelicula,
            'personas' => $personas,
            'credito' => $credito,
            'modo' => 'editar',
            'personaSeleccionada' => $credito->id_persona,
            'rolOriginal' => $credito->rol,
        ]);
    }

    /**
     * Actualizar un crédito sin depender de save() sobre
     * una clave primaria compuesta.
     */
    public function update(
        Request $request,
        Pelicula $pelicula,
        int $persona,
        string $rol
    ) {
        $datos = $request->validate($this->reglas());

        DB::transaction(function () use (
            $datos,
            $pelicula,
            $persona,
            $rol
        ) {
            $actual = PeliculaCredito::query()
                ->where('id_pelicula', $pelicula->id_pelicula)
                ->where('id_persona', $persona)
                ->where('rol', $rol)
                ->lockForUpdate()
                ->firstOrFail();

            $idPersonaNueva = (int) $datos['id_persona'];
            $rolNuevo = $datos['rol'];

            $cambioClave =
                $idPersonaNueva !== (int) $actual->id_persona
                || $rolNuevo !== $actual->rol;

            if ($cambioClave) {
                $duplicado = PeliculaCredito::query()
                    ->where('id_pelicula', $pelicula->id_pelicula)
                    ->where('id_persona', $idPersonaNueva)
                    ->where('rol', $rolNuevo)
                    ->exists();

                if ($duplicado) {
                    throw ValidationException::withMessages([
                        'rol' => 'Ya existe un crédito con esa persona y ese rol en esta película.',
                    ]);
                }

                // Se elimina por las tres columnas originales.
                PeliculaCredito::query()
                    ->where('id_pelicula', $pelicula->id_pelicula)
                    ->where('id_persona', $persona)
                    ->where('rol', $rol)
                    ->delete();

                // Se inserta la nueva clave compuesta.
                PeliculaCredito::query()->create([
                    'id_pelicula' => $pelicula->id_pelicula,
                    'id_persona' => $idPersonaNueva,
                    'rol' => $rolNuevo,
                    'personaje' => $datos['personaje'] ?? null,
                    'orden' => (int) ($datos['orden'] ?? 0),
                ]);

                return;
            }

            // Actualizar solamente campos que no son la clave.
            PeliculaCredito::query()
                ->where('id_pelicula', $pelicula->id_pelicula)
                ->where('id_persona', $persona)
                ->where('rol', $rol)
                ->update([
                    'personaje' => $datos['personaje'] ?? null,
                    'orden' => (int) ($datos['orden'] ?? 0),
                ]);
        });

        return redirect()
            ->route('admin.peliculas.creditos.index', $pelicula)
            ->with('success', 'Crédito actualizado correctamente.');
    }

    /**
     * Eliminar exclusivamente la asignación del crédito.
     */
    public function destroy(
        Pelicula $pelicula,
        int $persona,
        string $rol
    ) {
        $eliminados = PeliculaCredito::query()
            ->where('id_pelicula', $pelicula->id_pelicula)
            ->where('id_persona', $persona)
            ->where('rol', $rol)
            ->delete();

        if ($eliminados === 0) {
            abort(404);
        }

        return redirect()
            ->route('admin.peliculas.creditos.index', $pelicula)
            ->with('success', 'Crédito eliminado correctamente.');
    }

    /**
     * Buscar un crédito mediante su clave compuesta.
     */
    private function buscarCredito(
        Pelicula $pelicula,
        int $persona,
        string $rol
    ): PeliculaCredito {
        abort_unless(in_array($rol, self::ROLES, true), 404);

        return PeliculaCredito::query()
            ->where('id_pelicula', $pelicula->id_pelicula)
            ->where('id_persona', $persona)
            ->where('rol', $rol)
            ->firstOrFail();
    }
}
