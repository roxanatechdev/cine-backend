<?php

namespace App\Livewire\Admin\Funciones;

use App\Models\Funcion;
use App\Models\Sala;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TablaFunciones extends Component
{
    use WithPagination;

    private const ESTADOS_VALIDOS = ['PROGRAMADA', 'EN_CURSO', 'FINALIZADA', 'CANCELADA'];
    private const TAMANOS_PAGINA_VALIDOS = [10, 20, 50, 100];

    #[Url]
    public string $q = '';

    #[Url]
    public string $estado = '';

    #[Url(as: 'sala')]
    public string $idSala = '';

    #[Url(as: 'desde')]
    public string $fechaDesde = '';

    #[Url(as: 'hasta')]
    public string $fechaHasta = '';

    #[Url(as: 'per_page')]
    public int $perPage = 20;

    #[Url(as: 'orden')]
    public string $sortField = 'inicio';

    #[Url(as: 'direccion')]
    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->normalizarFiltros();
        $this->normalizarTamanioPagina();
        $this->normalizarOrden();
    }

    private function normalizarFiltros(): void
    {
        if ($this->estado !== '' && ! in_array($this->estado, self::ESTADOS_VALIDOS, true)) {
            $this->estado = '';
        }

        if ($this->idSala !== '' && (! ctype_digit($this->idSala) || ! Sala::query()->whereKey((int) $this->idSala)->exists())) {
            $this->idSala = '';
        }

        if (! $this->fechaValida($this->fechaDesde)) {
            $this->fechaDesde = '';
        }

        if (! $this->fechaValida($this->fechaHasta)) {
            $this->fechaHasta = '';
        }

        if ($this->fechaDesde !== '' && $this->fechaHasta !== '' && $this->fechaDesde > $this->fechaHasta) {
            [$this->fechaDesde, $this->fechaHasta] = [$this->fechaHasta, $this->fechaDesde];
        }
    }

    private function fechaValida(string $fecha): bool
    {
        if ($fecha === '') {
            return true;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        return $date !== false && $date->format('Y-m-d') === $fecha;
    }

    private function normalizarTamanioPagina(): void
    {
        if (! in_array($this->perPage, self::TAMANOS_PAGINA_VALIDOS, true)) {
            $this->perPage = 20;
        }
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    private function normalizarOrden(): void
    {
        $camposPermitidos = ['inicio', 'pelicula', 'sala', 'formato', 'precio_base', 'estado'];

        if (! in_array($this->sortField, $camposPermitidos, true)) {
            $this->sortField = 'inicio';
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'desc';
        }
    }

    public function ordenarPor(string $campo): void
    {
        $camposPermitidos = ['inicio', 'pelicula', 'sala', 'formato', 'precio_base', 'estado'];

        if (! in_array($campo, $camposPermitidos, true)) {
            return;
        }

        if ($this->sortField === $campo) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $campo;
            $this->sortDirection = in_array($campo, ['inicio', 'precio_base'], true) ? 'desc' : 'asc';
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

    public function updatedEstado(): void
    {
        $this->normalizarFiltros();
        $this->resetPage();
    }

    public function updatedIdSala(): void
    {
        $this->normalizarFiltros();
        $this->resetPage();
    }

    public function updatedFechaDesde(): void
    {
        $this->normalizarFiltros();
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->normalizarFiltros();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->normalizarTamanioPagina();
        $this->resetPage();
    }

    #[Computed]
    public function funciones(): LengthAwarePaginator
    {
        return $this->consultaFunciones()->paginate($this->perPage);
    }

    #[Computed]
    public function salas(): \Illuminate\Database\Eloquent\Collection
    {
        return Sala::query()->orderBy('nombre')->get(['id_sala', 'nombre']);
    }

    private function consultaFunciones(): Builder
    {
        $consulta = Funcion::query()->with(['pelicula', 'sala']);
        $texto = trim($this->q);

        $consulta
            ->when($texto !== '', fn (Builder $query) => $query->whereHas(
                'pelicula',
                fn (Builder $pelicula) => $pelicula->where('titulo', 'like', '%' . $texto . '%')
            ))
            ->when($this->estado !== '', fn (Builder $query) => $query->where('estado', $this->estado))
            ->when($this->idSala !== '', fn (Builder $query) => $query->where('id_sala', (int) $this->idSala))
            ->when($this->fechaDesde !== '', fn (Builder $query) => $query->where('inicio', '>=', $this->fechaDesde . ' 00:00:00'))
            ->when($this->fechaHasta !== '', fn (Builder $query) => $query->where('inicio', '<=', $this->fechaHasta . ' 23:59:59'));

        $direccion = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        match ($this->sortField) {
            'pelicula' => $consulta->orderBy(\App\Models\Pelicula::query()
                ->select('titulo')
                ->whereColumn('pelicula.id_pelicula', 'funcion.id_pelicula'), $direccion),
            'sala' => $consulta->orderBy(Sala::query()
                ->select('nombre')
                ->whereColumn('sala.id_sala', 'funcion.id_sala'), $direccion),
            'formato' => $consulta->orderBy('formato', $direccion),
            'precio_base' => $consulta->orderBy('precio_base', $direccion),
            'estado' => $consulta->orderBy('estado', $direccion),
            default => $consulta->orderBy('inicio', $direccion),
        };

        // Desempate estable para que la paginación no cambie de forma imprevisible.
        $consulta->orderBy('id_funcion', 'desc');

        return $consulta;
    }

    public function limpiarFiltros(): void
    {
        $this->q = '';
        $this->estado = '';
        $this->idSala = '';
        $this->fechaDesde = '';
        $this->fechaHasta = '';
        $this->sortField = 'inicio';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.funciones.tabla-funciones');
    }
}
