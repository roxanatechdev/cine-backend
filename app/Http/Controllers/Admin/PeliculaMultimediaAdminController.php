<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pelicula;
use App\Models\PeliculaMultimedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;

class PeliculaMultimediaAdminController extends Controller
{
    private const TIPOS = [
        'POSTER',
        'TRAILER',
        'IMAGEN',
        'VIDEO',
    ];

    /**
     * Listar recursos multimedia de una película.
     */
    public function index(Pelicula $pelicula)
    {
        // Conservar la ruta pública anterior, centralizando la navegación
        // en la pestaña Multimedia del editor unificado.
        return redirect()->route('admin.peliculas.edit', [
            'pelicula' => $pelicula,
            'tab' => 'multimedia',
        ]);
    }

    /**
     * Mostrar el formulario para registrar un recurso.
     */
    public function create(Pelicula $pelicula)
    {
        return view('admin.multimedia.form', [
            'pelicula' => $pelicula,
            'multimedia' => new PeliculaMultimedia(),
            'modo' => 'crear',
        ]);
    }


    private function reglas(): array
    {
        return [
            'tipo' => [
                'required',
                'string',
                Rule::in(self::TIPOS),
            ],
            'url' => [
                'nullable',
                'string',
                'max:500',
            ],
            'archivo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,avif',
                'max:5120',
            ],
            'texto_alternativo' => [
                'nullable',
                'string',
                'max:255',
            ],
            'es_principal' => [
                'sometimes',
                'boolean',
            ],
            'orden' => [
                'sometimes',
                'integer',
                'min:0',
                'max:65535',
            ],
            'activo' => [
                'sometimes',
                'boolean',
            ],
        ];
    }


    /**
     * Registrar un recurso multimedia.
     */


    public function store(Request $request, Pelicula $pelicula)
    {
        $archivoNuevo = null;

        try {
            $datos = $request->validate($this->reglas());

            $datos['url'] = $this->resolverFuente(
                $request,
                $datos['tipo']
            );

            // Registrar la ruta para poder limpiar el archivo
            // si falla posteriormente la operación.
            if (
                $request->hasFile('archivo') &&
                is_string($datos['url']) &&
                str_starts_with($datos['url'], 'peliculas/')
            ) {
                $archivoNuevo = $datos['url'];
            }

            unset($datos['archivo']);

            $datos['id_pelicula'] = $pelicula->id_pelicula;
            $datos['es_principal'] = $request->boolean('es_principal');
            $datos['activo'] = $request->boolean('activo', true);

            DB::transaction(function () use ($datos, $pelicula) {
                // Serializar cambios de principal por película para reducir
                // el riesgo de dos recursos principales simultáneos.
                $peliculaBloqueada = Pelicula::query()
                    ->whereKey($pelicula->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($datos['es_principal'] && $datos['activo']) {
                    $this->quitarPrincipal($peliculaBloqueada, $datos['tipo']);
                }

                PeliculaMultimedia::create($datos);
            });

            return redirect()
                ->route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'multimedia'])
                ->with('success', 'Recurso multimedia registrado correctamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->limpiarArchivoNuevo($archivoNuevo);

            Log::error('Error al registrar un recurso multimedia.', [
                'id_pelicula' => $pelicula->id_pelicula,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('archivo'))
                ->withErrors([
                    'almacenamiento' =>
                    'No se pudo guardar el recurso. Verifica los datos e inténtalo nuevamente.',
                ]);
        }
    }



    /**
     * Mostrar el formulario de edición.
     */
    public function edit(
        Pelicula $pelicula,
        PeliculaMultimedia $multimedia
    ) {
        $this->verificarPertenencia($pelicula, $multimedia);

        return view('admin.multimedia.form', [
            'pelicula' => $pelicula,
            'multimedia' => $multimedia,
            'modo' => 'editar',
        ]);
    }

    /**
     * Actualizar un recurso multimedia.
     */


    public function update(
        Request $request,
        Pelicula $pelicula,
        PeliculaMultimedia $multimedia
    ) {
        $this->verificarPertenencia($pelicula, $multimedia);

        $archivoNuevo = null;

        try {
            $datos = $request->validate($this->reglas());

            // Una película publicada debe conservar un póster principal activo.
            if ($pelicula->estado === 'ACTIVA') {
                $seguiraSiendoPosterPrincipal =
                    $datos['tipo'] === 'POSTER' &&
                    $request->boolean('es_principal') &&
                    $request->boolean('activo');

                $existeOtroPosterPrincipal = $pelicula->multimedia()
                    ->where('tipo', 'POSTER')
                    ->where('activo', 1)
                    ->where('es_principal', 1)
                    ->where('id_multimedia', '!=', $multimedia->id_multimedia)
                    ->exists();

                if (! $seguiraSiendoPosterPrincipal && ! $existeOtroPosterPrincipal) {
                    throw ValidationException::withMessages([
                        'es_principal' => 'Una película activa debe conservar al menos un póster activo marcado como principal.',
                    ]);
                }
            }

            $urlAnterior = $multimedia->url;

            $urlNueva = $this->resolverFuente(
                $request,
                $datos['tipo'],
                $multimedia
            );

            if (
                $request->hasFile('archivo') &&
                is_string($urlNueva) &&
                str_starts_with($urlNueva, 'peliculas/')
            ) {
                $archivoNuevo = $urlNueva;
            }

            $datos['url'] = $urlNueva;
            unset($datos['archivo']);

            $datos['es_principal'] = $request->boolean('es_principal');
            $datos['activo'] = $request->boolean('activo');

            DB::transaction(function () use (
                $pelicula,
                $multimedia,
                $datos
            ) {
                $peliculaBloqueada = Pelicula::query()
                    ->whereKey($pelicula->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($datos['es_principal'] && $datos['activo']) {
                    $this->quitarPrincipal(
                        $peliculaBloqueada,
                        $datos['tipo'],
                        $multimedia->id_multimedia
                    );
                }

                $multimedia->update($datos);
            });

            // Solo eliminar el archivo anterior después de confirmar
            // la actualización en la base de datos.
            if (
                $urlAnterior !== $urlNueva &&
                str_starts_with($urlAnterior, 'peliculas/')
            ) {
                $this->eliminarArchivoLocal(
                    $urlAnterior,
                    'archivo anterior del recurso multimedia'
                );
            }

            return redirect()
                ->route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'multimedia'])
                ->with('success', 'Recurso multimedia actualizado correctamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->limpiarArchivoNuevo($archivoNuevo);

            Log::error('Error al actualizar un recurso multimedia.', [
                'id_pelicula' => $pelicula->id_pelicula,
                'id_multimedia' => $multimedia->id_multimedia,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('archivo'))
                ->withErrors([
                    'almacenamiento' =>
                    'No se pudo actualizar el recurso. Inténtalo nuevamente.',
                ]);
        }
    }



    /**
     * Comprobar si la URL corresponde al formato admitido para imágenes.
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
            empty($partes['host'])
        ) {
            return false;
        }

        $ruta = strtolower($partes['path'] ?? '');

        return preg_match(
            '/\.(jpg|jpeg|png|webp|avif)$/i',
            $ruta
        ) === 1;
    }

    /**
     * Comprobar que el enlace pertenece a un dominio admitido de YouTube.
     */
    private function esUrlDeYoutube(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $partes = parse_url($url);

        if (
            ! is_array($partes) ||
            ! in_array(
                strtolower($partes['scheme'] ?? ''),
                ['https'],
                true
            )
        ) {
            return false;
        }

        $host = strtolower($partes['host'] ?? '');

        $hostsPermitidos = [
            'youtube.com',
            'www.youtube.com',
            'm.youtube.com',
            'youtu.be',
            'www.youtube-nocookie.com',
            'youtube-nocookie.com',
        ];

        if (! in_array($host, $hostsPermitidos, true)) {
            return false;
        }

        $ruta = $partes['path'] ?? '';

        if ($host === 'youtu.be') {
            return preg_match(
                '#^/[A-Za-z0-9_-]{11}/?$#',
                $ruta
            ) === 1;
        }

        parse_str($partes['query'] ?? '', $consulta);

        if (
            $ruta === '/watch' &&
            isset($consulta['v']) &&
            preg_match('/^[A-Za-z0-9_-]{11}$/', $consulta['v'])
        ) {
            return true;
        }

        return preg_match(
            '#^/(embed|shorts)/[A-Za-z0-9_-]{11}/?$#',
            $ruta
        ) === 1;
    }


    /**
     * Dejar sin principal al recurso anterior del mismo tipo.
     */
    private function quitarPrincipal(
        Pelicula $pelicula,
        string $tipo,
        ?int $exceptoId = null
    ): void {
        $query = PeliculaMultimedia::query()
            ->where('id_pelicula', $pelicula->id_pelicula)
            ->where('tipo', $tipo)
            ->where('activo', 1)
            ->where('es_principal', 1);

        if ($exceptoId !== null) {
            $query->where('id_multimedia', '!=', $exceptoId);
        }

        $query->update(['es_principal' => 0]);
    }

    /**
     * Evitar editar un recurso perteneciente a otra película.
     */
    private function verificarPertenencia(
        Pelicula $pelicula,
        PeliculaMultimedia $multimedia
    ): void {
        if (
            (int) $multimedia->id_pelicula !== (int) $pelicula->id_pelicula
        ) {
            abort(404);
        }
    }



    private function resolverFuente(
        Request $request,
        string $tipo,
        ?PeliculaMultimedia $actual = null
    ): string {
        $esImagen = in_array(
            $tipo,
            ['POSTER', 'IMAGEN'],
            true
        );

        // Las imágenes pueden ser archivos locales o URLs HTTPS.
        if ($esImagen) {
            if ($request->hasFile('archivo')) {
                if ($request->filled('url')) {
                    throw ValidationException::withMessages([
                        'url' => 'Elige subir una imagen o indicar una URL, no ambas.',
                    ]);
                }

                $ruta = $request->file('archivo')
                    ->store('peliculas', 'public');

                if (! is_string($ruta) || $ruta === '') {
                    throw new \RuntimeException(
                        'No se pudo guardar el archivo multimedia.'
                    );
                }

                return $ruta;
            }

            // Si se proporciona una URL, es la nueva fuente solicitada.
            if ($request->filled('url')) {
                $url = trim($request->input('url'));

                if (! $this->esUrlDeImagen($url)) {
                    throw ValidationException::withMessages([
                        'url' => 'Introduce una URL HTTPS válida de imagen.',
                    ]);
                }

                return $url;
            }

            // Si no se indicó una fuente nueva, conservar la actual
            // siempre que sea compatible con el tipo seleccionado.
            if ($actual !== null) {
                $urlActual = $actual->url;

                if (str_starts_with($urlActual, 'peliculas/')) {
                    if (! Storage::disk('public')->exists($urlActual)) {
                        throw ValidationException::withMessages([
                            'archivo' => 'La imagen actual ya no existe en el almacenamiento. Sube otra imagen o proporciona una URL.',
                        ]);
                    }

                    return $urlActual;
                }

                if ($this->esUrlDeImagen($urlActual)) {
                    return $urlActual;
                }

                throw ValidationException::withMessages([
                    'url' => 'La fuente actual no es válida para una imagen. Proporciona una nueva fuente.',
                ]);
            }

            throw ValidationException::withMessages([
                'archivo' => 'Sube una imagen o proporciona una URL pública.',
            ]);
        }

        // Los vídeos solo admiten enlaces de YouTube.
        if ($request->hasFile('archivo')) {
            throw ValidationException::withMessages([
                'archivo' => 'Los videos deben registrarse mediante una URL de YouTube.',
            ]);
        }

        if ($request->filled('url')) {
            $url = trim($request->input('url'));

            if (! $this->esUrlDeYoutube($url)) {
                throw ValidationException::withMessages([
                    'url' => 'Introduce un enlace válido de YouTube.',
                ]);
            }

            return $url;
        }

        // Si no se cambia el enlace, conservar el actual únicamente
        // si es válido para el tipo de vídeo.
        if ($actual !== null) {
            if ($this->esUrlDeYoutube($actual->url)) {
                return $actual->url;
            }

            throw ValidationException::withMessages([
                'url' => 'La fuente actual no es un enlace válido de YouTube. Proporciona un enlace nuevo.',
            ]);
        }

        throw ValidationException::withMessages([
            'url' => 'Debes proporcionar un enlace de YouTube.',
        ]);
    }



    /**
     * Limpiar un archivo nuevo cuando falla la operación principal.
     */
    private function limpiarArchivoNuevo(?string $ruta): void
    {
        if ($ruta === null) {
            return;
        }

        $this->eliminarArchivoLocal(
            $ruta,
            'archivo nuevo tras una operación fallida'
        );
    }

    /**
     * Eliminar un archivo local de forma controlada.
     */
    private function eliminarArchivoLocal(
        string $ruta,
        string $contexto
    ): bool {
        // Limitar la eliminación a archivos directamente dentro
        // del directorio administrado para imágenes.
        if (
            ! str_starts_with($ruta, 'peliculas/') ||
            str_contains($ruta, '..') ||
            basename($ruta) !== substr($ruta, strlen('peliculas/'))
        ) {
            Log::warning('Se rechazó una ruta de archivo no permitida.', [
                'contexto' => $contexto,
            ]);

            return false;
        }

        try {
            if (PeliculaMultimedia::query()->where('url', $ruta)->exists()) {
                Log::warning(
                    'Se conservó un archivo multimedia porque sigue referenciado.',
                    ['ruta' => $ruta, 'contexto' => $contexto]
                );

                return false;
            }

            $disco = Storage::disk('public');

            // Si el archivo ya no existe, no hay nada que eliminar.
            if (! $disco->exists($ruta)) {
                return true;
            }

            if (! $disco->delete($ruta)) {
                Log::warning('No se pudo eliminar un archivo multimedia.', [
                    'contexto' => $contexto,
                    'ruta' => $ruta,
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Excepción al eliminar un archivo multimedia.', [
                'contexto' => $contexto,
                'ruta' => $ruta,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }


    /**
     * Desactivar un recurso sin borrar su registro ni su archivo.
     */
    public function desactivar(
        Pelicula $pelicula,
        PeliculaMultimedia $multimedia
    ) {
        $this->verificarPertenencia($pelicula, $multimedia);

        if (
            $pelicula->estado === 'ACTIVA' &&
            $multimedia->tipo === 'POSTER' &&
            $multimedia->activo &&
            $multimedia->es_principal
        ) {
            $existeOtroPosterPrincipal = $pelicula->multimedia()
                ->where('tipo', 'POSTER')
                ->where('activo', 1)
                ->where('es_principal', 1)
                ->where('id_multimedia', '!=', $multimedia->id_multimedia)
                ->exists();

            if (! $existeOtroPosterPrincipal) {
                throw ValidationException::withMessages([
                    'multimedia' => 'No puedes desactivar el único póster principal de una película activa. Asigna primero otro póster principal o cambia la película a inactiva.',
                ]);
            }
        }

        DB::transaction(function () use ($pelicula, $multimedia) {
            Pelicula::query()
                ->whereKey($pelicula->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $recurso = $pelicula->multimedia()
                ->whereKey($multimedia->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $recurso->update([
                'activo' => false,
                'es_principal' => false,
            ]);
        });

        return redirect()
            ->route('admin.peliculas.edit', ['pelicula' => $pelicula, 'tab' => 'multimedia'])
            ->with(
                'success',
                'Recurso desactivado correctamente. El archivo se conserva.'
            );
    }
}
