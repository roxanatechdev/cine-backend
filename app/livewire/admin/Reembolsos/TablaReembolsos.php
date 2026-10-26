<?php

namespace App\Livewire\Admin\Reembolsos;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaReembolsos extends Component
{
    use WithPagination;

    private const ESTADOS_VALIDOS = ['', 'SOLICITADO', 'PROCESANDO', 'COMPLETADO', 'RECHAZADO'];
    private const COLUMNAS_ORDENABLES = ['id_reembolso', 'id_pago', 'id_compra', 'comprador', 'importe', 'estado', 'solicitado_en', 'completado_en', 'referencia_externa'];
    private const DIRECCIONES_VALIDAS = ['asc', 'desc'];

    #[Url(as: 'buscar')]
    public string $q = '';

    #[Url]
    public string $estado = '';

    #[Url(as: 'desde')]
    public string $fechaDesde = '';

    #[Url(as: 'hasta')]
    public string $fechaHasta = '';

    #[Url(as: 'orden')]
    public string $sort = 'solicitado_en';

    #[Url(as: 'direccion')]
    public string $direction = 'desc';

    public function mount(): void
    {
        $this->normalizarFiltros();
    }

    private function normalizarFiltros(): void
    {
        if (! in_array($this->estado, self::ESTADOS_VALIDOS, true)) $this->estado = '';
        if (! in_array($this->sort, self::COLUMNAS_ORDENABLES, true)) $this->sort = 'solicitado_en';
        if (! in_array($this->direction, self::DIRECCIONES_VALIDAS, true)) $this->direction = 'desc';
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

    public function updatedQ(): void { $this->resetPage(); }
    public function updatedEstado(): void { $this->normalizarFiltros(); $this->resetPage(); }
    public function updatedFechaDesde(): void { $this->normalizarFiltros(); $this->resetPage(); }
    public function updatedFechaHasta(): void { $this->normalizarFiltros(); $this->resetPage(); }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::COLUMNAS_ORDENABLES, true)) return;
        if ($this->sort === $column) $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        else {
            $this->sort = $column;
            $this->direction = $column === 'solicitado_en' ? 'desc' : 'asc';
        }
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->q = '';
        $this->estado = '';
        $this->fechaDesde = '';
        $this->fechaHasta = '';
        $this->sort = 'solicitado_en';
        $this->direction = 'desc';
        $this->resetPage();
    }

    public function indicadorOrden(string $column): string
    {
        if ($this->sort !== $column) return 'unfold_more';
        return $this->direction === 'asc' ? 'arrow_upward' : 'arrow_downward';
    }

    #[Computed]
    public function reembolsos(): LengthAwarePaginator
    {
        $this->normalizarFiltros();
        $columnas = [
            'id_reembolso' => 'r.id_reembolso', 'id_pago' => 'r.id_pago', 'id_compra' => 'p.id_compra',
            'comprador' => 'co.nombre', 'importe' => 'r.importe', 'estado' => 'r.estado',
            'solicitado_en' => 'r.solicitado_en', 'completado_en' => 'r.completado_en',
            'referencia_externa' => 'r.referencia_externa',
        ];
        $consulta = DB::table('reembolso as r')
            ->join('pago as p', 'p.id_pago', '=', 'r.id_pago')
            ->join('compra as c', 'c.id_compra', '=', 'p.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->select([
                'r.id_reembolso', 'r.id_pago', 'r.importe', 'r.motivo', 'r.estado',
                'r.solicitado_en', 'r.completado_en', 'r.referencia_externa',
                'p.id_compra', 'p.estado as pago_estado', 'p.importe as pago_importe',
                'c.moneda', 'co.nombre as comprador_nombre', 'co.correo as comprador_correo',
            ]);

        if ($this->estado !== '') $consulta->where('r.estado', $this->estado);
        $texto = trim($this->q);
        if ($texto !== '') {
            $consulta->where(function (Builder $query) use ($texto): void {
                if (ctype_digit($texto)) {
                    $id = (int) $texto;
                    $query->where('r.id_reembolso', $id)->orWhere('p.id_pago', $id)->orWhere('c.id_compra', $id);
                }
                $query->orWhere('co.nombre', 'like', "%{$texto}%")
                    ->orWhere('co.correo', 'like', "%{$texto}%")
                    ->orWhere('r.referencia_externa', 'like', "%{$texto}%");
            });
        }
        if ($this->fechaDesde !== '') $consulta->where('r.solicitado_en', '>=', $this->fechaDesde . ' 00:00:00');
        if ($this->fechaHasta !== '') $consulta->where('r.solicitado_en', '<=', $this->fechaHasta . ' 23:59:59');

        $direction = in_array($this->direction, self::DIRECCIONES_VALIDAS, true) ? $this->direction : 'desc';
        $sort = in_array($this->sort, self::COLUMNAS_ORDENABLES, true) ? $this->sort : 'solicitado_en';
        return $consulta->orderBy($columnas[$sort], $direction)->orderByDesc('r.id_reembolso')->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.reembolsos.tabla-reembolsos');
    }
}
