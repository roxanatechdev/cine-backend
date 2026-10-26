<?php

namespace App\Livewire\Admin\CategoriasProducto;

use App\Models\CategoriaProducto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaCategoriasProducto extends Component
{
    use WithPagination;

    private const TAMANOS_PAGINA_VALIDOS = [10, 25, 50];
    private const CAMPOS_ORDENABLES = ['id_categoria_producto', 'nombre', 'descripcion', 'productos_count', 'activo'];

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
        $this->q = mb_substr($this->q, 0, 80);
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

    /** Cambia el estado sin abandonar la petición reactiva ni modificar los filtros. */
    public function alternarEstado(int $idCategoria): void
    {
        $categoria = CategoriaProducto::query()->findOrFail($idCategoria);
        $categoria->activo = ! (bool) $categoria->activo;
        $categoria->save();

        session()->flash(
            'success',
            $categoria->activo
                ? 'Categoría activada correctamente.'
                : 'Categoría desactivada correctamente.'
        );

        unset($this->categorias);
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
            $this->sortDirection = $campo === 'id_categoria_producto' ? 'desc' : 'asc';
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
    public function categorias(): LengthAwarePaginator
    {
        return $this->consultaCategorias()->paginate($this->perPage);
    }

    private function consultaCategorias(): Builder
    {
        $consulta = CategoriaProducto::query()->withCount('productos');
        $texto = trim($this->q);

        $consulta
            ->when($texto !== '', function (Builder $query) use ($texto): void {
                $query->where(function (Builder $subquery) use ($texto): void {
                    if (ctype_digit($texto)) {
                        $subquery->where('id_categoria_producto', (int) $texto)
                            ->orWhere('nombre', 'like', '%' . $texto . '%')
                            ->orWhere('descripcion', 'like', '%' . $texto . '%');
                    } else {
                        $subquery->where('nombre', 'like', '%' . $texto . '%')
                            ->orWhere('descripcion', 'like', '%' . $texto . '%');
                    }
                });
            })
            ->when(in_array($this->activo, ['0', '1'], true), fn (Builder $query) => $query->where('activo', (int) $this->activo));

        $campo = in_array($this->sortField, self::CAMPOS_ORDENABLES, true)
            ? $this->sortField
            : 'nombre';
        $direccion = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return $consulta->orderBy($campo, $direccion)->orderBy('id_categoria_producto', 'asc');
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
        return view('livewire.admin.categorias-producto.tabla-categorias-producto');
    }
}
