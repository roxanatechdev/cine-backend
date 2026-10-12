<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pais;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaisAdminController extends Controller
{
    /**
     * Listar países registrados.
     */
    public function index()
    {
        $paises = Pais::query()
            ->orderBy('nombre')
            ->paginate(15);

        return view('admin.paises.index', compact('paises'));
    }

    /**
     * Mostrar el formulario de creación.
     */
    public function create()
    {
        return view('admin.paises.form', [
            'pais' => new Pais(),
            'modo' => 'crear',
        ]);
    }

    /**
     * Registrar un país.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'codigo' => [
                'required',
                'string',
                'size:2',
                'alpha',
                'uppercase',
                'unique:pais,codigo',
            ],
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:pais,nombre',
            ],
        ], [
            'codigo.required' => 'El código del país es obligatorio.',
            'codigo.size' => 'El código debe tener exactamente dos caracteres.',
            'codigo.alpha' => 'El código solo puede contener letras.',
            'codigo.uppercase' => 'El código debe estar en mayúsculas.',
            'codigo.unique' => 'Ya existe un país con ese código.',
            'nombre.required' => 'El nombre del país es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe un país con ese nombre.',
        ]);

        Pais::create($datos);

        return redirect()
            ->route('admin.paises.index')
            ->with('success', 'País registrado correctamente.');
    }

    /**
     * Mostrar el formulario de edición.
     */
    public function edit(string $pais)
    {
        $pais = Pais::query()->findOrFail($pais);

        return view('admin.paises.form', [
            'pais' => $pais,
            'modo' => 'editar',
        ]);
    }

    /**
     * Actualizar un país.
     */
    public function update(Request $request, string $pais)
    {
        $pais = Pais::query()->findOrFail($pais);

        $datos = $request->validate([
            'codigo' => [
                'required',
                'string',
                'size:2',
                'alpha',
                'uppercase',
                Rule::unique('pais', 'codigo')
                    ->ignore($pais->codigo, 'codigo'),
            ],
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pais', 'nombre')
                    ->ignore($pais->codigo, 'codigo'),
            ],
        ], [
            'codigo.required' => 'El código del país es obligatorio.',
            'codigo.size' => 'El código debe tener exactamente dos caracteres.',
            'codigo.alpha' => 'El código solo puede contener letras.',
            'codigo.uppercase' => 'El código debe estar en mayúsculas.',
            'codigo.unique' => 'Ya existe un país con ese código.',
            'nombre.required' => 'El nombre del país es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe un país con ese nombre.',
        ]);

        $pais->update($datos);

        return redirect()
            ->route('admin.paises.index')
            ->with('success', 'País actualizado correctamente.');
    }
}
