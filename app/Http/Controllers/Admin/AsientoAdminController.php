<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asiento;
use App\Models\Sala;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AsientoAdminController extends Controller
{
    private const TIPOS = [
        'ESTANDAR',
        'PREFERENCIAL',
        'ACCESIBLE',
    ];

    public function index()
    {
        $asientos = Asiento::query()
            ->with('sala')
            ->orderBy('id_sala')
            ->orderBy('fila')
            ->orderBy('numero')
            ->paginate(20);

        return view('admin.asientos.index', compact('asientos'));
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

    public function edit(string $asiento)
    {
        return view('admin.asientos.form', [
            'asiento' => Asiento::query()->findOrFail($asiento),
            'modo' => 'editar',
            'salas' => Sala::query()->orderBy('nombre')->get(),
            'tipos' => self::TIPOS,
        ]);
    }

    public function update(Request $request, string $asiento)
    {
        $asiento = Asiento::query()->findOrFail($asiento);
        $asiento->update($this->validarAsiento($request, $asiento));

        return redirect()
            ->route('admin.asientos.index')
            ->with('success', 'Asiento actualizado correctamente.');
    }

    public function destroy(string $asiento)
    {
        $asiento = Asiento::query()->findOrFail($asiento);

        // El esquema usa ON DELETE RESTRICT desde funcion_asiento.
        $tieneFunciones = DB::table('funcion_asiento')
            ->where('id_asiento', $asiento->id_asiento)
            ->exists();

        if ($tieneFunciones) {
            return redirect()
                ->route('admin.asientos.index')
                ->with('error', 'No se puede eliminar este asiento porque está asociado a funciones. Puedes desactivarlo para impedir su uso futuro.');
        }

        try {
            $asiento->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('admin.asientos.index')
                ->with('error', 'No se puede eliminar el asiento porque existen registros asociados.');
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
