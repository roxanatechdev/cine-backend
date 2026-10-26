<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pais;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaisAdminController extends Controller
{
    public function index()
    {
        return view('admin.paises.index');
    }

    public function create()
    {
        return view('admin.paises.form', [
            'pais' => new Pais(),
            'modo' => 'crear',
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/', 'unique:pais,codigo'],
            'nombre' => ['required', 'string', 'max:100', 'unique:pais,nombre'],
        ], [
            'codigo.required' => 'El código del país es obligatorio.',
            'codigo.size' => 'El código debe tener exactamente dos caracteres.',
            'codigo.regex' => 'Usa dos letras mayúsculas, por ejemplo CL, AR o BR.',
            'codigo.unique' => 'Ya existe un país con ese código.',
            'nombre.required' => 'El nombre del país es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe un país con ese nombre.',
        ]);

        $datos['codigo'] = strtoupper(trim($datos['codigo']));
        $datos['nombre'] = trim($datos['nombre']);
        Pais::create($datos);

        return redirect()->route('admin.paises.index')
            ->with('success', 'País registrado correctamente.');
    }

    public function edit(string $pais)
    {
        return view('admin.paises.form', [
            'pais' => Pais::query()->findOrFail($pais),
            'modo' => 'editar',
        ]);
    }

    public function update(Request $request, string $pais)
    {
        $registro = Pais::query()->findOrFail($pais);

        $datos = $request->validate([
            'codigo' => [
                'required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/',
                Rule::unique('pais', 'codigo')->ignore($registro->codigo, 'codigo'),
            ],
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('pais', 'nombre')->ignore($registro->codigo, 'codigo'),
            ],
        ], [
            'codigo.required' => 'El código del país es obligatorio.',
            'codigo.size' => 'El código debe tener exactamente dos caracteres.',
            'codigo.regex' => 'Usa dos letras mayúsculas, por ejemplo CL, AR o BR.',
            'codigo.unique' => 'Ya existe un país con ese código.',
            'nombre.required' => 'El nombre del país es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe un país con ese nombre.',
        ]);

        $datos['codigo'] = strtoupper(trim($datos['codigo']));
        $datos['nombre'] = trim($datos['nombre']);
        $registro->update($datos);

        return redirect()->route('admin.paises.index')
            ->with('success', 'País actualizado correctamente.');
    }
}
