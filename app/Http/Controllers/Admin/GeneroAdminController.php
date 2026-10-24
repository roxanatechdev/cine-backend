<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genero;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GeneroAdminController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $activo = $request->query('activo', '');

        $generos = Genero::query()
            ->withCount('peliculas')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    if (ctype_digit($buscar)) {
                        $subquery->where('id_genero', (int) $buscar)
                            ->orWhere('nombre', 'like', "%{$buscar}%")
                            ->orWhere('descripcion', 'like', "%{$buscar}%");
                    } else {
                        $subquery->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('descripcion', 'like', "%{$buscar}%");
                    }
                });
            })
            ->when(in_array($activo, ['0', '1'], true), function ($query) use ($activo) {
                $query->where('activo', (int) $activo);
            })
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('admin.generos.index', compact('generos', 'buscar', 'activo'));
    }

    public function create()
    {
        return view('admin.generos.form', [
            'genero' => new Genero(['activo' => 1]),
            'modo' => 'crear',
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80', 'unique:genero,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ], [
            'nombre.required' => 'El nombre del género es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 80 caracteres.',
            'nombre.unique' => 'Ya existe un género con ese nombre.',
            'descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
            'activo.required' => 'Debes indicar si el género está activo.',
            'activo.boolean' => 'El estado debe ser activo o inactivo.',
        ]);

        $datos['activo'] = (int) $datos['activo'];
        Genero::create($datos);

        return redirect()->route('admin.generos.index')
            ->with('success', 'Género registrado correctamente.');
    }

    public function edit(string $genero)
    {
        $registro = Genero::query()->findOrFail($genero);

        return view('admin.generos.form', [
            'genero' => $registro,
            'modo' => 'editar',
        ]);
    }

    public function update(Request $request, string $genero)
    {
        $registro = Genero::query()->findOrFail($genero);

        $datos = $request->validate([
            'nombre' => [
                'required', 'string', 'max:80',
                Rule::unique('genero', 'nombre')->ignore($registro->id_genero, 'id_genero'),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ], [
            'nombre.required' => 'El nombre del género es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 80 caracteres.',
            'nombre.unique' => 'Ya existe un género con ese nombre.',
            'descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
            'activo.required' => 'Debes indicar si el género está activo.',
            'activo.boolean' => 'El estado debe ser activo o inactivo.',
        ]);

        $datos['activo'] = (int) $datos['activo'];
        $registro->update($datos);

        return redirect()->route('admin.generos.index')
            ->with('success', 'Género actualizado correctamente.');
    }
}
