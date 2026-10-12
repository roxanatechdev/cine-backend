<?php

namespace App\Console\Commands;

use App\Models\PeliculaMultimedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AuditarAlmacenamientoMultimedia extends Command
{
    protected $signature = 'multimedia:auditar-almacenamiento';

    protected $description =
    'Audita las referencias y los archivos del almacenamiento multimedia.';

    public function handle(): int
    {
        try {
            $disco = Storage::disk('public');

            // Obtener las rutas locales referenciadas en la base de datos.
            $referencias = PeliculaMultimedia::query()
                ->where('url', 'like', 'peliculas/%')
                ->pluck('url')
                ->filter(
                    fn($ruta) =>
                    is_string($ruta) && $ruta !== ''
                )
                ->unique()
                ->values();

            // Obtener los archivos físicos del directorio administrado.
            $archivos = collect(
                $disco->allFiles('peliculas')
            )->unique()->values();

            // Rutas guardadas en BD cuyos archivos no existen.
            $faltantes = $referencias
                ->filter(fn($ruta) => ! $disco->exists($ruta))
                ->values();

            // Archivos físicos sin ninguna referencia en BD.
            $referenciasSet = $referencias->flip();

            $huerfanos = $archivos
                ->reject(
                    fn($ruta) =>
                    $referenciasSet->has($ruta)
                )
                ->values();

            $this->newLine();
            $this->info('AUDITORÍA DEL ALMACENAMIENTO MULTIMEDIA');
            $this->line('----------------------------------------');

            $this->line(
                'Referencias locales únicas: ' . $referencias->count()
            );

            $this->line(
                'Archivos físicos encontrados: ' . $archivos->count()
            );

            $this->newLine();

            if ($faltantes->isNotEmpty()) {
                $this->error(
                    'Referencias sin archivo: ' . $faltantes->count()
                );

                foreach ($faltantes as $ruta) {
                    $this->line("  - {$ruta}");
                }
            } else {
                $this->info('No se encontraron referencias rotas.');
            }

            $this->newLine();

            if ($huerfanos->isNotEmpty()) {
                $this->warn(
                    'Archivos sin referencia: ' . $huerfanos->count()
                );

                foreach ($huerfanos as $ruta) {
                    $this->line("  - {$ruta}");
                }
            } else {
                $this->info('No se encontraron archivos huérfanos.');
            }

            $this->newLine();

            if (
                $faltantes->isEmpty() &&
                $huerfanos->isEmpty()
            ) {
                $this->info(
                    'Auditoría finalizada: no se detectaron inconsistencias.'
                );

                return self::SUCCESS;
            }

            $this->warn(
                'Auditoría finalizada con incidencias. No se modificó ningún archivo.'
            );

            return self::FAILURE;
        } catch (Throwable $e) {
            report($e);

            $this->error(
                'No se pudo completar la auditoría. Revisa el log de Laravel.'
            );

            return self::FAILURE;
        }
    }
}
