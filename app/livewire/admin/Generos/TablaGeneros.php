<?php

namespace App\Livewire\Admin\Generos;

use App\Models\Genero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaGeneros extends Component
{
    use WithPagination;

    private const TAMANOS_PAGINA_VALIDOS = [10, 25, 50];
    private const CAMPOS_ORDENABLES = ['id_genero', 'nombre', 'descripcion', 'peliculas_count', 'activo'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $activo = '';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    #[Url(as: 'orden')]
    public string $sortField = 'nombre';

    #[Url(as: 'direccion')]
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $this->normalizarFiltros();
        $this->normalizarOrden();
        $this->normalizarTamanioPagina();
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedActivo(): void
    {
        $this->normalizarFiltros();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->normalizarTamanioPagina();
        $this->resetPage();
    }

    public function ordenarPor(string $campo): void
    {
        if (! in_array($campo, self::CAMPOS_ORDENABLES, true)) {
            return;
        }

        if ($this->sortField === $campo) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $campo;
            $this->sortDirection = $campo === 'id_genero' ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function indicadorOrden(string $campo): string
    {
        if ($this->sortField !== $campo) {
            return 'unfold_more';
        }

        return $this->sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward';
    }

    public function limpiarFiltros(): void
    {
        $this->q = '';
        $this->activo = '';
        $this->perPage = 10;
        $this->sortField = 'nombre';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    #[Computed]
    public function generos(): LengthAwarePaginator
    {
        return $this->consultaGeneros()->paginate($this->perPage);
    }

    private function consultaGeneros(): Builder
    {
        $consulta = Genero::query()->withCount('peliculas');
        $texto = trim($this->q);

        $consulta
            ->when($texto !== '', function (Builder $query) use ($texto): void {
                $query->where(function (Builder $subquery) use ($texto): void {
                    if (ctype_digit($texto)) {
                        $subquery->where('id_genero', (int) $texto)
                            ->orWhere('nombre', 'like', '%' . $texto . '%')
                            ->orWhere('descripcion', 'like', '%' . $texto . '%');
                    } else {
                        $subquery->where('nombre', 'like', '%' . $texto . '%')
                            ->orWhere('descripcion', 'like', '%' . $texto . '%');
                    }
                });
            })
            ->when(in_array($this->activo, ['0', '1'], true), fn (Builder $query) => $query->where('activo', (int) $this->activo));

        $campo = in_array($this->sortField, self::CAMPOS_ORDENABLES, true) ? $this->sortField : 'nombre';
        $direccion = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return $consulta->orderBy($campo, $direccion)->orderBy('id_genero', 'asc');
    }

    private function normalizarFiltros(): void
    {
        if (! in_array($this->activo, ['', '0', '1'], true)) {
            $this->activo = '';
        }
    }

    private function normalizarOrden(): void
    {
        if (! in_array($this->sortField, self::CAMPOS_ORDENABLES, true)) {
            $this->sortField = 'nombre';
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'asc';
        }
    }

    private function normalizarTamanioPagina(): void
    {
        if (! in_array($this->perPage, self::TAMANOS_PAGINA_VALIDOS, true)) {
            $this->perPage = 10;
        }
    }

    public function render()
    {
        return view('livewire.admin.generos.tabla-generos');
    }
}
