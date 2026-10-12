<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genero;
use App\Models\Idioma;
use App\Models\Pelicula;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PeliculaAdminController extends Controller
{
    /**
     * Clasificaciones permitidas por el esquema v2.
     */
    private const CLASIFICACIONES = [
        'G',
        'PG',
        'PG-13',
        'R',
        'NC-17',
    ];

    /**
     * Estados admitidos por el esquema físico.
     */
    private const ESTADOS = [
        'BORRADOR',
        'ACTIVA',
        'INACTIVA',
    ];

    /**
     * Mostrar el catálogo administrativo.
     */
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:150',
            ],
            'estado' => [
                'nullable',
                Rule::in(self::ESTADOS),
            ],
            'clasificacion' => [
                'nullable',
                Rule::in(self::CLASIFICACIONES),
            ],
        ]);

        $peliculas = Pelicula::query()
            ->with('generoPrincipal')
            ->when(
                $filtros['q'] ?? null,
                fn($query, $titulo) => $query->where(
                    'titulo',
                    'like',
                    '%' . $titulo . '%'
                )
            )
            ->when(
                $filtros['estado'] ?? null,
                fn($query, $estado) => $query->where(
                    'estado',
                    $estado
                )
            )
            ->when(
                $filtros['clasificacion'] ?? null,
                fn($query, $clasificacion) => $query->where(
                    'clasificacion',
                    $clasificacion
                )
            )
            ->orderBy('titulo')
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.peliculas.index',
            compact('peliculas')
        );
    }

    /**
     * Mostrar el formulario de creación.
     */
    public function create()
    {
        return view('admin.peliculas.form', [
            'pelicula' => new Pelicula(),
            'generos' => $this->generosDisponibles(),
            'idiomas' => $this->idiomasDisponibles(),
            'modo' => 'crear',
        ]);
    }

    /**
     * Registrar una película como borrador.
     */
    public function store(Request $request)
    {
        $datos = $request->validate(
            $this->reglas()
        );

        // El cliente no decide el estado inicial.
        $datos['estado'] = 'BORRADOR';

        Pelicula::create($datos);

        return redirect()
            ->route('admin.peliculas.index')
            ->with(
                'success',
                'Película registrada como borrador correctamente.'
            );
    }

    /**
     * Mostrar el formulario de edición.
     */
    public function edit(Pelicula $pelicula)
    {
        return view('admin.peliculas.form', [
            'pelicula' => $pelicula,
            'generos' => $this->generosDisponibles(
                $pelicula->id_genero_principal
            ),
            'idiomas' => $this->idiomasDisponibles(
                $pelicula->idioma_original_codigo
            ),
            'modo' => 'editar',
        ]);
    }

    /**
     * Actualizar únicamente los datos principales.
     *
     * El estado se conserva y no puede cambiarse desde
     * este formulario.
     */
    public function update(
        Request $request,
        Pelicula $pelicula
    ) {
        $datos = $request->validate(
            $this->reglas()
        );

        $pelicula->update($datos);

        return redirect()
            ->route('admin.peliculas.index')
            ->with(
                'success',
                'Película actualizada correctamente.'
            );
    }

    /**
     * Reglas para los datos principales.
     */
    private function reglas(): array
    {
        return [
            'titulo' => [
                'required',
                'string',
                'max:150',
            ],

            'sinopsis' => [
                'required',
                'string',
                'max:65535',
            ],

            'id_genero_principal' => [
                'nullable',
                'integer',
                Rule::exists('genero', 'id_genero')
                    ->where('activo', 1),
            ],

            'clasificacion' => [
                'nullable',
                Rule::in(self::CLASIFICACIONES),
            ],

            'duracion_minutos' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'fecha_estreno' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'idioma_original_codigo' => [
                'nullable',
                'string',
                'max:15',
                Rule::exists('idioma', 'codigo')
                    ->where('activo', 1),
            ],
            'estado' => ['required', Rule::in(self::ESTADOS)],
        ];
    }

    /**
     * Obtener los géneros activos.
     *
     * Durante la edición se incluye el género actual aunque
     * haya sido desactivado posteriormente, para no perder
     * la selección existente.
     */
    private function generosDisponibles($idActual = null)
    {
        return Genero::query()
            ->where(function ($query) use ($idActual) {
                $query->where('activo', 1);

                if ($idActual !== null) {
                    $query->orWhere(
                        'id_genero',
                        $idActual
                    );
                }
            })
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtener los idiomas activos.
     *
     * Durante la edición se conserva visible el idioma actual,
     * aunque haya sido desactivado posteriormente.
     */
    private function idiomasDisponibles($codigoActual = null)
    {
        return Idioma::query()
            ->where(function ($query) use ($codigoActual) {
                $query->where('activo', 1);

                if ($codigoActual !== null) {
                    $query->orWhere(
                        'codigo',
                        $codigoActual
                    );
                }
            })
            ->orderBy('nombre')
            ->get();
    }
}
