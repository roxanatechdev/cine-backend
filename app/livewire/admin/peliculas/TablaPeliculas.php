<?php

namespace App\Livewire\Admin\Peliculas;

use App\Models\Genero;
use App\Models\Idioma;
use App\Models\Pelicula;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaPeliculas extends Component
{
    use WithPagination;

    private const COLUMNAS_ORDENABLES = [
        'id_pelicula',
        'titulo',
        'genero',
        'clasificacion',
        'fecha_estreno',
        'estado',
    ];

    private const DIRECCIONES_VALIDAS = ['asc', 'desc'];

    private const TAMANOS_PAGINA_VALIDOS = [10, 25, 50, 100];

    private const ESTADOS_VALIDOS = [
        'BORRADOR',
        'ACTIVA',
        'INACTIVA',
    ];

    private const CLASIFICACIONES_VALIDAS = [
        'G',
        'PG',
        'PG-13',
        'R',
        'NC-17',
    ];

    #[Url]
    public string $sort = 'id_pelicula';

    #[Url]
    public string $direction = 'desc';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    #[Url]
    public string $q = '';

    #[Url]
    public string $estado = '';

    #[Url]
    public string $clasificacion = '';

    public function mount(): void
    {
        $this->normalizarOrdenamiento();
        $this->normalizarFiltros();
        $this->normalizarTamanioPagina();
    }

    /** Normaliza el orden recibido desde la URL antes de consultar. */
    private function normalizarOrdenamiento(): void
    {
        if (! in_array($this->sort, self::COLUMNAS_ORDENABLES, true)) {
            $this->sort = 'id_pelicula';
        }

        if (! in_array($this->direction, self::DIRECCIONES_VALIDAS, true)) {
            $this->direction = 'desc';
        }
    }

    /** Garantiza que los filtros coincidan con los valores admitidos. */
    private function normalizarFiltros(): void
    {
        if (
            $this->estado !== ''
            && ! in_array($this->estado, self::ESTADOS_VALIDOS, true)
        ) {
            $this->estado = '';
        }

        if (
            $this->clasificacion !== ''
            && ! in_array($this->clasificacion, self::CLASIFICACIONES_VALIDAS, true)
        ) {
            $this->clasificacion = '';
        }
    }

    private function normalizarTamanioPagina(): void
    {
        if (! in_array($this->perPage, self::TAMANOS_PAGINA_VALIDOS, true)) {
            $this->perPage = 10;
        }
    }

    public function sortBy(string $column): void
    {
        // Nunca se usa una columna recibida del cliente sin validar.
        if (! in_array($column, self::COLUMNAS_ORDENABLES, true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }

        $this->resetPage();
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->normalizarFiltros();
        $this->resetPage();
    }

    public function updatedClasificacion(): void
    {
        $this->normalizarFiltros();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->normalizarTamanioPagina();
        $this->resetPage();
    }

    /**
     * Resultados paginados para el catálogo.
     * La consulta queda separada para que sus filtros y orden sean más fáciles de mantener.
     */
    #[Computed]
    public function peliculas(): LengthAwarePaginator
    {
        return $this->consultaPeliculas()->paginate(
            $this->perPage,
            ['*'],
            'page'
        );
    }

    private function consultaPeliculas(): Builder
    {
        $consulta = Pelicula::query()
            ->with('generoPrincipal')
            ->with([
                'multimedia' => static function (HasMany $consulta): void {
                    $consulta
                        ->where('activo', 1)
                        ->whereIn('tipo', ['POSTER', 'IMAGEN'])
                        ->orderByRaw("
                            CASE
                                WHEN tipo = 'POSTER' AND es_principal = 1 THEN 0
                                WHEN tipo = 'POSTER' THEN 1
                                WHEN tipo = 'IMAGEN' AND es_principal = 1 THEN 2
                                ELSE 3
                            END
                        ")
                        ->orderBy('orden')
                        ->orderBy('id_multimedia');
                },
            ]);

        $textoBusqueda = trim($this->q);

        $consulta
            ->when(
                $textoBusqueda !== '',
                static fn (Builder $query) => $query->where(
                    'titulo',
                    'like',
                    '%' . $textoBusqueda . '%'
                )
            )
            ->when(
                $this->estado !== '',
                fn (Builder $query) => $query->where('estado', $this->estado)
            )
            ->when(
                $this->clasificacion !== '',
                fn (Builder $query) => $query->where('clasificacion', $this->clasificacion)
            );

        $this->aplicarOrdenamiento($consulta);

        return $consulta;
    }

    private function aplicarOrdenamiento(Builder $consulta): void
    {
        // Se vuelve a validar para mantener segura la consulta incluso si cambia el estado interno.
        $columna = in_array($this->sort, self::COLUMNAS_ORDENABLES, true)
            ? $this->sort
            : 'id_pelicula';

        $direccion = in_array($this->direction, self::DIRECCIONES_VALIDAS, true)
            ? $this->direction
            : 'desc';

        if ($columna === 'genero') {
            $consulta->orderBy(
                Genero::query()
                    ->select('nombre')
                    ->whereColumn('genero.id_genero', 'pelicula.id_genero_principal'),
                $direccion
            );
        } else {
            $consulta->orderBy($columna, $direccion);
        }

        // Desempate determinista para que los resultados no salten entre páginas.
        if ($columna !== 'id_pelicula') {
            $consulta->orderByDesc('id_pelicula');
        }
    }

    /** Conservado por compatibilidad con posibles acciones existentes en la vista. */
    public function buscar(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->q = '';
        $this->estado = '';
        $this->clasificacion = '';

        $this->resetPage();
    }

    /** Impide ejecutar acciones administrativas desde una petición Livewire no autorizada. */
    private function asegurarAdministrador(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->role === 'admin',
            403,
            'No tienes permiso para realizar esta acción.'
        );
    }

    public function desactivarPelicula(int $idPelicula): void
    {
        $this->asegurarAdministrador();

        $pelicula = DB::transaction(function () use ($idPelicula): ?Pelicula {
            $pelicula = Pelicula::query()
                ->lockForUpdate()
                ->findOrFail($idPelicula);

            // La operación solo corresponde a películas actualmente activas.
            if ($pelicula->estado !== 'ACTIVA') {
                return null;
            }

            $pelicula->estado = 'INACTIVA';
            $pelicula->save();

            return $pelicula;
        });

        if ($pelicula === null) {
            return;
        }

        unset($this->peliculas);

        session()->flash(
            'success',
            "La película «{$pelicula->titulo}» ha sido desactivada correctamente."
        );
    }

    public function activarPelicula(int $idPelicula): void
    {
        $this->asegurarAdministrador();

        $resultado = DB::transaction(function () use ($idPelicula): array {
            $pelicula = Pelicula::query()
                ->lockForUpdate()
                ->findOrFail($idPelicula);

            if (! in_array($pelicula->estado, ['BORRADOR', 'INACTIVA'], true)) {
                return ['pelicula' => null, 'error' => null];
            }

            if (! filled($pelicula->titulo) || ! filled($pelicula->sinopsis)) {
                return ['pelicula' => null, 'error' => 'La película debe tener título y sinopsis.'];
            }

            $generoActivo = $pelicula->id_genero_principal !== null
                && Genero::query()
                    ->where('id_genero', $pelicula->id_genero_principal)
                    ->where('activo', 1)
                    ->exists();

            if (! $generoActivo) {
                return ['pelicula' => null, 'error' => 'Asigna un género principal activo.'];
            }

            $idiomaActivo = filled($pelicula->idioma_original_codigo)
                && Idioma::query()
                    ->where('codigo', $pelicula->idioma_original_codigo)
                    ->where('activo', 1)
                    ->exists();

            if (! $idiomaActivo) {
                return ['pelicula' => null, 'error' => 'Asigna un idioma original activo.'];
            }

            $tienePosterPrincipal = $pelicula->multimedia()
                ->where('tipo', 'POSTER')
                ->where('activo', 1)
                ->where('es_principal', 1)
                ->exists();

            if (! $tienePosterPrincipal) {
                return ['pelicula' => null, 'error' => 'Registra un póster activo y principal.'];
            }

            $pelicula->estado = 'ACTIVA';
            $pelicula->save();

            return ['pelicula' => $pelicula, 'error' => null];
        });

        if ($resultado['error'] !== null) {
            session()->flash(
                'error',
                'No se puede activar la película: ' . $resultado['error']
            );

            return;
        }

        /** @var Pelicula|null $pelicula */
        $pelicula = $resultado['pelicula'];

        if ($pelicula === null) {
            return;
        }

        unset($this->peliculas);

        session()->flash(
            'success',
            "La película «{$pelicula->titulo}» está activa."
        );
    }

    public function render()
    {
        return view('livewire.admin.peliculas.tabla-peliculas');
    }
}
