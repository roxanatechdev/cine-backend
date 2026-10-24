<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Funcion;
use App\Models\Asiento;
use App\Models\Pelicula;
use App\Models\Sala;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FuncionAdminController extends Controller
{
    private const FORMATOS = ['2D', '3D'];

    private const ESTADOS = [
        'PROGRAMADA',
        'EN_CURSO',
        'FINALIZADA',
        'CANCELADA',
    ];

    public function index()
    {
        $funciones = Funcion::query()
            ->with(['pelicula', 'sala'])
            ->orderByDesc('inicio')
            ->paginate(20);

        return view('admin.funciones.index', compact('funciones'));
    }

    public function create()
    {
        return view('admin.funciones.form', [
            'funcion' => new Funcion(['formato' => '2D', 'estado' => 'PROGRAMADA']),
            'modo' => 'crear',
            'peliculas' => Pelicula::query()->where('estado', 'ACTIVA')->whereNotNull('duracion_minutos')->orderBy('titulo')->get(),
            'salas' => Sala::query()->where('estado', 'ACTIVA')->orderBy('nombre')->get(),
            'formatos' => self::FORMATOS,
            'estados' => self::ESTADOS,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validarFuncion($request);

        try {
            DB::transaction(function () use ($datos) {
                $sala = Sala::query()->whereKey($datos['id_sala'])->lockForUpdate()->firstOrFail();
                $pelicula = Pelicula::query()->whereKey($datos['id_pelicula'])->firstOrFail();
                $this->validarRecursos($sala, $pelicula, $datos['estado']);
                $this->validarSolapamiento($datos, $pelicula, $sala);
                $nuevaFuncion = Funcion::create($datos);
                $this->sincronizarInventarioAsientos($nuevaFuncion, $sala, (string) $datos['precio_base']);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            return back()->withInput()->with('error', 'No fue posible registrar la función por una restricción de la base de datos.');
        }

        return redirect()->route('admin.funciones.index')->with('success', 'Función registrada correctamente.');
    }

    public function edit(string $funcion)
    {
        $funcion = Funcion::query()->with(['pelicula', 'sala'])->findOrFail($funcion);

        return view('admin.funciones.form', [
            'funcion' => $funcion,
            'modo' => 'editar',
            'peliculas' => Pelicula::query()->where(function ($query) use ($funcion) {
                $query->where('estado', 'ACTIVA')->whereNotNull('duracion_minutos')
                    ->orWhere('id_pelicula', $funcion->id_pelicula);
            })->orderBy('titulo')->get(),
            'salas' => Sala::query()->where(function ($query) use ($funcion) {
                $query->where('estado', 'ACTIVA')->orWhere('id_sala', $funcion->id_sala);
            })->orderBy('nombre')->get(),
            'formatos' => self::FORMATOS,
            'estados' => self::ESTADOS,
        ]);
    }

    public function update(Request $request, string $funcion)
    {
        $funcion = Funcion::query()->findOrFail($funcion);
        $datos = $this->validarFuncion($request, $funcion);

        try {
            DB::transaction(function () use ($funcion, $datos) {
                // Bloquea la sala destino para serializar las altas/ediciones concurrentes en ella.
                $sala = Sala::query()->whereKey($datos['id_sala'])->lockForUpdate()->firstOrFail();
                $pelicula = Pelicula::query()->whereKey($datos['id_pelicula'])->firstOrFail();
                $this->validarRecursos($sala, $pelicula, $datos['estado']);

                $tieneInventario = DB::table('funcion_asiento')
                    ->where('id_funcion', $funcion->id_funcion)
                    ->exists();
                if ($tieneInventario && (int) $funcion->id_sala !== (int) $sala->id_sala) {
                    throw ValidationException::withMessages([
                        'id_sala' => 'No se puede cambiar la sala porque esta función ya tiene inventario de asientos asociado.',
                    ]);
                }

                $this->validarSolapamiento($datos, $pelicula, $sala, $funcion->id_funcion);
                $funcion->update($datos);
                $this->sincronizarInventarioAsientos($funcion, $sala, (string) $datos['precio_base']);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            return back()->withInput()->with('error', 'No fue posible actualizar la función por una restricción de la base de datos.');
        }

        return redirect()->route('admin.funciones.index')->with('success', 'Función actualizada correctamente.');
    }

    public function destroy(string $funcion)
    {
        $funcion = Funcion::query()->findOrFail($funcion);

        if (DB::table('funcion_asiento')->where('id_funcion', $funcion->id_funcion)->exists()) {
            return redirect()->route('admin.funciones.index')->with('error', 'No se puede eliminar físicamente esta función porque tiene asientos asociados. Puedes cambiar su estado a CANCELADA.');
        }

        try {
            $funcion->delete();
        } catch (QueryException $exception) {
            return redirect()->route('admin.funciones.index')->with('error', 'No se puede eliminar la función porque existen registros asociados.');
        }

        return redirect()->route('admin.funciones.index')->with('success', 'Función eliminada correctamente.');
    }

    private function validarFuncion(Request $request, ?Funcion $funcion = null): array
    {
        return $request->validate([
            'id_pelicula' => ['required', 'integer', Rule::exists('pelicula', 'id_pelicula')],
            'id_sala' => ['required', 'integer', Rule::exists('sala', 'id_sala')],
            'inicio' => ['required', 'date_format:Y-m-d\\TH:i'],
            'formato' => ['required', Rule::in(self::FORMATOS)],
            'precio_base' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
        ], [
            'id_pelicula.required' => 'Debes seleccionar una película.',
            'id_pelicula.exists' => 'La película seleccionada no existe.',
            'id_sala.required' => 'Debes seleccionar una sala.',
            'id_sala.exists' => 'La sala seleccionada no existe.',
            'inicio.required' => 'La fecha y hora de inicio son obligatorias.',
            'inicio.date_format' => 'Indica una fecha y hora válida.',
            'formato.in' => 'El formato debe ser 2D o 3D.',
            'precio_base.required' => 'El precio base es obligatorio.',
            'precio_base.min' => 'El precio base no puede ser negativo.',
            'precio_base.decimal' => 'El precio admite como máximo dos decimales.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);
    }

    private function validarRecursos(Sala $sala, Pelicula $pelicula, string $estado): void
    {
        if ($estado !== 'CANCELADA' && $sala->estado !== 'ACTIVA') {
            throw ValidationException::withMessages([
                'id_sala' => 'Solo se pueden programar funciones en salas activas.',
            ]);
        }

        if ($estado !== 'CANCELADA' && ($pelicula->estado !== 'ACTIVA' || (int) $pelicula->duracion_minutos < 1)) {
            throw ValidationException::withMessages([
                'id_pelicula' => 'La película debe estar activa y tener una duración válida para programar una función.',
            ]);
        }
    }

    private function sincronizarInventarioAsientos(Funcion $funcion, Sala $sala, string $precioBase): void
    {
        if ($funcion->estado === 'CANCELADA') {
            return;
        }

        $idsExistentes = DB::table('funcion_asiento')
            ->where('id_funcion', $funcion->id_funcion)
            ->pluck('id_asiento')
            ->map(fn ($id) => (int) $id)
            ->all();

        $asientosNuevos = Asiento::query()
            ->where('id_sala', $sala->id_sala)
            ->where('activo', 1)
            ->when($idsExistentes !== [], fn ($query) => $query->whereNotIn('id_asiento', $idsExistentes))
            ->get(['id_asiento']);

        if ($asientosNuevos->isEmpty()) {
            return;
        }

        $filas = $asientosNuevos->map(fn ($asiento) => [
            'id_funcion' => $funcion->id_funcion,
            'id_asiento' => $asiento->id_asiento,
            'precio' => $precioBase,
        ])->all();

        // La tabla no tiene timestamps; se insertan solo los campos definidos por el esquema.
        DB::table('funcion_asiento')->insert($filas);
    }

    private function validarSolapamiento(array $datos, Pelicula $pelicula, Sala $sala, ?int $idExcluir = null): void
    {
        if ($datos['estado'] === 'CANCELADA') {
            return;
        }

        $inicio = Carbon::createFromFormat('Y-m-d\\TH:i', $datos['inicio']);
        $fin = $inicio->copy()->addMinutes((int) $pelicula->duracion_minutos + (int) $sala->margen_limpieza_minutos);

        $existentes = Funcion::query()
            ->with('pelicula:id_pelicula,duracion_minutos')
            ->where('id_sala', $sala->id_sala)
            ->where('estado', '!=', 'CANCELADA')
            ->when($idExcluir !== null, fn ($query) => $query->where('id_funcion', '!=', $idExcluir))
            ->lockForUpdate()
            ->get();

        foreach ($existentes as $existente) {
            if (!$existente->pelicula || (int) $existente->pelicula->duracion_minutos < 1) {
                continue;
            }

            $inicioExistente = $existente->inicio;
            $finExistente = $inicioExistente->copy()->addMinutes(
                (int) $existente->pelicula->duracion_minutos + (int) $sala->margen_limpieza_minutos
            );

            if ($inicio->lt($finExistente) && $fin->gt($inicioExistente)) {
                throw ValidationException::withMessages([
                    'inicio' => 'La función se solapa con otra función de la misma sala considerando la duración de la película y el margen de limpieza.',
                ]);
            }
        }
    }
}
