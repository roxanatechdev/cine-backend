<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sala;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class SalaAdminController extends Controller
{
    private const ESTADOS = [
        'ACTIVA',
        'INACTIVA',
        'MANTENIMIENTO',
    ];

    public function index()
    {
        $salas = Sala::query()
            ->orderBy('nombre')
            ->paginate(15);

        return view('admin.salas.index', compact('salas'));
    }

    public function create()
    {
        return view('admin.salas.form', [
            'sala' => new Sala(),
            'modo' => 'crear',
            'estados' => self::ESTADOS,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validarSala($request);

        Sala::create($datos);

        return redirect()
            ->route('admin.salas.index')
            ->with('success', 'Sala registrada correctamente.');
    }

    public function edit(string $sala)
    {
        return view('admin.salas.form', [
            'sala' => Sala::query()->findOrFail($sala),
            'modo' => 'editar',
            'estados' => self::ESTADOS,
        ]);
    }

    public function distribucion(string $sala)
    {
        $sala = Sala::query()->findOrFail($sala);

        return view('admin.salas.distribucion', compact('sala'));
    }

    public function update(Request $request, string $sala)
    {
        $sala = Sala::query()->findOrFail($sala);
        $datos = $this->validarSala($request, $sala);

        $sala->update($datos);

        return redirect()
            ->route('admin.salas.index')
            ->with('success', 'Sala actualizada correctamente.');
    }

    public function destroy(string $sala)
    {
        $sala = Sala::query()->findOrFail($sala);

        // La base física define ON DELETE RESTRICT para las referencias a sala.
        // Comprobamos dependencias para mostrar un mensaje entendible al usuario.
        $tieneAsientos = DB::table('asiento')
            ->where('id_sala', $sala->id_sala)
            ->exists();

        $tieneFunciones = DB::table('funcion')
            ->where('id_sala', $sala->id_sala)
            ->exists();

        if ($tieneAsientos || $tieneFunciones) {
            return redirect()
                ->route('admin.salas.index')
                ->with('error', 'No se puede eliminar la sala porque tiene asientos o funciones asociados. Cambia su estado si ya no debe utilizarse.');
        }

        try {
            $sala->delete();
        } catch (QueryException $exception) {
            // Conserva el comportamiento seguro si existe otra referencia física.
            return redirect()
                ->route('admin.salas.index')
                ->with('error', 'No se puede eliminar la sala porque existen registros asociados.');
        }

        return redirect()
            ->route('admin.salas.index')
            ->with('success', 'Sala eliminada correctamente.');
    }

    private function validarSala(Request $request, ?Sala $sala = null): array
    {
        $nombreUnico = Rule::unique('sala', 'nombre');

        if ($sala !== null) {
            $nombreUnico->ignore($sala->id_sala, 'id_sala');
        }

        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:50',
                $nombreUnico,
            ],
            'capacidad' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],
            'margen_limpieza_minutos' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],
            'estado' => [
                'required',
                Rule::in(self::ESTADOS),
            ],
        ], [
            'nombre.required' => 'El nombre de la sala es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'nombre.unique' => 'Ya existe una sala con ese nombre.',
            'capacidad.integer' => 'La capacidad debe ser un número entero.',
            'capacidad.min' => 'La capacidad debe ser mayor que cero o quedar vacía.',
            'capacidad.max' => 'La capacidad no puede superar 65535.',
            'margen_limpieza_minutos.required' => 'El margen de limpieza es obligatorio.',
            'margen_limpieza_minutos.integer' => 'El margen debe ser un número entero.',
            'margen_limpieza_minutos.min' => 'El margen no puede ser negativo.',
            'margen_limpieza_minutos.max' => 'El margen no puede superar 65535 minutos.',
            'estado.required' => 'Debes seleccionar el estado de la sala.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);
    }
}
