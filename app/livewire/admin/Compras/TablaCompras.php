<?php

namespace App\Livewire\Admin\Compras;

use App\Models\Compra;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaCompras extends Component
{
    use WithPagination;

    private const ESTADOS_VALIDOS = ['', 'INICIADA', 'PENDIENTE_PAGO', 'CONFIRMADA', 'CANCELADA', 'EXPIRADA'];
    private const COLUMNAS_ORDENABLES = ['id_compra', 'comprador', 'creada_en', 'vence_reserva', 'total', 'estado'];
    private const DIRECCIONES_VALIDAS = ['asc', 'desc'];
    private const TAMANOS_PAGINA_VALIDOS = [10, 25, 50, 100];

    #[Url]
    public string $q = '';

    #[Url]
    public string $estado = '';

    #[Url(as: 'desde')]
    public string $fechaDesde = '';

    #[Url(as: 'hasta')]
    public string $fechaHasta = '';

    #[Url]
    public string $sort = 'creada_en';

    #[Url]
    public string $direction = 'desc';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    public function mount(): void
    {
        $this->normalizarEstado();
    }

    private function normalizarEstado(): void
    {
        if (! in_array($this->estado, self::ESTADOS_VALIDOS, true)) {
            $this->estado = '';
        }
        if (! in_array($this->sort, self::COLUMNAS_ORDENABLES, true)) {
            $this->sort = 'creada_en';
        }
        if (! in_array($this->direction, self::DIRECCIONES_VALIDAS, true)) {
            $this->direction = 'desc';
        }
        if (! in_array($this->perPage, self::TAMANOS_PAGINA_VALIDOS, true)) {
            $this->perPage = 10;
        }
        $this->q = mb_substr($this->q, 0, 150);
        $this->fechaDesde = $this->fechaValida($this->fechaDesde) ? $this->fechaDesde : '';
        $this->fechaHasta = $this->fechaValida($this->fechaHasta) ? $this->fechaHasta : '';
        if ($this->fechaDesde !== '' && $this->fechaHasta !== '' && $this->fechaDesde > $this->fechaHasta) {
            [$this->fechaDesde, $this->fechaHasta] = [$this->fechaHasta, $this->fechaDesde];
        }
    }

    private function fechaValida(string $fecha): bool
    {
        if ($fecha === '') {
            return false;
        }
        try {
            return Carbon::createFromFormat('!Y-m-d', $fecha)->format('Y-m-d') === $fecha;
        } catch (\Throwable) {
            return false;
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
            $this->direction = $column === 'creada_en' ? 'desc' : 'asc';
        }
        $this->resetPage();
    }

    public function updatedQ(): void { $this->resetPage(); }
    public function updatedEstado(): void { $this->normalizarEstado(); $this->resetPage(); }
    public function updatedFechaDesde(): void { $this->normalizarEstado(); $this->resetPage(); }
    public function updatedFechaHasta(): void { $this->normalizarEstado(); $this->resetPage(); }
    public function updatedPerPage(): void { $this->normalizarEstado(); $this->resetPage(); }

    public function limpiarFiltros(): void
    {
        $this->q = '';
        $this->estado = '';
        $this->fechaDesde = '';
        $this->fechaHasta = '';
        $this->sort = 'creada_en';
        $this->direction = 'desc';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function indicadorOrden(string $column): string
    {
        if ($this->sort !== $column) return 'unfold_more';
        return $this->direction === 'asc' ? 'arrow_upward' : 'arrow_downward';
    }

    #[Computed]
    public function compras(): LengthAwarePaginator
    {
        $this->normalizarEstado();
        $consulta = Compra::query()->with('comprador:id_comprador,nombre,correo');
        $texto = trim($this->q);

        if ($texto !== '') {
            $consulta->where(function (Builder $query) use ($texto): void {
                if (ctype_digit($texto)) {
                    $query->where('id_compra', (int) $texto)
                        ->orWhereHas('comprador', fn (Builder $comprador) => $comprador
                            ->where('nombre', 'like', "%{$texto}%")
                            ->orWhere('correo', 'like', "%{$texto}%"));
                } else {
                    $query->whereHas('comprador', fn (Builder $comprador) => $comprador
                        ->where('nombre', 'like', "%{$texto}%")
                        ->orWhere('correo', 'like', "%{$texto}%"));
                }
            });
        }

        if ($this->estado !== '') $consulta->where('estado', $this->estado);
        if ($this->fechaDesde !== '') $consulta->where('creada_en', '>=', $this->fechaDesde . ' 00:00:00');
        if ($this->fechaHasta !== '') $consulta->where('creada_en', '<=', $this->fechaHasta . ' 23:59:59');

        $direccion = in_array($this->direction, self::DIRECCIONES_VALIDAS, true) ? $this->direction : 'desc';
        if ($this->sort === 'comprador') {
            $consulta->orderBy(
                \App\Models\Comprador::query()->select('nombre')->whereColumn('comprador.id_comprador', 'compra.id_comprador'),
                $direccion
            );
        } else {
            $columna = in_array($this->sort, self::COLUMNAS_ORDENABLES, true) && $this->sort !== 'comprador' ? $this->sort : 'creada_en';
            $consulta->orderBy('compra.' . $columna, $direccion);
        }

        return $consulta->orderBy('compra.id_compra', 'desc')->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.compras.tabla-compras');
    }
}
