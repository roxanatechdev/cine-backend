<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comprador;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompradorAdminController extends Controller
{
    private const TIPOS = [
        'INVITADO',
        'REGISTRADO',
    ];

    public function index(Request $request)
    {
        return view('admin.compradores.index');
    }

    public function create()
    {
        return view('admin.compradores.form', [
            'comprador' => new Comprador(),
            'modo' => 'crear',
            'tipos' => self::TIPOS,
        ]);
    }

    public function store(Request $request)
    {
        Comprador::create($this->validarComprador($request));

        return redirect()
            ->route('admin.compradores.index')
            ->with('success', 'Comprador registrado correctamente.');
    }

    public function edit(string $compradorId)
    {
        return view('admin.compradores.form', [
            'comprador' => Comprador::query()->findOrFail($compradorId),
            'modo' => 'editar',
            'tipos' => self::TIPOS,
        ]);
    }

    public function update(Request $request, string $compradorId)
    {
        $comprador = Comprador::query()->findOrFail($compradorId);
        $comprador->update($this->validarComprador($request));

        return redirect()
            ->route('admin.compradores.index')
            ->with('success', 'Comprador actualizado correctamente.');
    }

    public function destroy(string $compradorId)
    {
        $comprador = Comprador::query()->findOrFail($compradorId);

        if (DB::table('compra')->where('id_comprador', $comprador->id_comprador)->exists()) {
            return redirect()
                ->route('admin.compradores.index')
                ->with('error', 'No se puede eliminar el comprador porque tiene compras asociadas.');
        }

        try {
            $comprador->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('admin.compradores.index')
                ->with('error', 'No se puede eliminar el comprador porque existen registros asociados.');
        }

        return redirect()
            ->route('admin.compradores.index')
            ->with('success', 'Comprador eliminado correctamente.');
    }

    private function validarComprador(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'string', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'tipo_comprador' => ['required', Rule::in(self::TIPOS)],
        ], [
            'nombre.required' => 'El nombre del comprador es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'correo.max' => 'El correo no puede superar los 150 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
            'tipo_comprador.required' => 'Debes seleccionar el tipo de comprador.',
            'tipo_comprador.in' => 'El tipo de comprador seleccionado no es válido.',
        ]);
    }
}
