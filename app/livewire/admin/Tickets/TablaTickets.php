<?php

namespace App\Livewire\Admin\Tickets;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaTickets extends Component
{
    use WithPagination;

    private const ESTADOS_VALIDOS = ['', 'EMITIDO', 'UTILIZADO', 'ANULADO'];
    private const COLUMNAS_ORDENABLES = ['id_ticket', 'codigo_validacion', 'id_compra', 'comprador', 'estado', 'emitido_en', 'utilizado_en'];
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
    public string $sort = 'emitido_en';
    #[Url(as: 'direccion')]
    public string $direction = 'desc';

    public function mount(): void { $this->normalizarFiltros(); }

    private function normalizarFiltros(): void
    {
        if (! in_array($this->estado, self::ESTADOS_VALIDOS, true)) $this->estado = '';
        if (! in_array($this->sort, self::COLUMNAS_ORDENABLES, true)) $this->sort = 'emitido_en';
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
        try { return Carbon::createFromFormat('!Y-m-d', $fecha)->format('Y-m-d') === $fecha; }
        catch (\Throwable) { return false; }
    }

    public function updatedQ(): void { $this->resetPage(); }
    public function updatedEstado(): void { $this->normalizarFiltros(); $this->resetPage(); }
    public function updatedFechaDesde(): void { $this->normalizarFiltros(); $this->resetPage(); }
    public function updatedFechaHasta(): void { $this->normalizarFiltros(); $this->resetPage(); }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::COLUMNAS_ORDENABLES, true)) return;
        if ($this->sort === $column) $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        else { $this->sort = $column; $this->direction = $column === 'emitido_en' ? 'desc' : 'asc'; }
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->q = ''; $this->estado = ''; $this->fechaDesde = ''; $this->fechaHasta = '';
        $this->sort = 'emitido_en'; $this->direction = 'desc'; $this->resetPage();
    }

    public function indicadorOrden(string $column): string
    {
        if ($this->sort !== $column) return 'unfold_more';
        return $this->direction === 'asc' ? 'arrow_upward' : 'arrow_downward';
    }

    #[Computed]
    public function tickets(): LengthAwarePaginator
    {
        $this->normalizarFiltros();
        $columnas = [
            'id_ticket' => 't.id_ticket', 'codigo_validacion' => 't.codigo_validacion', 'id_compra' => 't.id_compra',
            'comprador' => 'co.nombre', 'estado' => 't.estado', 'emitido_en' => 't.emitido_en', 'utilizado_en' => 't.utilizado_en',
        ];
        $consulta = DB::table('ticket as t')
            ->join('compra as c', 'c.id_compra', '=', 't.id_compra')
            ->join('comprador as co', 'co.id_comprador', '=', 'c.id_comprador')
            ->select([
                't.id_ticket', 't.id_compra', 't.codigo_validacion', 't.emitido_en', 't.estado', 't.utilizado_en',
                'c.estado as compra_estado', 'c.total as compra_total', 'c.moneda',
                'co.nombre as comprador_nombre', 'co.correo as comprador_correo',
            ]);
        if ($this->estado !== '') $consulta->where('t.estado', $this->estado);
        $texto = trim($this->q);
        if ($texto !== '') {
            $consulta->where(function (Builder $query) use ($texto): void {
                if (ctype_digit($texto)) $query->where('t.id_ticket', (int) $texto)->orWhere('c.id_compra', (int) $texto);
                $query->orWhere('t.codigo_validacion', 'like', "%{$texto}%")
                    ->orWhere('co.nombre', 'like', "%{$texto}%")
                    ->orWhere('co.correo', 'like', "%{$texto}%");
            });
        }
        if ($this->fechaDesde !== '') $consulta->where('t.emitido_en', '>=', $this->fechaDesde . ' 00:00:00');
        if ($this->fechaHasta !== '') $consulta->where('t.emitido_en', '<=', $this->fechaHasta . ' 23:59:59');
        $sort = in_array($this->sort, self::COLUMNAS_ORDENABLES, true) ? $this->sort : 'emitido_en';
        $direction = in_array($this->direction, self::DIRECCIONES_VALIDAS, true) ? $this->direction : 'desc';
        return $consulta->orderBy($columnas[$sort], $direction)->orderByDesc('t.id_ticket')->paginate(15);
    }

    public function render() { return view('livewire.admin.tickets.tabla-tickets'); }
}
