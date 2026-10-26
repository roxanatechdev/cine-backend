<?php

namespace App\Livewire\Admin\Productos;

use App\Models\CategoriaProducto;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaProductos extends Component
{
    use WithPagination;

    private const ESTADOS_VALIDOS = ['ACTIVO', 'INACTIVO', 'AGOTADO'];
    private const CAMPOS_ORDENABLES = ['id_producto', 'nombre', 'categoria', 'precio_actual', 'stock', 'estado', 'es_promocion'];
    private const TAMANOS_PAGINA_VALIDOS = [10, 25, 50, 100];

    #[Url]
    public string $q = '';

    #[Url]
    public string $estado = '';

    #[Url]
    public string $categoria = '';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    #[Url(as: 'orden')]
    public string $sortField = 'nombre';

    #[Url(as: 'direccion')]
    public string $sortDirection = 'asc';

    /** Estado elegido en el selector de cada fila, antes de guardarlo. */
    public array $estadosSeleccionados = [];

    public function mount(): void
    {
        $this->normalizarFiltros();
        $this->normalizarOrden();
        $this->normalizarTamanioPagina();
    }

    public function updatedQ(): void
    {
        $this->q = mb_substr($this->q, 0, 100);
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->normalizarFiltros();
        $this->resetPage();
    }

    public function updatedCategoria(): void
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
            $this->sortDirection = $campo === 'id_producto' ? 'desc' : 'asc';
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
        $this->categoria = '';
        $this->perPage = 10;
        $this->sortField = 'nombre';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    /** Guarda un estado permitido sin redirigir ni modificar los filtros activos. */
    public function guardarEstado(int $idProducto): void
    {
        $datos = $this->validate([
            'estadosSeleccionados.' . $idProducto => ['required', Rule::in(self::ESTADOS_VALIDOS)],
        ]);

        $producto = Producto::query()->findOrFail($idProducto);
        $producto->estado = $datos['estadosSeleccionados'][$idProducto];
        $producto->save();

        $this->estadosSeleccionados[$idProducto] = $producto->estado;

        session()->flash('success', 'Estado del producto actualizado correctamente.');
        unset($this->productos);
    }

    #[Computed]
    public function productos(): LengthAwarePaginator
    {
        return $this->consultaProductos()->paginate($this->perPage);
    }

    #[Computed]
    public function categoriasFiltro()
    {
        return CategoriaProducto::query()->orderBy('nombre')->get(['id_categoria_producto', 'nombre']);
    }

    private function consultaProductos(): Builder
    {
        $consulta = Producto::query()->with('categoria');
        $texto = trim($this->q);

        $consulta
            ->when($texto !== '', function (Builder $query) use ($texto): void {
                $query->where(function (Builder $subquery) use ($texto): void {
                    if (ctype_digit($texto)) {
                        $subquery->where('id_producto', (int) $texto)
                            ->orWhere('nombre', 'like', '%' . $texto . '%')
                            ->orWhere('descripcion', 'like', '%' . $texto . '%');
                    } else {
                        $subquery->where('nombre', 'like', '%' . $texto . '%')
                            ->orWhere('descripcion', 'like', '%' . $texto . '%');
                    }
                });
            })
            ->when($this->estado !== '', fn (Builder $query) => $query->where('estado', $this->estado))
            ->when($this->categoria !== '', fn (Builder $query) => $query->where('id_categoria_producto', (int) $this->categoria));

        $campo = in_array($this->sortField, self::CAMPOS_ORDENABLES, true) ? $this->sortField : 'nombre';
        $direccion = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        if ($campo === 'categoria') {
            $consulta->orderBy(
                CategoriaProducto::query()
                    ->select('nombre')
                    ->whereColumn('categoria_producto.id_categoria_producto', 'producto.id_categoria_producto'),
                $direccion
            );
        } else {
            $consulta->orderBy($campo, $direccion);
        }

        return $consulta->orderBy('id_producto', 'asc');
    }

    private function normalizarFiltros(): void
    {
        if ($this->estado !== '' && ! in_array($this->estado, self::ESTADOS_VALIDOS, true)) {
            $this->estado = '';
        }

        if ($this->categoria !== '' && (! ctype_digit($this->categoria) || ! CategoriaProducto::query()->whereKey((int) $this->categoria)->exists())) {
            $this->categoria = '';
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

    public function urlImagen(?string $ruta): ?string
    {
        return $ruta ? Storage::disk('public')->url($ruta) : null;
    }

    public function render()
    {
        foreach ($this->productos as $producto) {
            if (! array_key_exists($producto->id_producto, $this->estadosSeleccionados)) {
                $this->estadosSeleccionados[$producto->id_producto] = $producto->estado;
            }
        }

        return view('livewire.admin.productos.tabla-productos');
    }
}
