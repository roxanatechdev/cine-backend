<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pais;
use App\Models\Pelicula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeliculaPaisAdminController extends Controller
{
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
            ->route('admin.peliculas.paises.index', $pelicula)
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
            ->route('admin.peliculas.paises.index', $pelicula)
            ->with(
                'success',
                'País de producción desasignado correctamente.'
            );
    }
}
