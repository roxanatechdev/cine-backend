<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pais;
use App\Models\Pelicula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class PeliculaPaisAdminController extends Controller
{

    /** Buscar países por código o nombre para el selector integrado. */
    public function buscarPaises(Request $request, Pelicula $pelicula)
    {
        $buscar = trim((string) $request->query('q', ''));
        if (mb_strlen($buscar) < 2) {
            return response()->json(['resultados' => []]);
        }

        $asignados = DB::table('pelicula_pais')
            ->where('id_pelicula', $pelicula->id_pelicula)
            ->pluck('codigo_pais')
            ->all();

        $resultados = Pais::query()
            ->where(function ($query) use ($buscar) {
                $query->where('nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('codigo', 'like', '%' . mb_strtoupper($buscar) . '%');
            })
            ->orderBy('nombre')
            ->limit(10)
            ->get(['codigo', 'nombre'])
            ->map(fn (Pais $pais) => [
                'codigo' => $pais->codigo,
                'nombre' => $pais->nombre,
                'asignado' => in_array($pais->codigo, $asignados, true),
            ]);

        return response()->json(['resultados' => $resultados]);
    }

    /** Crear un país desde Producción y asociarlo en una sola transacción. */
    public function crearRapido(Request $request, Pelicula $pelicula)
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'nombre' => ['required', 'string', 'max:100'],
        ], [
            'codigo.size' => 'El código debe tener exactamente dos letras, por ejemplo CL.',
            'codigo.regex' => 'Usa dos letras del código ISO alfa-2, por ejemplo CL, AR o BR.',
            'nombre.required' => 'El nombre del país es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
        ]);

        $codigo = strtoupper(trim($datos['codigo']));
        $nombre = trim($datos['nombre']);

        try {
            DB::transaction(function () use ($pelicula, $codigo, $nombre) {
                $pais = Pais::query()->where('codigo', $codigo)->first();
                if (!$pais) {
                    $paisPorNombre = Pais::query()->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->first();
                    if ($paisPorNombre) {
                        throw ValidationException::withMessages([
                            'nombre' => 'Ya existe un país con ese nombre y utiliza el código ' . $paisPorNombre->codigo . '.',
                        ]);
                    }
                    $pais = Pais::create(['codigo' => $codigo, 'nombre' => $nombre]);
                } elseif (mb_strtolower($pais->nombre) !== mb_strtolower($nombre)) {
                    throw ValidationException::withMessages([
                        'codigo' => 'El código ' . $codigo . ' ya pertenece a ' . $pais->nombre . '. Busca y selecciona ese país existente.',
                    ]);
                }

                $yaAsignado = DB::table('pelicula_pais')
                    ->where('id_pelicula', $pelicula->id_pelicula)
                    ->where('codigo_pais', $pais->codigo)
                    ->exists();
                if ($yaAsignado) {
                    throw ValidationException::withMessages([
                        'codigo' => 'Este país ya está asignado a la película.',
                    ]);
                }

                DB::table('pelicula_pais')->insert([
                    'id_pelicula' => $pelicula->id_pelicula,
                    'codigo_pais' => $pais->codigo,
                ]);
            });
        } catch (QueryException $e) {
            // Los índices únicos y las claves foráneas siguen siendo la última barrera de integridad.
            if (str_contains($e->getMessage(), 'uq_pais_nombre') || str_contains($e->getMessage(), 'pais.nombre')) {
                throw ValidationException::withMessages(['nombre' => 'Ya existe un país con ese nombre.']);
            }
            if (str_contains($e->getMessage(), 'PRIMARY') || str_contains($e->getMessage(), 'pelicula_pais')) {
                throw ValidationException::withMessages(['codigo' => 'Este país ya está asociado a la película.']);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'País creado y asignado correctamente.',
            'redirect' => route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'produccion']),
        ]);
    }

    /**
     * Listar los países de producción de una película.
     */
    public function index(Pelicula $pelicula)
    {
        $paises = DB::table('pelicula_pais')
            ->join(
                'pais',
                'pelicula_pais.codigo_pais',
                '=',
                'pais.codigo'
            )
            ->where(
                'pelicula_pais.id_pelicula',
                $pelicula->id_pelicula
            )
            ->orderBy('pais.nombre')
            ->select('pais.codigo', 'pais.nombre')
            ->paginate(15);

        return view('admin.paises-peliculas.index', [
            'pelicula' => $pelicula,
            'paises' => $paises,
        ]);
    }

    /**
     * Mostrar el formulario para asignar un país.
     */
    public function create(Pelicula $pelicula)
    {
        $paises = Pais::query()
            ->orderBy('nombre')
            ->get(['codigo', 'nombre']);

        return view('admin.paises-peliculas.form', [
            'pelicula' => $pelicula,
            'paises' => $paises,
        ]);
    }

    /**
     * Asignar un país de producción a una película.
     */
    public function store(Request $request, Pelicula $pelicula)
    {
        $datos = $request->validate([
            'codigo_pais' => [
                'required',
                'string',
                'size:2',
                'exists:pais,codigo',
            ],
        ]);

        $codigoPais = strtoupper($datos['codigo_pais']);

        $existe = DB::table('pelicula_pais')
            ->where('id_pelicula', $pelicula->id_pelicula)
            ->where('codigo_pais', $codigoPais)
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'codigo_pais' =>
                'Este país ya está asignado a la película.',
            ]);
        }

        DB::table('pelicula_pais')->insert([
            'id_pelicula' => $pelicula->id_pelicula,
            'codigo_pais' => $codigoPais,
        ]);

        return redirect()
            ->route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'produccion'])
            ->with(
                'success',
                'País de producción asignado correctamente.'
            );
    }

    /**
     * Eliminar únicamente la asociación entre película y país.
     */
    public function destroy(Pelicula $pelicula, string $pais)
    {
        abort_unless(
            preg_match('/^[A-Za-z]{2}$/', $pais) === 1,
            404
        );

        $eliminados = DB::table('pelicula_pais')
            ->where('id_pelicula', $pelicula->id_pelicula)
            ->where('codigo_pais', strtoupper($pais))
            ->delete();

        if ($eliminados === 0) {
            abort(404);
        }

        return redirect()
            ->route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'produccion'])
            ->with(
                'success',
                'País de producción desasignado correctamente.'
            );
    }
}
