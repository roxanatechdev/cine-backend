<?php

namespace App\Livewire\Admin\Salas;

use App\Models\Sala;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaSalas extends Component
{
    use WithPagination;

    private const ESTADOS_VALIDOS = ['ACTIVA', 'INACTIVA', 'MANTENIMIENTO'];
    private const TAMANOS_PAGINA_VALIDOS = [10, 25, 50];
    private const CAMPOS_ORDENABLES = ['nombre', 'capacidad', 'margen_limpieza_minutos', 'estado'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $estado = '';

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

    public function updatedEstado(): void
    {
        if ($this->estado !== '' && ! in_array($this->estado, self::ESTADOS_VALIDOS, true)) {
            $this->estado = '';
        }

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
            $this->sortDirection = $campo === 'capacidad' ? 'desc' : 'asc';
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
        $this->estado = '';
        $this->perPage = 10;
        $this->sortField = 'nombre';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    #[Computed]
    public function salas(): LengthAwarePaginator
    {
        return $this->consultaSalas()->paginate($this->perPage);
    }

    private function consultaSalas(): Builder
    {
        $consulta = Sala::query();
        $texto = trim($this->q);

        $consulta
            ->when($texto !== '', fn (Builder $query) => $query->where('nombre', 'like', '%' . $texto . '%'))
            ->when($this->estado !== '', fn (Builder $query) => $query->where('estado', $this->estado));

        $direccion = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $campo = in_array($this->sortField, self::CAMPOS_ORDENABLES, true) ? $this->sortField : 'nombre';

        $consulta->orderBy($campo, $direccion)->orderBy('id_sala', 'asc');

        return $consulta;
    }

    private function normalizarFiltros(): void
    {
        if ($this->estado !== '' && ! in_array($this->estado, self::ESTADOS_VALIDOS, true)) {
            $this->estado = '';
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
        return view('livewire.admin.salas.tabla-salas');
    }
}
