<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genero;
use App\Models\Idioma;
use App\Models\Pelicula;
use App\Models\PeliculaCredito;
use App\Models\PeliculaMultimedia;
use App\Models\Persona;
use App\Models\Pais;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'q' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
            'clasificacion' => ['nullable', Rule::in(self::CLASIFICACIONES)],
            'sort' => ['nullable', Rule::in([
                'id_pelicula',
                'titulo',
                'genero',
                'clasificacion',
                'fecha_estreno',
                'estado',
                'duracion_minutos',
            ])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in([10, 25, 50, 100])],
        ]);

        // Orden y tamaño de página controlados por una lista permitida.
        $orden = $filtros['sort'] ?? 'id_pelicula';
        $direccion = $filtros['direction'] ?? 'desc';
        $porPagina = (int) ($filtros['per_page'] ?? 10);

        // Indicadores globales: no cambian al filtrar ni al paginar.
        $hoy = Carbon::today();

        $kpis = [
            'activas' => Pelicula::query()->where('estado', 'ACTIVA')->count(),
            'borradores' => Pelicula::query()->where('estado', 'BORRADOR')->count(),
            'proximos_estrenos' => Pelicula::query()
                ->where('estado', 'ACTIVA')
                ->whereDate('fecha_estreno', '>=', $hoy)
                ->count(),
            'duracion_promedio' => (int) round(
                Pelicula::query()->whereNotNull('duracion_minutos')->avg('duracion_minutos') ?? 0
            ),
        ];

        // Distribución global por clasificación, incluyendo registros incompletos.
        $clasificaciones = Pelicula::query()
            ->select('clasificacion', DB::raw('COUNT(*) as total'))
            ->groupBy('clasificacion')
            ->orderByRaw('clasificacion IS NULL DESC')
            ->orderBy('clasificacion')
            ->get()
            ->map(fn($fila) => [
                'nombre' => $fila->clasificacion ?: 'Sin clasificación',
                'total' => (int) $fila->total,
            ]);

        $totalClasificaciones = (int) $clasificaciones->sum('total');

        return view('admin.peliculas.index', compact(
            'kpis',
            'clasificaciones',
            'totalClasificaciones'
        ));
    }

    /**
     * Exportar el catálogo filtrado a CSV compatible con Excel en español.
     * La exportación incluye todos los resultados, no solo la página actual.
     */
    public function export(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
            'clasificacion' => ['nullable', Rule::in(self::CLASIFICACIONES)],
        ]);

        $consulta = Pelicula::query()
            ->with(['generoPrincipal', 'idiomaOriginal'])
            ->with(['multimedia' => function ($query) {
                $query->where('activo', 1)
                    ->whereIn('tipo', ['POSTER', 'IMAGEN'])
                    ->orderByRaw("CASE
                        WHEN tipo = 'POSTER' AND es_principal = 1 THEN 0
                        WHEN tipo = 'POSTER' THEN 1
                        WHEN tipo = 'IMAGEN' AND es_principal = 1 THEN 2
                        ELSE 3
                    END")
                    ->orderBy('orden')
                    ->orderBy('id_multimedia');
            }])
            ->when(
                $filtros['q'] ?? null,
                fn($query, $titulo) => $query->where('titulo', 'like', '%' . $titulo . '%')
            )
            ->when(
                $filtros['estado'] ?? null,
                fn($query, $estado) => $query->where('estado', $estado)
            )
            ->when(
                $filtros['clasificacion'] ?? null,
                fn($query, $clasificacion) => $query->where('clasificacion', $clasificacion)
            )
            ->orderBy('titulo');

        $nombreArchivo = 'cineprime-peliculas-' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($consulta) {
            $salida = fopen('php://output', 'w');

            // BOM UTF-8 para que Excel reconozca correctamente tildes y eñes.
            fwrite($salida, "\xEF\xBB\xBF");

            // Punto y coma: mejora la apertura directa en Excel con configuración regional española.
            fputcsv($salida, [
                'ID',
                'Título',
                'Estado',
                'Género principal',
                'Clasificación',
                'Duración (minutos)',
                'Fecha de estreno',
                'Idioma original (código)',
                'Idioma original',
                'URL de póster/imagen',
                'Sinopsis',
                'Creada el',
                'Actualizada el',
            ], ';', '"', '\\');

            // Evita que valores de texto se interpreten como fórmulas al abrir el CSV.
            $textoSeguro = static function ($valor): string {
                $valor = (string) ($valor ?? '');
                if ($valor !== '' && preg_match('/^[\s\x00-\x1F]*[=+@\-]/u', $valor)) {
                    return "'" . $valor;
                }
                return $valor;
            };

            $consulta->chunk(500, function ($peliculas) use ($salida, $textoSeguro) {
                foreach ($peliculas as $pelicula) {
                    $multimedia = $pelicula->multimedia->first();
                    $urlImagen = '';

                    if ($multimedia && filled($multimedia->url)) {
                        $url = trim($multimedia->url);
                        if (filter_var($url, FILTER_VALIDATE_URL)) {
                            $urlImagen = $url;
                        } else {
                            $ruta = ltrim($url, '/');
                            $urlImagen = str_starts_with($ruta, 'storage/')
                                ? asset($ruta)
                                : asset('storage/' . $ruta);
                        }
                    }

                    fputcsv($salida, [
                        $pelicula->id_pelicula,
                        $textoSeguro($pelicula->titulo),
                        $textoSeguro($pelicula->estado),
                        $textoSeguro($pelicula->generoPrincipal?->nombre),
                        $textoSeguro($pelicula->clasificacion),
                        $pelicula->duracion_minutos ?? '',
                        $pelicula->fecha_estreno?->format('Y-m-d') ?? '',
                        $textoSeguro($pelicula->idioma_original_codigo),
                        $textoSeguro($pelicula->idiomaOriginal?->nombre),
                        $textoSeguro($urlImagen),
                        $textoSeguro($pelicula->sinopsis),
                        $pelicula->creado_en?->format('Y-m-d H:i:s') ?? '',
                        $pelicula->actualizado_en?->format('Y-m-d H:i:s') ?? '',
                    ], ';', '"', '\\');
                }
            });

            fclose($salida);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
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
            $this->reglas('BORRADOR', true)
        );

        // Una película nueva siempre empieza como borrador.
        $datos['estado'] = 'BORRADOR';
        $this->validarCatalogosActivosAlAsignar($datos);

        $pelicula = Pelicula::create($datos);

        // Tras el primer guardado, continuar en el editor para completar las pestañas.
        return redirect()
            ->route('admin.peliculas.edit', $pelicula)
            ->with(
                'success',
                'Película creada como borrador. Ya puedes completar sus demás secciones.'
            );
    }

    /**
     * Mostrar el formulario de edición.
     */
    public function edit(Request $request, Pelicula $pelicula)
    {
        $tabSolicitada = (string) $request->query('tab', 'informacion');
        $pestana = in_array($tabSolicitada, ['informacion', 'creditos', 'multimedia', 'produccion'], true)
            ? $tabSolicitada
            : 'informacion';

        $multimedia = null;
        if ($pestana === 'multimedia') {
            $multimedia = $pelicula->multimedia()
                ->orderBy('tipo')
                ->orderBy('orden')
                ->orderBy('id_multimedia')
                ->paginate(10)
                ->withQueryString();
        }

        $paisesProduccion = collect();
        if ($pestana === 'produccion') {
            $paisesProduccion = $pelicula->paises()->orderBy('pais.nombre')->get(['pais.codigo', 'pais.nombre']);
        }

        $creditos = collect();
        $personas = collect();
        $credito = new PeliculaCredito();
        $creditoModo = null;

        if ($pestana === 'creditos') {
            $creditos = $pelicula->creditos()
                ->with('persona')
                ->orderBy('rol')
                ->orderBy('orden')
                ->orderBy('id_persona')
                ->paginate(15)
                ->withQueryString();

            // No cargar el catálogo completo: el selector consulta por AJAX.
            $idPersonaSeleccionada = $request->integer('credito_persona');
            if ($idPersonaSeleccionada < 1) {
                $idPersonaSeleccionada = (int) session()->getOldInput('id_persona', 0);
            }
            if ($idPersonaSeleccionada > 0) {
                $personas = Persona::query()
                    ->where('id_persona', $idPersonaSeleccionada)
                    ->get(['id_persona', 'nombre_completo']);
            }

            $accion = $request->query('credito_accion');
            if ($accion === 'crear') {
                $creditoModo = 'crear';
            } elseif ($accion === 'editar') {
                $idPersona = $request->integer('credito_persona');
                $rol = (string) $request->query('credito_rol', '');
                $rolesPermitidos = [
                    'DIRECTOR', 'ACTOR', 'GUIONISTA', 'PRODUCTOR', 'COMPOSITOR',
                ];

                abort_unless($idPersona > 0 && in_array($rol, $rolesPermitidos, true), 404);

                $credito = PeliculaCredito::query()
                    ->where('id_pelicula', $pelicula->id_pelicula)
                    ->where('id_persona', $idPersona)
                    ->where('rol', $rol)
                    ->firstOrFail();
                $creditoModo = 'editar';
            }
        }

        return view('admin.peliculas.form', [
            'pelicula' => $pelicula,
            'generos' => $this->generosDisponibles(
                $pelicula->id_genero_principal
            ),
            'idiomas' => $this->idiomasDisponibles(
                $pelicula->idioma_original_codigo
            ),
            'modo' => 'editar',
            'pestana' => $pestana,
            'multimedia' => $multimedia,
            'paisesProduccion' => $paisesProduccion,
            'creditos' => $creditos,
            'personas' => $personas,
            'credito' => $credito,
            'creditoModo' => $creditoModo,
        ]);
    }

    /**
     * Actualizar los datos principales y validar las transiciones de estado.
     */
    public function update(
        Request $request,
        Pelicula $pelicula
    ) {
        $estadoSolicitado = (string) $request->input(
            'estado',
            $pelicula->estado
        );

        $datos = $request->validate(
            $this->reglas($estadoSolicitado)
        );

        $this->validarCatalogosActivosAlAsignar($datos, $pelicula);

        if ($datos['estado'] === 'ACTIVA') {
            $tienePosterPrincipal = $pelicula->multimedia()
                ->where('tipo', 'POSTER')
                ->where('activo', 1)
                ->where('es_principal', 1)
                ->exists();
            if (! $tienePosterPrincipal) {
                throw ValidationException::withMessages([
                    'estado' => 'Para activar la película, registra primero un póster multimedia activo y marcado como principal.',
                ]);
            }
        }

        $pelicula->update($datos);

        return redirect()
            ->route('admin.peliculas.edit', $pelicula)
            ->with(
                'success',
                'Película actualizada correctamente.'
            );
    }

    /**
     * Reglas para los datos principales.
     */
    private function reglas(string $estadoObjetivo = 'BORRADOR', bool $esCreacion = false): array
    {
        $publicacion = $estadoObjetivo === 'ACTIVA';

        $reglaGenero = Rule::exists('genero', 'id_genero');
        $reglaIdioma = Rule::exists('idioma', 'codigo');

        // Para publicar, los catálogos deben estar activos.
        // En borrador/inactiva se permite conservar una referencia existente
        // aunque el catálogo se haya desactivado posteriormente.
        if ($publicacion) {
            $reglaGenero->where('activo', 1);
            $reglaIdioma->where('activo', 1);
        }

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
                $publicacion ? 'required' : 'nullable',
                'integer',
                $reglaGenero,
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
                $publicacion ? 'required' : 'nullable',
                'string',
                'max:15',
                $reglaIdioma,
            ],
            'estado' => [
                'required',
                Rule::in($esCreacion ? ['BORRADOR'] : self::ESTADOS),
            ],
        ];
    }

    /**
     * Permite conservar referencias desactivadas que ya tenía la película,
     * pero solo admite asignar nuevas referencias a catálogos activos.
     */
    private function validarCatalogosActivosAlAsignar(
        array $datos,
        ?Pelicula $pelicula = null
    ): void {
        if (($datos['estado'] ?? null) === 'ACTIVA') {
            // La regla de publicación ya exige ambos catálogos activos.
            return;
        }

        $generoNuevo = $datos['id_genero_principal'] ?? null;
        $generoAnterior = $pelicula?->id_genero_principal;

        if (
            $generoNuevo !== null &&
            (string) $generoNuevo !== (string) $generoAnterior &&
            ! Genero::query()
                ->where('id_genero', $generoNuevo)
                ->where('activo', 1)
                ->exists()
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_genero_principal' => 'Solo puedes asignar un género activo. Puedes conservar el género desactivado que ya tenía la película.',
            ]);
        }

        $idiomaNuevo = $datos['idioma_original_codigo'] ?? null;
        $idiomaAnterior = $pelicula?->idioma_original_codigo;

        if (
            $idiomaNuevo !== null &&
            (string) $idiomaNuevo !== (string) $idiomaAnterior &&
            ! Idioma::query()
                ->where('codigo', $idiomaNuevo)
                ->where('activo', 1)
                ->exists()
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'idioma_original_codigo' => 'Solo puedes asignar un idioma activo. Puedes conservar el idioma desactivado que ya tenía la película.',
            ]);
        }
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
