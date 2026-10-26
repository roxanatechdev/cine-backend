<?php

namespace App\Livewire\Admin\Paises;

use App\Models\Pais;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaPaises extends Component
{
    use WithPagination;

    private const TAMANOS_PAGINA_VALIDOS = [10, 25, 50];
    private const CAMPOS_ORDENABLES = ['codigo', 'nombre', 'peliculas_count'];

    #[Url]
    public string $q = '';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    #[Url(as: 'orden')]
    public string $sortField = 'nombre';

    #[Url(as: 'direccion')]
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $this->normalizarOrden();
        $this->normalizarTamanioPagina();
    }

    public function updatedQ(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->normalizarTamanioPagina(); $this->resetPage(); }

    public function ordenarPor(string $campo): void
    {
        if (! in_array($campo, self::CAMPOS_ORDENABLES, true)) return;
        if ($this->sortField === $campo) $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        else { $this->sortField = $campo; $this->sortDirection = 'asc'; }
        $this->resetPage();
    }

    public function indicadorOrden(string $campo): string
    {
        if ($this->sortField !== $campo) return 'unfold_more';
        return $this->sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward';
    }

    public function limpiarFiltros(): void
    {
        $this->q = ''; $this->perPage = 10; $this->sortField = 'nombre'; $this->sortDirection = 'asc'; $this->resetPage();
    }

    #[Computed]
    public function paises(): LengthAwarePaginator
    {
        return $this->consultaPaises()->paginate($this->perPage);
    }

    private function consultaPaises(): Builder
    {
        $consulta = Pais::query()->withCount('peliculas');
        $texto = trim($this->q);
        $consulta->when($texto !== '', function (Builder $query) use ($texto): void {
            $query->where(fn (Builder $subquery) => $subquery
                ->where('codigo', 'like', '%' . $texto . '%')
                ->orWhere('nombre', 'like', '%' . $texto . '%'));
        });
        $campo = in_array($this->sortField, self::CAMPOS_ORDENABLES, true) ? $this->sortField : 'nombre';
        $direccion = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        return $consulta->orderBy($campo, $direccion)->orderBy('codigo', 'asc');
    }

    private function normalizarOrden(): void
    {
        if (! in_array($this->sortField, self::CAMPOS_ORDENABLES, true)) $this->sortField = 'nombre';
        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) $this->sortDirection = 'asc';
    }

    private function normalizarTamanioPagina(): void
    {
        if (! in_array($this->perPage, self::TAMANOS_PAGINA_VALIDOS, true)) $this->perPage = 10;
    }

    public function render() { return view('livewire.admin.paises.tabla-paises'); }
}
