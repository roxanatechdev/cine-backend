<?php

namespace App\Livewire\Admin\Salas;

use App\Models\Asiento;
use App\Models\Sala;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EditorDistribucionSala extends Component
{
    #[Locked]
    public int $salaId;

    #[Locked]
    public ?int $asientoSeleccionadoId = null;
    public string $fila = 'A';
    public int $numero = 1;
    public string $tipo = 'ESTANDAR';
    public string $activo = '1';

    public string $filaMasiva = 'A';
    public int $numeroInicioMasivo = 1;
    public int $numeroFinMasivo = 10;
    public string $tipoMasivo = 'ESTANDAR';
    public string $activoMasivo = '1';
    public array $vistaPreviaMasiva = [];

    public bool $modoSeleccionMultiple = false;
    public array $asientosSeleccionados = [];
    public string $tipoEdicionMasiva = '';
    public string $activoEdicionMasiva = '';
    public array $vistaPreviaEdicionMasiva = [];

    public function mount(int $salaId): void
    {
        $this->salaId = $salaId;
        $this->sala;
    }

    #[Computed]
    public function sala(): Sala
    {
        return Sala::query()->findOrFail($this->salaId);
    }

    #[Computed]
    public function asientos(): array
    {
        $asientos = Asiento::query()
            ->where('id_sala', $this->salaId)
            ->orderBy('fila')
            ->orderBy('numero')
            ->get();
        $pasillos = DB::table('sala_pasillo')
            ->where('id_sala', $this->salaId)
            ->orderBy('fila')
            ->orderBy('despues_de_numero')
            ->get();
        $pasillosLongitudinales = $pasillos->where('orientacion', 'LONGITUDINAL');
        $pasillosTransversales = $pasillos->where('orientacion', 'TRANSVERSAL');

        $referenciados = DB::table('funcion_asiento')
            ->whereIn('id_asiento', $asientos->pluck('id_asiento'))
            ->pluck('id_asiento')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->all();

        $filas = $asientos->pluck('fila')
            ->merge($pasillosLongitudinales->pluck('fila'))
            ->merge($pasillosTransversales->pluck('fila'))
            ->merge($pasillosTransversales->pluck('fila_siguiente'))
            ->filter()
            ->unique()->sort()->values();
        if ($filas->isEmpty()) {
            $filas = collect(['A', 'B', 'C', 'D', 'E', 'F']);
        }

        $maximoNumero = (int) ($asientos->max('numero') ?? 0);
        $limiteColumnas = min(max($maximoNumero, 12), 30);
        $columnas = range(1, $limiteColumnas);

        $pasillosPorFila = [];
        foreach ($pasillosLongitudinales as $pasillo) {
            $pasillosPorFila[(string) $pasillo->fila][(int) $pasillo->despues_de_numero] = true;
        }
        $pasillosTransversalesPorPar = [];
        foreach ($pasillosTransversales as $pasillo) {
            $pasillosTransversalesPorPar[(string) $pasillo->fila][(string) $pasillo->fila_siguiente] = true;
        }

        $porUbicacion = [];
        foreach ($asientos as $asiento) {
            $porUbicacion[$asiento->fila][(int) $asiento->numero] = [
                'id' => (int) $asiento->id_asiento,
                'fila' => (string) $asiento->fila,
                'numero' => (int) $asiento->numero,
                'tipo' => (string) $asiento->tipo,
                'activo' => (int) $asiento->activo,
                'protegido' => in_array((int) $asiento->id_asiento, $referenciados, true),
            ];
        }

        $advertencias = [];
        $huecos = 0;
        $filasConHuecos = 0;
        $asientosPorFila = $asientos->groupBy(fn ($asiento) => (string) $asiento->fila);

        foreach (array_keys($pasillosPorFila) as $filaConPasillo) {
            if (!$asientosPorFila->has($filaConPasillo)) {
                $advertencias[] = [
                    'tipo' => 'fila_sin_asientos',
                    'fila' => $filaConPasillo,
                    'cantidad' => 0,
                    'numeros' => [],
                    'mensaje' => 'La fila ' . $filaConPasillo . ' tiene pasillos registrados, pero no tiene asientos.',
                ];
            }
        }

        foreach ($asientosPorFila as $fila => $asientosFila) {
            $numeros = $asientosFila->pluck('numero')->map(fn ($numero) => (int) $numero)->unique()->sort()->values();
            if ($numeros->count() < 2) {
                continue;
            }

            $faltantes = [];
            for ($numero = $numeros->first() + 1; $numero < $numeros->last(); $numero++) {
                if (!$numeros->contains($numero)) {
                    $faltantes[] = $numero;
                }
            }

            if ($faltantes !== []) {
                $huecos += count($faltantes);
                $filasConHuecos++;
                $advertencias[] = [
                    'tipo' => 'huecos',
                    'fila' => $fila,
                    'cantidad' => count($faltantes),
                    'numeros' => $faltantes,
                    'mensaje' => 'La fila ' . $fila . ' tiene posiciones vacías dentro de su rango: ' . implode(', ', $faltantes) . '.',
                ];
            }
        }

        return [
            'filas' => $filas->all(),
            'columnas' => $columnas,
            'asientos' => $porUbicacion,
            'pasillos' => $pasillosPorFila,
            'pasillos_transversales' => $pasillosTransversalesPorPar,
            'total' => $asientos->count(),
            'activos' => $asientos->where('activo', 1)->count(),
            'inactivos' => $asientos->where('activo', 0)->count(),
            'protegidos' => count($referenciados),
            'total_pasillos' => $pasillos->count(),
            'huecos' => $huecos,
            'filas_con_huecos' => $filasConHuecos,
            'advertencias' => $advertencias,
            'fuera_de_rango' => $asientos->filter(fn ($a) => (int) $a->numero > 30)->count(),
        ];
    }

    public function alternarPasillo(string $fila, int $despuesDeNumero): void
    {
        $fila = strtoupper(trim($fila));
        validator(
            ['fila' => $fila, 'despuesDeNumero' => $despuesDeNumero],
            [
                'fila' => ['required', 'string', 'max:5', 'regex:/^[A-Z0-9]+$/'],
                'despuesDeNumero' => ['required', 'integer', 'min:1', 'max:65534'],
            ],
            [
                'fila.regex' => 'La fila solo puede contener letras y números.',
                'despuesDeNumero.max' => 'El pasillo debe ubicarse antes del número 65535.',
            ]
        )->validate();

        $this->sala->id_sala; // Confirma que la sala siga existiendo y pertenece al componente.

        $existe = DB::transaction(function () use ($fila, $despuesDeNumero): bool {
            $pasillo = DB::table('sala_pasillo')
                ->where('id_sala', $this->salaId)
                ->where('fila', $fila)
                ->where('despues_de_numero', $despuesDeNumero)
                ->lockForUpdate()
                ->first();

            if ($pasillo) {
                DB::table('sala_pasillo')->where('id_pasillo', $pasillo->id_pasillo)->delete();
                return false;
            }

            DB::table('sala_pasillo')->insertOrIgnore([
                'id_sala' => $this->salaId,
                'fila' => $fila,
                'despues_de_numero' => $despuesDeNumero,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });

        unset($this->asientos);
        $this->dispatch('distribucion-guardada', mensaje: $existe
            ? "Pasillo agregado después del asiento {$fila}-{$despuesDeNumero}."
            : "Pasillo eliminado después del asiento {$fila}-{$despuesDeNumero}.");
    }

    public function alternarPasilloTransversal(string $fila, string $filaSiguiente): void
    {
        $fila = strtoupper(trim($fila));
        $filaSiguiente = strtoupper(trim($filaSiguiente));
        validator(
            ['fila' => $fila, 'fila_siguiente' => $filaSiguiente],
            [
                'fila' => ['required', 'string', 'max:5', 'regex:/^[A-Z0-9]+$/'],
                'fila_siguiente' => ['required', 'string', 'max:5', 'regex:/^[A-Z0-9]+$/', 'different:fila'],
            ]
        )->validate();

        $filas = Asiento::query()->where('id_sala', $this->salaId)
            ->distinct()->orderBy('fila')->pluck('fila')->map(fn ($valor) => (string) $valor)->values()->all();
        $indice = array_search($fila, $filas, true);
        if ($indice === false || ($filas[$indice + 1] ?? null) !== $filaSiguiente) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fila_siguiente' => 'El pasillo transversal debe separar dos filas existentes y consecutivas.',
            ]);
        }

        $existente = DB::table('sala_pasillo')->where('id_sala', $this->salaId)
            ->where('orientacion', 'TRANSVERSAL')->where('fila', $fila)
            ->where('fila_siguiente', $filaSiguiente)->first();

        if ($existente) {
            DB::table('sala_pasillo')->where('id_pasillo', $existente->id_pasillo)->delete();
            $mensaje = "Pasillo transversal eliminado entre las filas {$fila} y {$filaSiguiente}.";
        } else {
            DB::table('sala_pasillo')->insert([
                'id_sala' => $this->salaId,
                'fila' => $fila,
                'fila_siguiente' => $filaSiguiente,
                'orientacion' => 'TRANSVERSAL',
                'despues_de_numero' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $mensaje = "Pasillo transversal agregado entre las filas {$fila} y {$filaSiguiente}.";
        }

        unset($this->asientos);
        $this->dispatch('distribucion-guardada', mensaje: $mensaje);
    }

    public function seleccionarCelda(string $fila, int $numero): void
    {
        $this->validarUbicacion($fila, $numero);

        if ($this->modoSeleccionMultiple) {
            $id = Asiento::query()->where('id_sala', $this->salaId)->where('fila', $fila)->where('numero', $numero)->value('id_asiento');
            if ($id !== null) {
                $id = (int) $id;
                $seleccionados = array_map('intval', $this->asientosSeleccionados);
                $this->asientosSeleccionados = in_array($id, $seleccionados, true)
                    ? array_values(array_diff($seleccionados, [$id]))
                    : array_values(array_unique([...$seleccionados, $id]));
                $this->vistaPreviaEdicionMasiva = [];
            }
            return;
        }
        $this->resetValidation();

        $asiento = Asiento::query()
            ->where('id_sala', $this->salaId)
            ->where('fila', $fila)
            ->where('numero', $numero)
            ->first();

        $this->fila = $fila;
        $this->numero = $numero;
        $this->asientoSeleccionadoId = $asiento?->id_asiento;
        $this->tipo = $asiento?->tipo ?? 'ESTANDAR';
        $this->activo = (string) ($asiento?->activo ?? 1);
    }

    public function guardarAsiento(): void
    {
        if ($this->asientoSeleccionadoId === null) {
            $this->fila = strtoupper(trim($this->fila));
        }

        $reglas = [
            'fila' => ['required', 'string', 'max:5'],
            'numero' => ['required', 'integer', 'min:1', 'max:65535'],
            'tipo' => ['required', Rule::in(['ESTANDAR', 'PREFERENCIAL', 'ACCESIBLE'])],
            'activo' => ['required', Rule::in(['0', '1'])],
        ];

        $reglas['numero'][] = Rule::unique('asiento', 'numero')
            ->where(fn ($query) => $query
                ->where('id_sala', $this->salaId)
                ->where('fila', strtoupper(trim($this->fila))))
            ->ignore($this->asientoSeleccionadoId, 'id_asiento');

        $datos = $this->validate($reglas, [
            'fila.required' => 'La fila es obligatoria.',
            'fila.max' => 'La fila no puede superar los 5 caracteres.',
            'numero.required' => 'El número de asiento es obligatorio.',
            'numero.min' => 'El número debe ser mayor que cero.',
            'numero.max' => 'El número no puede superar 65535.',
            'numero.unique' => 'Ya existe un asiento en esa fila y número.',
            'tipo.in' => 'Selecciona un tipo de asiento válido.',
            'activo.in' => 'Selecciona una disponibilidad válida.',
        ]);

        if ($this->asientoSeleccionadoId === null) {
            Asiento::create([
                'id_sala' => $this->salaId,
                'fila' => strtoupper(trim($datos['fila'])),
                'numero' => $datos['numero'],
                'tipo' => $datos['tipo'],
                'activo' => (int) $datos['activo'],
            ]);

            $this->dispatch('distribucion-guardada', mensaje: 'Asiento agregado a la distribución.');
        } else {
            $resultado = DB::transaction(function () use ($datos): array {
                $asiento = Asiento::query()
                    ->where('id_sala', $this->salaId)
                    ->whereKey($this->asientoSeleccionadoId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $protegido = DB::table('funcion_asiento')
                    ->where('id_asiento', $asiento->id_asiento)
                    ->lockForUpdate()
                    ->exists();

                if ($protegido && (
                    $datos['tipo'] !== $asiento->tipo
                    || strtoupper(trim($datos['fila'])) !== $asiento->fila
                    || (int) $datos['numero'] !== (int) $asiento->numero
                )) {
                    return ['tipo' => 'Este asiento tiene historial de funciones. No puedes cambiar su tipo ni su ubicación; solo su disponibilidad.'];
                }

                $asiento->update([
                    'fila' => strtoupper(trim($datos['fila'])),
                    'numero' => (int) $datos['numero'],
                    'tipo' => $datos['tipo'],
                    'activo' => (int) $datos['activo'],
                ]);

                return [];
            });

            if ($resultado !== []) {
                foreach ($resultado as $campo => $mensaje) {
                    $this->addError($campo, $mensaje);
                }
                return;
            }

            $this->dispatch('distribucion-guardada', mensaje: 'Cambios del asiento guardados.');
        }

        unset($this->asientos);
        $this->seleccionarCelda(strtoupper(trim($datos['fila'])), (int) $datos['numero']);
    }

    public function eliminarAsiento(): void
    {
        if ($this->asientoSeleccionadoId === null) {
            $this->addError('asientoSeleccionadoId', 'Selecciona un asiento antes de eliminarlo.');
            return;
        }

        $resultado = DB::transaction(function (): array {
            $asiento = Asiento::query()
                ->where('id_sala', $this->salaId)
                ->whereKey($this->asientoSeleccionadoId)
                ->lockForUpdate()
                ->firstOrFail();

            $protegido = DB::table('funcion_asiento')
                ->where('id_asiento', $asiento->id_asiento)
                ->lockForUpdate()
                ->exists();

            if ($protegido) {
                return ['asientoSeleccionadoId' => 'No puedes eliminar este asiento porque está vinculado al historial de funciones.'];
            }

            $asiento->delete();
            return [];
        });

        if ($resultado !== []) {
            foreach ($resultado as $campo => $mensaje) {
                $this->addError($campo, $mensaje);
            }
            return;
        }

        $this->asientoSeleccionadoId = null;
        $this->resetValidation();
        $this->fila = 'A';
        $this->numero = 1;
        $this->tipo = 'ESTANDAR';
        $this->activo = '1';
        unset($this->asientos);
        $this->dispatch('distribucion-guardada', mensaje: 'Asiento eliminado de la distribución.');
    }

    public function previsualizarCreacionMasiva(): void
    {
        $datos = $this->validarDatosMasivos();
        $fila = strtoupper(trim($datos['filaMasiva']));
        $numeros = range((int) $datos['numeroInicioMasivo'], (int) $datos['numeroFinMasivo']);
        $existentes = Asiento::query()
            ->where('id_sala', $this->salaId)
            ->where('fila', $fila)
            ->whereIn('numero', $numeros)
            ->pluck('numero')
            ->map(fn ($numero) => (int) $numero)
            ->all();
        $existentes = array_fill_keys($existentes, true);
        $faltantes = array_values(array_filter($numeros, fn ($numero) => !isset($existentes[$numero])));

        $this->vistaPreviaMasiva = [
            'fila' => $fila,
            'inicio' => (int) $datos['numeroInicioMasivo'],
            'fin' => (int) $datos['numeroFinMasivo'],
            'total' => count($numeros),
            'existentes' => count($existentes),
            'faltantes' => count($faltantes),
            'numeros_faltantes' => $faltantes,
            'tipo' => $datos['tipoMasivo'],
            'activo' => (int) $datos['activoMasivo'],
        ];
        $this->resetValidation();
    }

    public function crearAsientosMasivos(): void
    {
        $datos = $this->validarDatosMasivos();
        $fila = strtoupper(trim($datos['filaMasiva']));
        $inicio = (int) $datos['numeroInicioMasivo'];
        $fin = (int) $datos['numeroFinMasivo'];

        $creados = DB::transaction(function () use ($fila, $inicio, $fin, $datos): int {
            $numeros = range($inicio, $fin);
            $existentes = Asiento::query()
                ->where('id_sala', $this->salaId)
                ->where('fila', $fila)
                ->whereIn('numero', $numeros)
                ->lockForUpdate()
                ->pluck('numero')
                ->map(fn ($numero) => (int) $numero)
                ->all();
            $existentes = array_fill_keys($existentes, true);
            $faltantes = array_values(array_filter($numeros, fn ($numero) => !isset($existentes[$numero])));

            foreach (array_chunk($faltantes, 100) as $grupo) {
                $ahora = collect($grupo)->map(fn ($numero) => [
                    'id_sala' => $this->salaId,
                    'fila' => $fila,
                    'numero' => $numero,
                    'tipo' => $datos['tipoMasivo'],
                    'activo' => (int) $datos['activoMasivo'],
                ])->all();
                if ($ahora !== []) {
                    DB::table('asiento')->insertOrIgnore($ahora);
                }
            }

            return count($faltantes);
        });

        $this->vistaPreviaMasiva = [];
        unset($this->asientos);
        $this->fila = $fila;
        $this->numero = $inicio;
        $this->seleccionarCelda($fila, $inicio);
        $this->dispatch('distribucion-guardada', mensaje: $creados > 0
            ? "Se agregaron {$creados} asiento(s). Los existentes se conservaron sin cambios."
            : 'No había asientos faltantes en ese rango; no se realizaron cambios.');
    }


    public function activarSeleccionMultiple(): void
    {
        $this->modoSeleccionMultiple = true;
        $this->asientosSeleccionados = [];
        $this->vistaPreviaEdicionMasiva = [];
        $this->resetValidation();
    }

    public function cancelarSeleccionMultiple(): void
    {
        $this->modoSeleccionMultiple = false;
        $this->asientosSeleccionados = [];
        $this->vistaPreviaEdicionMasiva = [];
        $this->tipoEdicionMasiva = '';
        $this->activoEdicionMasiva = '';
        $this->resetValidation();
    }

    public function previsualizarEdicionMasiva(): void
    {
        $datos = $this->validarEdicionMasiva();
        $ids = $this->idsSeleccionadosValidos();
        if ($ids === []) {
            throw \Illuminate\Validation\ValidationException::withMessages(['asientosSeleccionados' => 'Selecciona al menos un asiento existente.']);
        }

        $asientos = Asiento::query()->where('id_sala', $this->salaId)->whereIn('id_asiento', $ids)->get();
        $protegidos = DB::table('funcion_asiento')->whereIn('id_asiento', $asientos->pluck('id_asiento'))->distinct()->pluck('id_asiento')->map(fn ($id) => (int) $id)->all();
        $cambioTipo = $datos['tipoEdicionMasiva'] !== '';
        $this->vistaPreviaEdicionMasiva = [
            'seleccionados' => $asientos->count(),
            'protegidos' => count($protegidos),
            'tipo' => $datos['tipoEdicionMasiva'],
            'activo' => $datos['activoEdicionMasiva'],
            'modificables_tipo' => $cambioTipo ? $asientos->whereNotIn('id_asiento', $protegidos)->count() : 0,
            'tipo_bloqueado' => $cambioTipo ? count($protegidos) : 0,
            'afectados_estado' => $datos['activoEdicionMasiva'] !== '' ? $asientos->count() : 0,
        ];
    }

    public function aplicarEdicionMasiva(): void
    {
        $datos = $this->validarEdicionMasiva();
        $ids = $this->idsSeleccionadosValidos();
        if ($ids === []) {
            throw \Illuminate\Validation\ValidationException::withMessages(['asientosSeleccionados' => 'Selecciona al menos un asiento existente.']);
        }
        if ($datos['tipoEdicionMasiva'] === '' && $datos['activoEdicionMasiva'] === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['tipoEdicionMasiva' => 'Elige al menos un cambio: tipo o disponibilidad.']);
        }

        $resultado = DB::transaction(function () use ($ids, $datos): array {
            $asientos = Asiento::query()->where('id_sala', $this->salaId)->whereIn('id_asiento', $ids)->lockForUpdate()->get();
            if ($asientos->count() !== count($ids)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['asientosSeleccionados' => 'La selección contiene asientos que ya no pertenecen a esta sala. Actualiza la selección e inténtalo de nuevo.']);
            }
            $protegidos = DB::table('funcion_asiento')->whereIn('id_asiento', $ids)->distinct()->pluck('id_asiento')->map(fn ($id) => (int) $id)->all();
            $actualizados = 0;
            foreach ($asientos as $asiento) {
                $esProtegido = in_array((int) $asiento->id_asiento, $protegidos, true);
                $cambios = [];
                if ($datos['activoEdicionMasiva'] !== '') {
                    $cambios['activo'] = (int) $datos['activoEdicionMasiva'];
                }
                if ($datos['tipoEdicionMasiva'] !== '' && !$esProtegido) {
                    $cambios['tipo'] = $datos['tipoEdicionMasiva'];
                }
                if ($cambios !== []) {
                    $asiento->update($cambios);
                    $actualizados++;
                }
            }
            return ['actualizados' => $actualizados, 'protegidos' => count($protegidos)];
        });

        $mensaje = "Se actualizaron {$resultado['actualizados']} asiento(s).";
        if ($resultado['protegidos'] > 0 && $datos['tipoEdicionMasiva'] !== '') {
            $mensaje .= " El tipo se conservó en {$resultado['protegidos']} asiento(s) protegidos por historial; su disponibilidad sí se actualizó si fue solicitada.";
        }
        $this->cancelarSeleccionMultiple();
        unset($this->asientos);
        $this->dispatch('distribucion-guardada', mensaje: $mensaje);
    }

    private function validarEdicionMasiva(): array
    {
        return $this->validate([
            'tipoEdicionMasiva' => ['nullable', 'string', Rule::in(['', 'ESTANDAR', 'PREFERENCIAL', 'ACCESIBLE'])],
            'activoEdicionMasiva' => ['nullable', 'string', Rule::in(['', '0', '1'])],
        ], [
            'tipoEdicionMasiva.in' => 'Selecciona un tipo válido.',
            'activoEdicionMasiva.in' => 'Selecciona una disponibilidad válida.',
        ]);
    }

    private function idsSeleccionadosValidos(): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) ? (int) $id : null, $this->asientosSeleccionados), fn ($id) => $id !== null && $id > 0)));
    }

    private function validarDatosMasivos(): array
    {
        $datos = $this->validate([
            'filaMasiva' => ['required', 'string', 'max:5', 'regex:/^[A-Za-z0-9]+$/'],
            'numeroInicioMasivo' => ['required', 'integer', 'min:1', 'max:65535'],
            'numeroFinMasivo' => ['required', 'integer', 'gte:numeroInicioMasivo', 'max:65535'],
            'tipoMasivo' => ['required', Rule::in(['ESTANDAR', 'PREFERENCIAL', 'ACCESIBLE'])],
            'activoMasivo' => ['required', Rule::in(['0', '1'])],
        ], [
            'filaMasiva.required' => 'Indica la fila que quieres completar.',
            'filaMasiva.regex' => 'La fila solo puede contener letras y números.',
            'numeroInicioMasivo.min' => 'El número inicial debe ser mayor que cero.',
            'numeroFinMasivo.gte' => 'El número final debe ser igual o mayor que el inicial.',
        ]);

        if (((int) $datos['numeroFinMasivo'] - (int) $datos['numeroInicioMasivo']) > 99) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'numeroFinMasivo' => 'Puedes generar como máximo 100 posiciones por operación.',
            ]);
        }

        return $datos;
    }

    public function render()
    {
        return view('livewire.admin.salas.editor-distribucion-sala');
    }

    private function validarUbicacion(string $fila, int $numero): void
    {
        validator(
            ['fila' => $fila, 'numero' => $numero],
            ['fila' => ['required', 'string', 'max:5'], 'numero' => ['required', 'integer', 'min:1', 'max:65535']]
        )->validate();
    }
}
