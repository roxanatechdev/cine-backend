<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class PersonaAdminController extends Controller
{
    /**
     * Listar personas con búsqueda y paginación.
     */
    public function index(Request $request)
    {
        // El listado es reactivo; se conservan la ruta y los endpoints CRUD.
        return view('admin.personas.index');
    }

    /**
     * Mostrar el formulario de registro.
     */
    public function create()
    {
        return view('admin.personas.form', [
            'persona' => new Persona(),
            'modo' => 'crear',
        ]);
    }

    private function reglas(): array
    {
        return [
            'nombre_completo' => [
                'required',
                'string',
                'max:180',
            ],
            'nombre_busqueda' => [
                'nullable',
                'string',
                'max:180',
            ],
            'url_foto' => [
                'nullable',
                'string',
                'max:255',
            ],
            'archivo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,avif',
                'max:5120',
            ],
        ];
    }

    /**
     * Crear una persona.
     */
    public function store(Request $request)
    {
        $rutaNueva = null;

        try {
            $datos = $request->validate($this->reglas());

            $foto = $this->resolverFoto($request);

            if (
                $request->hasFile('archivo') &&
                is_string($foto) &&
                str_starts_with($foto, 'personas/')
            ) {
                $rutaNueva = $foto;
            }

            unset($datos['archivo'], $datos['url_foto']);

            $datos['foto'] = $foto;

            Persona::create($datos);

            return redirect()
                ->route('admin.personas.index')
                ->with('success', 'Persona registrada correctamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->limpiarArchivoNuevo($rutaNueva);

            Log::error('Error al registrar una persona.', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('archivo'))
                ->withErrors([
                    'almacenamiento' =>
                    'No se pudo registrar la persona. Inténtalo nuevamente.',
                ]);
        }
    }

    /**
     * Mostrar el formulario de edición.
     */
    public function edit(Persona $persona)
    {
        return view('admin.personas.form', [
            'persona' => $persona,
            'modo' => 'editar',
        ]);
    }

    /**
     * Actualizar una persona.
     */
    public function update(Request $request, Persona $persona)
    {
        $rutaNueva = null;

        try {
            $datos = $request->validate($this->reglas());

            $fotoAnterior = $persona->foto;
            $fotoNueva = $this->resolverFoto($request, $persona);

            if (
                $request->hasFile('archivo') &&
                is_string($fotoNueva) &&
                str_starts_with($fotoNueva, 'personas/')
            ) {
                $rutaNueva = $fotoNueva;
            }

            unset($datos['archivo'], $datos['url_foto']);

            $datos['foto'] = $fotoNueva;

            DB::transaction(function () use ($persona, $datos) {
                $persona->update($datos);
            });

            // Eliminar la foto anterior únicamente después de guardar.
            if (
                $fotoAnterior !== $fotoNueva &&
                $this->esRutaLocalGestionada($fotoAnterior)
            ) {
                $this->eliminarArchivoLocal(
                    $fotoAnterior,
                    'foto anterior de una persona'
                );
            }

            return redirect()
                ->route('admin.personas.index')
                ->with('success', 'Persona actualizada correctamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->limpiarArchivoNuevo($rutaNueva);

            Log::error('Error al actualizar una persona.', [
                'id_persona' => $persona->id_persona,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('archivo'))
                ->withErrors([
                    'almacenamiento' =>
                    'No se pudo actualizar la persona. Inténtalo nuevamente.',
                ]);
        }
    }

    /**
     * Resolver la foto: archivo local, URL HTTPS o foto existente.
     */
    private function resolverFoto(
        Request $request,
        ?Persona $actual = null
    ): ?string {
        if ($request->hasFile('archivo')) {
            if ($request->filled('url_foto')) {
                throw ValidationException::withMessages([
                    'url_foto' =>
                    'Sube una fotografía o indica una URL, pero no ambas.',
                ]);
            }

            $ruta = $request->file('archivo')
                ->store('personas', 'public');

            if (! is_string($ruta) || $ruta === '') {
                throw new \RuntimeException(
                    'No se pudo almacenar la fotografía.'
                );
            }

            return $ruta;
        }

        if ($request->filled('url_foto')) {
            $url = trim($request->input('url_foto'));

            if (! $this->esUrlDeImagen($url)) {
                throw ValidationException::withMessages([
                    'url_foto' =>
                    'Introduce una URL HTTPS válida de imagen.',
                ]);
            }

            return $url;
        }

        // En edición se conserva la foto si no se cambia.
        if ($actual !== null) {
            return $actual->foto;
        }

        // La foto es opcional al registrar una persona.
        return null;
    }

    /**
     * Validar una URL HTTPS de imagen.
     */
    private function esUrlDeImagen(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $partes = parse_url($url);

        if (
            ! is_array($partes) ||
            strtolower($partes['scheme'] ?? '') !== 'https' ||
            empty($partes['host']) ||
            isset($partes['user']) ||
            isset($partes['pass'])
        ) {
            return false;
        }

        return preg_match(
            '/\.(jpg|jpeg|png|webp|avif)$/i',
            $partes['path'] ?? ''
        ) === 1;
    }

    /**
     * Reconocer únicamente rutas del directorio administrado.
     */
    private function esRutaLocalGestionada(?string $ruta): bool
    {
        if (! is_string($ruta) || $ruta === '') {
            return false;
        }

        if (! str_starts_with($ruta, 'personas/')) {
            return false;
        }

        $nombre = substr($ruta, strlen('personas/'));

        return $nombre !== ''
            && basename($nombre) === $nombre
            && ! str_contains($ruta, '..')
            && ! str_contains($ruta, '\\');
    }

    /**
     * Eliminar un archivo solo si ya no está referenciado.
     */
    private function eliminarArchivoLocal(
        string $ruta,
        string $contexto
    ): bool {
        if (! $this->esRutaLocalGestionada($ruta)) {
            Log::warning('Se rechazó una ruta de foto no permitida.', [
                'contexto' => $contexto,
            ]);

            return false;
        }

        try {
            if (Persona::query()->where('foto', $ruta)->exists()) {
                Log::warning(
                    'Se conservó la foto porque sigue referenciada.',
                    [
                        'ruta' => $ruta,
                        'contexto' => $contexto,
                    ]
                );

                return false;
            }

            $disco = Storage::disk('public');

            if (! $disco->exists($ruta)) {
                return true;
            }

            if (! $disco->delete($ruta)) {
                Log::warning('No se pudo eliminar una foto.', [
                    'ruta' => $ruta,
                    'contexto' => $contexto,
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Error al eliminar una foto.', [
                'ruta' => $ruta,
                'contexto' => $contexto,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function limpiarArchivoNuevo(?string $ruta): void
    {
        if ($ruta !== null) {
            $this->eliminarArchivoLocal(
                $ruta,
                'archivo nuevo tras una operación fallida'
            );
        }
    }
}
