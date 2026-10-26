<?php

namespace App\Livewire\Admin\Personas;

use App\Models\Persona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaPersonas extends Component
{
    use WithPagination;

    private const COLUMNAS_ORDENABLES = [
        'id_persona', 'nombre_completo', 'nombre_busqueda',
    ];

    private const DIRECCIONES_VALIDAS = ['asc', 'desc'];
    private const TAMANOS_PAGINA_VALIDOS = [10, 25, 50, 100];

    #[Url]
    public string $q = '';

    #[Url]
    public string $sort = 'nombre_completo';

    #[Url]
    public string $direction = 'asc';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    public function mount(): void
    {
        $this->normalizarOrdenamiento();
        $this->normalizarTamanioPagina();
    }

    private function normalizarOrdenamiento(): void
    {
        if (! in_array($this->sort, self::COLUMNAS_ORDENABLES, true)) {
            $this->sort = 'nombre_completo';
        }

        if (! in_array($this->direction, self::DIRECCIONES_VALIDAS, true)) {
            $this->direction = 'asc';
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

    public function updatedPerPage(): void
    {
        $this->normalizarTamanioPagina();
        $this->resetPage();
    }

    #[Computed]
    public function personas(): LengthAwarePaginator
    {
        $consulta = Persona::query();
        $texto = trim($this->q);

        if ($texto !== '') {
            $consulta->where(function (Builder $query) use ($texto): void {
                $query->where('nombre_completo', 'like', '%' . $texto . '%')
                    ->orWhere('nombre_busqueda', 'like', '%' . $texto . '%');
            });
        }

        $columna = in_array($this->sort, self::COLUMNAS_ORDENABLES, true)
            ? $this->sort
            : 'nombre_completo';
        $direccion = in_array($this->direction, self::DIRECCIONES_VALIDAS, true)
            ? $this->direction
            : 'asc';

        return $consulta->orderBy($columna, $direccion)
            ->orderBy('id_persona')
            ->paginate($this->perPage, ['*'], 'page');
    }

    /** Devuelve una URL pública para rutas locales o conserva URLs externas. */
    public function urlFoto(?string $foto): ?string
    {
        if ($foto === null || trim($foto) === '') {
            return null;
        }

        if (str_starts_with($foto, 'personas/')) {
            return asset('storage/' . ltrim($foto, '/'));
        }

        return $foto;
    }

    public function render()
    {
        return view('livewire.admin.personas.tabla-personas');
    }
}
