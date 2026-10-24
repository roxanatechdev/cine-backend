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
        return redirect()->route('admin.peliculas.edit', [
            'pelicula' => $pelicula,
            'tab' => 'creditos',
        ]);
    }

    /** Abrir el formulario integrado para asignar un crédito. */
    public function create(Pelicula $pelicula)
    {
        return redirect()->route('admin.peliculas.edit', [
            'pelicula' => $pelicula,
            'tab' => 'creditos',
            'credito_accion' => 'crear',
        ]);
    }

    /** Buscar coincidencias para el selector de personas del editor. */
    public function buscarPersonas(Request $request, Pelicula $pelicula)
    {
        $datos = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:180'],
        ]);
        $termino = trim($datos['q']);

        $personas = Persona::query()
            ->where(function ($query) use ($termino) {
                $query->where('nombre_completo', 'like', '%' . $termino . '%')
                    ->orWhere('nombre_busqueda', 'like', '%' . $termino . '%');
            })
            ->orderByRaw('CASE WHEN nombre_completo LIKE ? THEN 0 ELSE 1 END', [$termino . '%'])
            ->orderBy('nombre_completo')
            ->limit(8)
            ->get(['id_persona', 'nombre_completo', 'nombre_busqueda']);

        return response()->json([
            'resultados' => $personas->map(fn (Persona $persona) => [
                'id' => $persona->id_persona,
                'nombre' => $persona->nombre_completo,
                'nombre_busqueda' => $persona->nombre_busqueda,
            ]),
        ]);
    }

    /** Crear una persona y asignarla a la película en una sola transacción. */
    public function crearPersonaRapida(Request $request, Pelicula $pelicula)
    {
        $datos = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:180'],
            'nombre_busqueda' => ['nullable', 'string', 'max:180'],
            'url_foto' => [
                'nullable', 'string', 'max:255',
                function ($attribute, $value, $fail) {
                    if ($value === null || trim($value) === '') {
                        return;
                    }
                    $url = trim($value);
                    $parts = parse_url($url);
                    $valida = filter_var($url, FILTER_VALIDATE_URL)
                        && is_array($parts)
                        && strtolower($parts['scheme'] ?? '') === 'https'
                        && ! empty($parts['host'])
                        && ! isset($parts['user'])
                        && ! isset($parts['pass'])
                        && preg_match('/\.(jpg|jpeg|png|webp|avif)$/i', $parts['path'] ?? '') === 1;
                    if (! $valida) {
                        $fail('Introduce una URL HTTPS válida de imagen (JPG, PNG, WEBP o AVIF).');
                    }
                },
            ],
            'rol' => ['required', 'string', Rule::in(self::ROLES)],
            'personaje' => ['nullable', 'string', 'max:150'],
            'orden' => ['sometimes', 'required', 'integer', 'min:0', 'max:65535'],
        ]);

        $nombre = trim($datos['nombre_completo']);
        $duplicado = Persona::query()
            ->whereRaw('LOWER(TRIM(nombre_completo)) = ?', [mb_strtolower($nombre)])
            ->first(['id_persona', 'nombre_completo']);

        if ($duplicado) {
            return response()->json([
                'message' => 'Ya existe una persona con ese nombre. Selecciónala en los resultados para evitar duplicados.',
                'persona_existente' => [
                    'id' => $duplicado->id_persona,
                    'nombre' => $duplicado->nombre_completo,
                ],
            ], 422);
        }

        $persona = DB::transaction(function () use ($datos, $nombre, $pelicula) {
            $persona = Persona::query()->create([
                'nombre_completo' => $nombre,
                'nombre_busqueda' => isset($datos['nombre_busqueda']) && trim($datos['nombre_busqueda']) !== ''
                    ? trim($datos['nombre_busqueda'])
                    : null,
                'foto' => isset($datos['url_foto']) && trim($datos['url_foto']) !== ''
                    ? trim($datos['url_foto'])
                    : null,
            ]);

            $existe = PeliculaCredito::query()
                ->where('id_pelicula', $pelicula->id_pelicula)
                ->where('id_persona', $persona->id_persona)
                ->where('rol', $datos['rol'])
                ->exists();
            if ($existe) {
                throw ValidationException::withMessages([
                    'rol' => 'Esta persona ya tiene ese rol asignado a la película.',
                ]);
            }

            PeliculaCredito::query()->create([
                'id_pelicula' => $pelicula->id_pelicula,
                'id_persona' => $persona->id_persona,
                'rol' => $datos['rol'],
                'personaje' => isset($datos['personaje']) && trim((string) $datos['personaje']) !== ''
                    ? trim($datos['personaje'])
                    : null,
                'orden' => (int) ($datos['orden'] ?? 0),
            ]);

            return $persona;
        });

        return response()->json([
            'message' => 'Persona registrada y crédito asignado correctamente.',
            'redirect' => route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos']),
            'persona' => ['id' => $persona->id_persona, 'nombre' => $persona->nombre_completo],
        ], 201);
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
            ->route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos'])
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
        $this->buscarCredito($pelicula, $persona, $rol);

        return redirect()->route('admin.peliculas.edit', [
            'pelicula' => $pelicula,
            'tab' => 'creditos',
            'credito_accion' => 'editar',
            'credito_persona' => $persona,
            'credito_rol' => $rol,
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
            ->route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos'])
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
            ->route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'creditos'])
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
