<?php

namespace App\Livewire\Admin\Compradores;

use App\Models\Comprador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaCompradores extends Component
{
    use WithPagination;

    private const COLUMNAS_ORDENABLES = [
        'id_comprador', 'nombre', 'correo', 'telefono', 'tipo_comprador', 'creado_en',
    ];

    private const DIRECCIONES_VALIDAS = ['asc', 'desc'];
    private const TAMANOS_PAGINA_VALIDOS = [10, 25, 50, 100];
    private const TIPOS_VALIDOS = ['', 'INVITADO', 'REGISTRADO'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $tipo = '';

    #[Url]
    public string $sort = 'nombre';

    #[Url]
    public string $direction = 'asc';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    public function mount(): void
    {
        $this->normalizarEstado();
    }

    private function normalizarEstado(): void
    {
        if (! in_array($this->sort, self::COLUMNAS_ORDENABLES, true)) {
            $this->sort = 'nombre';
        }
        if (! in_array($this->direction, self::DIRECCIONES_VALIDAS, true)) {
            $this->direction = 'asc';
        }
        if (! in_array($this->perPage, self::TAMANOS_PAGINA_VALIDOS, true)) {
            $this->perPage = 10;
        }
        if (! in_array($this->tipo, self::TIPOS_VALIDOS, true)) {
            $this->tipo = '';
        }
        $this->q = mb_substr($this->q, 0, 150);
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

    public function updatedTipo(): void
    {
        $this->normalizarEstado();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->normalizarEstado();
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->q = '';
        $this->tipo = '';
        $this->perPage = 10;
        $this->sort = 'nombre';
        $this->direction = 'asc';
        $this->resetPage();
    }

    #[Computed]
    public function compradores(): LengthAwarePaginator
    {
        $consulta = Comprador::query();
        $texto = trim($this->q);

        if ($texto !== '') {
            $consulta->where(function (Builder $query) use ($texto): void {
                $query->where('nombre', 'like', '%' . $texto . '%')
                    ->orWhere('correo', 'like', '%' . $texto . '%')
                    ->orWhere('telefono', 'like', '%' . $texto . '%');
                if (ctype_digit($texto)) {
                    $query->orWhere('id_comprador', (int) $texto);
                }
            });
        }

        if (in_array($this->tipo, ['INVITADO', 'REGISTRADO'], true)) {
            $consulta->where('tipo_comprador', $this->tipo);
        }

        $columna = in_array($this->sort, self::COLUMNAS_ORDENABLES, true) ? $this->sort : 'nombre';
        $direccion = in_array($this->direction, self::DIRECCIONES_VALIDAS, true) ? $this->direction : 'asc';

        return $consulta->orderBy($columna, $direccion)
            ->orderBy('id_comprador')
            ->paginate($this->perPage, ['*'], 'page');
    }

    public function render()
    {
        return view('livewire.admin.compradores.tabla-compradores');
    }
}
