<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asiento;
use App\Models\Sala;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AsientoAdminController extends Controller
{
    private const TIPOS = [
        'ESTANDAR',
        'PREFERENCIAL',
        'ACCESIBLE',
    ];

    /** Campos cuya modificación cambia la identidad o ubicación estructural del asiento. */
    private const CAMPOS_ESTRUCTURALES = [
        'id_sala' => 'la sala',
        'fila' => 'la fila',
        'numero' => 'el número',
        'tipo' => 'el tipo',
    ];

    public function index(Request $request)
    {
        return view('admin.asientos.index');
    }

    public function create()
    {
        return view('admin.asientos.form', [
            'asiento' => new Asiento(),
            'modo' => 'crear',
            'salas' => Sala::query()->orderBy('nombre')->get(),
            'tipos' => self::TIPOS,
        ]);
    }

    public function store(Request $request)
    {
        Asiento::create($this->validarAsiento($request));

        return redirect()
            ->route('admin.asientos.index')
            ->with('success', 'Asiento registrado correctamente.');
    }

    public function edit(string $idAsiento)
    {
        $asiento = Asiento::query()->findOrFail($idAsiento);

        // La protección depende directamente del inventario histórico, no del resultado de los JOIN.
        // Así, un asiento sigue protegido incluso si una relación de presentación no devuelve filas.
        $asientoProtegido = DB::table('funcion_asiento')
            ->where('id_asiento', $asiento->id_asiento)
            ->exists();

        // Se muestran las funciones conservadas en el inventario, incluidas las canceladas.
        $funcionesAsociadas = DB::table('funcion_asiento as fa')
            ->join('funcion as f', 'f.id_funcion', '=', 'fa.id_funcion')
            ->join('pelicula as p', 'p.id_pelicula', '=', 'f.id_pelicula')
            ->where('fa.id_asiento', $asiento->id_asiento)
            ->select([
                'fa.id_funcion_asiento',
                'fa.id_funcion',
                'fa.precio as precio_asiento',
                'f.inicio',
                'f.formato',
                'f.estado',
                'f.precio_base',
                'p.titulo as pelicula_titulo',
            ])
            ->selectSub(
                DB::table('entrada as e')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('e.id_funcion_asiento', 'fa.id_funcion_asiento'),
                'cantidad_entradas'
            )
            ->orderByDesc('f.inicio')
            ->orderBy('fa.id_funcion')
            ->get();

        return view('admin.asientos.form', [
            'asiento' => $asiento,
            'modo' => 'editar',
            'salas' => Sala::query()->orderBy('nombre')->get(),
            'tipos' => self::TIPOS,
            'asientoProtegido' => $asientoProtegido,
            'funcionesAsociadas' => $funcionesAsociadas,
        ]);
    }

    public function update(Request $request, string $idAsiento)
    {
        // Valida fuera de la transacción: una ValidationException no debe provocar
        // rollback de un savepoint anidado dentro de RefreshDatabase en MySQL.
        $asientoActual = Asiento::query()->findOrFail($idAsiento);
        $datos = $this->validarAsiento($request, $asientoActual);

        $errores = DB::transaction(function () use ($idAsiento, $datos): array {
            $asiento = Asiento::query()
                ->whereKey($idAsiento)
                ->lockForUpdate()
                ->firstOrFail();

            $referencias = DB::table('funcion_asiento')
                ->where('id_asiento', $asiento->id_asiento)
                ->lockForUpdate()
                ->get(['id_funcion', 'id_funcion_asiento']);

            if ($referencias->isNotEmpty()) {
                $cambiosEstructurales = [];

                foreach (self::CAMPOS_ESTRUCTURALES as $campo => $etiqueta) {
                    $actual = $asiento->getAttribute($campo);
                    $nuevo = $datos[$campo];

                    if (in_array($campo, ['id_sala', 'numero'], true)) {
                        $actual = (int) $actual;
                        $nuevo = (int) $nuevo;
                    } else {
                        $actual = (string) $actual;
                        $nuevo = (string) $nuevo;
                    }

                    if ($actual !== $nuevo) {
                        $cambiosEstructurales[$campo] = $etiqueta;
                    }
                }

                if ($cambiosEstructurales !== []) {
                    $cantidadFunciones = $referencias
                        ->pluck('id_funcion')
                        ->unique()
                        ->count();

                    $mensajeBase = sprintf(
                        'No se puede modificar %s porque este asiento está asociado a %d %s. ',
                        implode(', ', array_values($cambiosEstructurales)),
                        $cantidadFunciones,
                        $cantidadFunciones === 1 ? 'función' : 'funciones'
                    );

                    $errores = [];
                    foreach ($cambiosEstructurales as $campo => $etiqueta) {
                        $errores[$campo] = $mensajeBase
                            . 'La sala, fila, número y tipo deben conservarse para proteger el historial.';
                    }

                    // No lanzar ValidationException dentro de la transacción.
                    // Devolver los errores permite que el commit/rollback normal termine primero.
                    return $errores;
                }
            }

            // El estado puede cambiar, pero el inventario histórico nunca se elimina ni reconstruye.
            $asiento->update($datos);

            return [];
        });

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }

        return redirect()
            ->route('admin.asientos.index')
            ->with('success', 'Asiento actualizado correctamente.');
    }

    public function destroy(string $idAsiento)
    {
        try {
            $eliminado = DB::transaction(function () use ($idAsiento): bool {
                $asiento = Asiento::query()
                    ->whereKey($idAsiento)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Se vuelve a comprobar dentro de la transacción para no confiar en una revisión previa.
                $tieneFunciones = DB::table('funcion_asiento')
                    ->where('id_asiento', $asiento->id_asiento)
                    ->lockForUpdate()
                    ->exists();

                if ($tieneFunciones) {
                    return false;
                }

                $asiento->delete();

                return true;
            });
        } catch (QueryException $exception) {
            // La FK RESTRICT sigue siendo la última barrera ante referencias concurrentes u otras relaciones.
            return redirect()
                ->route('admin.asientos.index')
                ->with('error', 'No se puede eliminar el asiento porque existen registros asociados.');
        }

        if (! $eliminado) {
            return redirect()
                ->route('admin.asientos.index')
                ->with('error', 'No se puede eliminar este asiento porque está asociado a funciones. Puedes desactivarlo para impedir su uso futuro.');
        }

        return redirect()
            ->route('admin.asientos.index')
            ->with('success', 'Asiento eliminado correctamente.');
    }

    private function validarAsiento(Request $request, ?Asiento $asiento = null): array
    {
        $ubicacionUnica = Rule::unique('asiento', 'numero')
            ->where(fn ($query) => $query
                ->where('id_sala', $request->input('id_sala'))
                ->where('fila', $request->input('fila')));

        if ($asiento !== null) {
            $ubicacionUnica->ignore($asiento->id_asiento, 'id_asiento');
        }

        return $request->validate([
            'id_sala' => ['required', 'integer', Rule::exists('sala', 'id_sala')],
            'fila' => ['required', 'string', 'max:5'],
            'numero' => ['required', 'integer', 'min:1', 'max:65535', $ubicacionUnica],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'activo' => ['required', 'integer', Rule::in([0, 1])],
        ], [
            'id_sala.required' => 'Debes seleccionar una sala.',
            'id_sala.exists' => 'La sala seleccionada no existe.',
            'fila.required' => 'La fila es obligatoria.',
            'fila.max' => 'La fila no puede superar los 5 caracteres.',
            'numero.required' => 'El número del asiento es obligatorio.',
            'numero.integer' => 'El número debe ser un entero.',
            'numero.min' => 'El número del asiento debe ser mayor que cero.',
            'numero.max' => 'El número no puede superar 65535.',
            'numero.unique' => 'Ya existe un asiento con ese número en esa fila y sala.',
            'tipo.required' => 'Debes seleccionar el tipo de asiento.',
            'tipo.in' => 'El tipo de asiento seleccionado no es válido.',
            'activo.required' => 'Debes indicar si el asiento está activo.',
            'activo.in' => 'El valor de activo debe ser 1 (sí) o 0 (no).',
        ]);
    }
}
