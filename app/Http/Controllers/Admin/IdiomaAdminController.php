<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Idioma;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IdiomaAdminController extends Controller
{
    public function index()
    {
        return view('admin.idiomas.index');
    }

    public function create()
    {
        return view('admin.idiomas.form', [
            'idioma' => new Idioma(['activo' => 1]),
            'modo' => 'crear',
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:15', 'regex:/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/', 'unique:idioma,codigo'],
            'nombre' => ['required', 'string', 'max:100', 'unique:idioma,nombre'],
            'activo' => ['required', 'boolean'],
        ], [
            'codigo.required' => 'El código del idioma es obligatorio.',
            'codigo.max' => 'El código no puede superar los 15 caracteres.',
            'codigo.regex' => 'Usa un código de idioma válido, por ejemplo es, en o pt-BR.',
            'codigo.unique' => 'Ya existe un idioma con ese código.',
            'nombre.required' => 'El nombre del idioma es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe un idioma con ese nombre.',
            'activo.required' => 'Debes indicar si el idioma está activo.',
            'activo.boolean' => 'El estado debe ser activo o inactivo.',
        ]);

        $datos['codigo'] = trim($datos['codigo']);
        $datos['activo'] = (int) $datos['activo'];
        Idioma::create($datos);

        return redirect()->route('admin.idiomas.index')
            ->with('success', 'Idioma registrado correctamente.');
    }

    public function edit(string $idioma)
    {
        $registro = Idioma::query()->findOrFail($idioma);

        return view('admin.idiomas.form', [
            'idioma' => $registro,
            'modo' => 'editar',
        ]);
    }

    public function update(Request $request, string $idioma)
    {
        $registro = Idioma::query()->findOrFail($idioma);

        $datos = $request->validate([
            'codigo' => [
                'required', 'string', 'max:15',
                'regex:/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/',
                Rule::unique('idioma', 'codigo')->ignore($registro->codigo, 'codigo'),
            ],
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('idioma', 'nombre')->ignore($registro->codigo, 'codigo'),
            ],
            'activo' => ['required', 'boolean'],
        ], [
            'codigo.required' => 'El código del idioma es obligatorio.',
            'codigo.max' => 'El código no puede superar los 15 caracteres.',
            'codigo.regex' => 'Usa un código de idioma válido, por ejemplo es, en o pt-BR.',
            'codigo.unique' => 'Ya existe un idioma con ese código.',
            'nombre.required' => 'El nombre del idioma es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe un idioma con ese nombre.',
            'activo.required' => 'Debes indicar si el idioma está activo.',
            'activo.boolean' => 'El estado debe ser activo o inactivo.',
        ]);

        $nuevoCodigo = trim($datos['codigo']);
        $datos['codigo'] = $nuevoCodigo;
        $datos['activo'] = (int) $datos['activo'];
        $registro->update($datos);

        return redirect()->route('admin.idiomas.index')
            ->with('success', 'Idioma actualizado correctamente.');
    }
}
