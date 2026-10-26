<?php

namespace App\Livewire\Admin\Asientos;

use App\Models\Asiento;
use App\Models\Sala;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaAsientos extends Component
{
    use WithPagination;

    private const TIPOS_VALIDOS = ['', 'ESTANDAR', 'PREFERENCIAL', 'ACCESIBLE'];
    private const COLUMNAS_ORDENABLES = ['sala', 'fila', 'numero', 'tipo', 'activo'];
    private const DIRECCIONES_VALIDAS = ['asc', 'desc'];

    #[Url(as: 'buscar')]
    public string $q = '';
    #[Url(as: 'id_sala')]
    public string $idSala = '';
    #[Url]
    public string $tipo = '';
    #[Url]
    public string $activo = '';
    #[Url(as: 'orden')]
    public string $sort = 'sala';
    #[Url(as: 'direccion')]
    public string $direction = 'asc';

    public function mount(): void { $this->normalizarFiltros(); }

    private function normalizarFiltros(): void
    {
        $this->q = mb_substr($this->q, 0, 150);
        if ($this->idSala !== '' && (! ctype_digit($this->idSala) || ! Sala::query()->whereKey((int) $this->idSala)->exists())) $this->idSala = '';
        if (! in_array($this->tipo, self::TIPOS_VALIDOS, true)) $this->tipo = '';
        if (! in_array($this->activo, ['', '0', '1'], true)) $this->activo = '';
        if (! in_array($this->sort, self::COLUMNAS_ORDENABLES, true)) $this->sort = 'sala';
        if (! in_array($this->direction, self::DIRECCIONES_VALIDAS, true)) $this->direction = 'asc';
    }

    public function updatedQ(): void { $this->resetPage(); }
    public function updatedIdSala(): void { $this->normalizarFiltros(); $this->resetPage(); }
    public function updatedTipo(): void { $this->normalizarFiltros(); $this->resetPage(); }
    public function updatedActivo(): void { $this->normalizarFiltros(); $this->resetPage(); }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::COLUMNAS_ORDENABLES, true)) return;
        if ($this->sort === $column) $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        else { $this->sort = $column; $this->direction = $column === 'numero' ? 'asc' : 'asc'; }
        $this->resetPage();
    }

    public function indicadorOrden(string $column): string
    {
        if ($this->sort !== $column) return 'unfold_more';
        return $this->direction === 'asc' ? 'arrow_upward' : 'arrow_downward';
    }

    public function limpiarFiltros(): void
    {
        $this->q = ''; $this->idSala = ''; $this->tipo = ''; $this->activo = '';
        $this->sort = 'sala'; $this->direction = 'asc'; $this->resetPage();
    }

    #[Computed]
    public function opcionesSalas(): array
    {
        return ['' => 'Todas las salas'] + Sala::query()->orderBy('nombre')->pluck('nombre', 'id_sala')->map(fn ($nombre) => (string) $nombre)->toArray();
    }

    #[Computed]
    public function asientos(): LengthAwarePaginator
    {
        $this->normalizarFiltros();
        $consulta = Asiento::query()->with('sala:id_sala,nombre');
        $texto = trim($this->q);
        if ($texto !== '') {
            $consulta->where(function (Builder $query) use ($texto): void {
                if (ctype_digit($texto)) $query->where('asiento.numero', (int) $texto)->orWhere('asiento.id_asiento', (int) $texto);
                $query->orWhere('asiento.fila', 'like', "%{$texto}%")
                    ->orWhereHas('sala', fn (Builder $sala) => $sala->where('nombre', 'like', "%{$texto}%"));
            });
        }
        if ($this->idSala !== '') $consulta->where('asiento.id_sala', (int) $this->idSala);
        if ($this->tipo !== '') $consulta->where('asiento.tipo', $this->tipo);
        if ($this->activo !== '') $consulta->where('asiento.activo', (int) $this->activo);

        $direction = in_array($this->direction, self::DIRECCIONES_VALIDAS, true) ? $this->direction : 'asc';
        if ($this->sort === 'sala') {
            $consulta->orderBy(Sala::query()->select('nombre')->whereColumn('sala.id_sala', 'asiento.id_sala'), $direction);
        } else {
            $column = in_array($this->sort, self::COLUMNAS_ORDENABLES, true) ? $this->sort : 'fila';
            $consulta->orderBy('asiento.' . $column, $direction);
        }
        return $consulta->orderBy('asiento.id_sala')->orderBy('asiento.fila')->orderBy('asiento.numero')->orderBy('asiento.id_asiento')->paginate(20);
    }

    public function render() { return view('livewire.admin.asientos.tabla-asientos'); }
}
