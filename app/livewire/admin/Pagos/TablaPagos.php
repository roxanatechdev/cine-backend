<?php

namespace App\Livewire\Admin\Pagos;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaPagos extends Component
{
    use WithPagination;

    private const ESTADOS_VALIDOS = ['', 'PENDIENTE', 'APROBADO', 'RECHAZADO'];
    private const METODOS_VALIDOS = ['', 'QR', 'TARJETA'];
    private const COLUMNAS_ORDENABLES = ['id_pago', 'id_compra', 'comprador', 'metodo', 'importe', 'estado', 'referencia_externa', 'creado_en', 'aprobado_en'];
    private const DIRECCIONES_VALIDAS = ['asc', 'desc'];
    private const TAMANOS_PAGINA_VALIDOS = [10, 25, 50, 100];

    #[Url]
    public string $q = '';

    #[Url]
    public string $estado = '';

    #[Url]
    public string $metodo = '';

    #[Url(as: 'desde')]
    public string $fechaDesde = '';

    #[Url(as: 'hasta')]
    public string $fechaHasta = '';

    #[Url]
    public string $sort = 'creado_en';

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
        if (! in_array($this->estado, self::ESTADOS_VALIDOS, true)) $this->estado = '';
        if (! in_array($this->metodo, self::METODOS_VALIDOS, true)) $this->metodo = '';
        if (! in_array($this->sort, self::COLUMNAS_ORDENABLES, true)) $this->sort = 'creado_en';
        if (! in_array($this->direction, self::DIRECCIONES_VALIDAS, true)) $this->direction = 'desc';
        if (! in_array($this->perPage, self::TAMANOS_PAGINA_VALIDOS, true)) $this->perPage = 10;

        $this->q = mb_substr($this->q, 0, 150);
        $this->fechaDesde = $this->fechaValida($this->fechaDesde) ? $this->fechaDesde : '';
        $this->fechaHasta = $this->fechaValida($this->fechaHasta) ? $this->fechaHasta : '';
        if ($this->fechaDesde !== '' && $this->fechaHasta !== '' && $this->fechaDesde > $this->fechaHasta) {
            [$this->fechaDesde, $this->fechaHasta] = [$this->fechaHasta, $this->fechaDesde];
        }
    }

    private function fechaValida(string $fecha): bool
    {
        if ($fecha === '') return false;
        try {
            return Carbon::createFromFormat('!Y-m-d', $fecha)->format('Y-m-d') === $fecha;
        } catch (\Throwable) {
            return false;
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::COLUMNAS_ORDENABLES, true)) return;
        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = $column === 'creado_en' ? 'desc' : 'asc';
        }
        $this->resetPage();
    }

    public function updatedQ(): void { $this->resetPage(); }
    public function updatedEstado(): void { $this->normalizarEstado(); $this->resetPage(); }
    public function updatedMetodo(): void { $this->normalizarEstado(); $this->resetPage(); }
    public function updatedFechaDesde(): void { $this->normalizarEstado(); $this->resetPage(); }
    public function updatedFechaHasta(): void { $this->normalizarEstado(); $this->resetPage(); }
    public function updatedPerPage(): void { $this->normalizarEstado(); $this->resetPage(); }

    public function limpiarFiltros(): void
    {
        $this->q = '';
        $this->estado = '';
        $this->metodo = '';
        $this->fechaDesde = '';
        $this->fechaHasta = '';
        $this->sort = 'creado_en';
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
    public function pagos(): LengthAwarePaginator
    {
        $this->normalizarEstado();

        $consulta = DB::table('pago as pa')
            ->join('compra as c', 'c.id_compra', '=', 'pa.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->select([
                'pa.id_pago', 'pa.id_compra', 'pa.metodo', 'pa.importe', 'pa.estado',
                'pa.referencia_externa', 'pa.creado_en', 'pa.aprobado_en',
                'c.moneda as compra_moneda', 'c.total as compra_total', 'c.estado as compra_estado',
                'co.nombre as comprador_nombre', 'co.correo as comprador_correo',
            ]);

        $texto = trim($this->q);
        if ($texto !== '') {
            $consulta->where(function (Builder $query) use ($texto): void {
                if (ctype_digit($texto)) {
                    $id = (int) $texto;
                    $query->where('pa.id_pago', $id)->orWhere('c.id_compra', $id);
                }
                $query->orWhere('co.nombre', 'like', "%{$texto}%")
                    ->orWhere('co.correo', 'like', "%{$texto}%")
                    ->orWhere('pa.referencia_externa', 'like', "%{$texto}%");
            });
        }

        if ($this->estado !== '') $consulta->where('pa.estado', $this->estado);
        if ($this->metodo !== '') $consulta->where('pa.metodo', $this->metodo);
        if ($this->fechaDesde !== '') $consulta->where('pa.creado_en', '>=', $this->fechaDesde . ' 00:00:00');
        if ($this->fechaHasta !== '') $consulta->where('pa.creado_en', '<=', $this->fechaHasta . ' 23:59:59');

        $direccion = in_array($this->direction, self::DIRECCIONES_VALIDAS, true) ? $this->direction : 'desc';
        if ($this->sort === 'comprador') {
            $consulta->orderBy(
                DB::table('comprador')->select('nombre')->whereColumn('comprador.id_comprador', 'c.id_comprador'),
                $direccion
            );
        } else {
            $columna = in_array($this->sort, self::COLUMNAS_ORDENABLES, true) ? $this->sort : 'creado_en';
            $consulta->orderBy('pa.' . $columna, $direccion);
        }

        return $consulta->orderByDesc('pa.id_pago')->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.pagos.tabla-pagos');
    }
}
