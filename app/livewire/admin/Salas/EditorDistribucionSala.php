<?php

namespace App\Livewire\Admin\Salas;

use App\Models\Sala;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Editor de distribución de salas (lado servidor).
 *
 * Todo el borrador vive en Alpine.js (public/js/editor-distribucion-salas.js).
 * Este componente solo:
 *   1. entrega la distribución persistida al montar,
 *   2. valida y persiste el borrador completo en guardarDistribucion(),
 *   3. entrega un snapshot fresco en recargarDistribucion().
 *
 * El cliente nunca es autoridad: el campo "protegido" se recalcula aquí
 * desde funcion_asiento y toda regla de negocio se vuelve a verificar.
 */
class EditorDistribucionSala extends Component
{
    private const TIPOS = ['ESTANDAR', 'PREFERENCIAL', 'ACCESIBLE'];
    private const MAX_ASIENTOS = 2000;
    private const MAX_PASILLOS = 2000;

    #[Locked] public int $salaId;
    #[Locked] public string $salaNombre = '';
    #[Locked] public ?int $capacidad = null;

    /** Snapshot persistido que el editor cargó (base de la verificación de concurrencia). */
    #[Locked] public array $asientosOriginales = [];
    #[Locked] public array $pasillosOriginales = [];

    public function mount(int $salaId): void
    {
        $this->salaId = $salaId;

        $sala = Sala::query()->findOrFail($salaId);
        $this->salaNombre = (string) $sala->nombre;
        $this->capacidad = $sala->capacidad !== null ? (int) $sala->capacidad : null;

        $this->cargarDistribucionPersistida();
    }

    public function render()
    {
        return view('livewire.admin.salas.editor-distribucion-sala', [
            'snapshot' => $this->snapshot(),
        ]);
    }

    /**
     * Guarda la distribución completa enviada por Alpine.
     *
     * Payload esperado:
     * {
     *   "asientos": [{"id": 101|null, "fila": "A", "numero": 1, "tipo": "ESTANDAR", "activo": true}],
     *   "pasillos": {
     *     "longitudinales": [{"fila": "A", "despues_de_numero": 2}],
     *     "transversales": [{"fila": "B", "fila_siguiente": "C"}]
     *   }
     * }
     *
     * Los errores esperados NO lanzan excepción: se devuelven como
     * ['ok' => false, 'mensaje' => '...', 'conflicto' => bool].
     * En éxito: ['ok' => true, 'mensaje' => '...', 'snapshot' => [...]].
     */
    public function guardarDistribucion(array $payload): array
    {
        $validator = Validator::make($payload, [
            'asientos' => ['present', 'array', 'max:' . self::MAX_ASIENTOS],
            'asientos.*' => ['array'],
            'asientos.*.id' => ['nullable', 'integer', 'min:1'],
            'asientos.*.fila' => ['required', 'string', 'regex:/^[A-Z0-9]{1,5}$/'],
            'asientos.*.numero' => ['required', 'integer', 'min:1', 'max:65535'],
            'asientos.*.tipo' => ['required', Rule::in(self::TIPOS)],
            'asientos.*.activo' => ['required', 'boolean'],
            'pasillos' => ['required', 'array'],
            'pasillos.longitudinales' => ['present', 'array', 'max:' . self::MAX_PASILLOS],
            'pasillos.longitudinales.*' => ['array'],
            'pasillos.longitudinales.*.fila' => ['required', 'string', 'regex:/^[A-Z0-9]{1,5}$/'],
            'pasillos.longitudinales.*.despues_de_numero' => ['required', 'integer', 'min:1', 'max:65534'],
            'pasillos.transversales' => ['present', 'array', 'max:' . self::MAX_PASILLOS],
            'pasillos.transversales.*' => ['array'],
            'pasillos.transversales.*.fila' => ['required', 'string', 'regex:/^[A-Z0-9]{1,5}$/'],
            'pasillos.transversales.*.fila_siguiente' => ['required', 'string', 'regex:/^[A-Z0-9]{1,5}$/'],
        ]);

        if ($validator->fails()) {
            return $this->falla('La estructura de la distribución no es válida. ' . $validator->errors()->first());
        }

        $datos = $validator->validated();

        // Reconstruye el borrador desde el payload. "protegido" del cliente se ignora.
        $borrador = [];
        $idTemporal = -1;
        foreach ($datos['asientos'] as $a) {
            $id = $a['id'] ?? null;
            $borrador[] = [
                'id' => $id === null ? $idTemporal-- : (int) $id,
                'fila' => (string) $a['fila'],
                'numero' => (int) $a['numero'],
                'tipo' => (string) $a['tipo'],
                'activo' => filter_var($a['activo'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ];
        }

        $pasillos = [];
        foreach ($datos['pasillos']['longitudinales'] as $p) {
            $pasillos[] = [
                'orientacion' => 'LONGITUDINAL',
                'fila' => (string) $p['fila'],
                'despues_de_numero' => (int) $p['despues_de_numero'],
                'fila_siguiente' => null,
            ];
        }
        foreach ($datos['pasillos']['transversales'] as $p) {
            $pasillos[] = [
                'orientacion' => 'TRANSVERSAL',
                'fila' => (string) $p['fila'],
                'despues_de_numero' => null,
                'fila_siguiente' => (string) $p['fila_siguiente'],
            ];
        }

        $errorBorrador = $this->validarBorrador($borrador, $pasillos);
        if ($errorBorrador !== null) {
            return $this->falla($errorBorrador);
        }

        // Las reglas que dependen del estado persistido se validan antes de escribir.
        // Si una falla, la transacción termina sin escrituras y el error se devuelve después.
        try {
            $resultado = DB::transaction(fn () => $this->persistir($borrador, $pasillos));
        } catch (QueryException $exception) {
            report($exception);

            return $this->falla('No se pudo guardar la distribución porque la base de datos rechazó la operación. Recarga la distribución e inténtalo de nuevo.');
        }

        if ($resultado !== null) {
            return $this->falla($resultado['mensaje'], $resultado['conflicto']);
        }

        $this->cargarDistribucionPersistida();

        return [
            'ok' => true,
            'mensaje' => 'Distribución guardada correctamente.',
            'snapshot' => $this->snapshot(),
        ];
    }

    /** Devuelve un snapshot fresco desde la base de datos y actualiza los originales bloqueados. */
    public function recargarDistribucion(): array
    {
        $this->cargarDistribucionPersistida();

        return $this->snapshot();
    }

    /**
     * Escribe el borrador dentro de la transacción.
     *
     * @return array{mensaje: string, conflicto: bool}|null null si todo se guardó.
     */
    private function persistir(array $borrador, array $pasillos): ?array
    {
        $sala = DB::table('sala')->where('id_sala', $this->salaId)->lockForUpdate()->first();
        if (! $sala) {
            return ['mensaje' => 'La sala ya no existe.', 'conflicto' => true];
        }

        $persistidos = DB::table('asiento')->where('id_sala', $this->salaId)->lockForUpdate()->get()->keyBy('id_asiento');
        $originalIds = collect($this->asientosOriginales)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $currentIds = $persistidos->keys()->map(fn ($id) => (int) $id)->all();
        if (array_diff($originalIds, $currentIds) || array_diff($currentIds, $originalIds)) {
            return ['mensaje' => 'La sala cambió en otra sesión. Recarga la distribución antes de guardar.', 'conflicto' => true];
        }

        // Evita sobrescribir cambios hechos por otra sesión aunque los IDs sigan iguales.
        $snapshotPersistido = $persistidos->map(fn ($asiento) => [
            'id' => (int) $asiento->id_asiento,
            'fila' => (string) $asiento->fila,
            'numero' => (int) $asiento->numero,
            'tipo' => (string) $asiento->tipo,
            'activo' => (int) $asiento->activo,
        ])->values()->all();

        if ($this->normalizarAsientos($snapshotPersistido) !== $this->normalizarAsientos($this->asientosOriginales)) {
            return ['mensaje' => 'Los asientos cambiaron en otra sesión. Recarga la distribución antes de guardar.', 'conflicto' => true];
        }

        $pasillosPersistidos = DB::table('sala_pasillo')
            ->where('id_sala', $this->salaId)
            ->lockForUpdate()
            ->get()
            ->map(fn ($pasillo) => (array) $pasillo)
            ->all();

        if ($this->normalizarPasillos($pasillosPersistidos) !== $this->normalizarPasillos($this->pasillosOriginales)) {
            return ['mensaje' => 'Los pasillos cambiaron en otra sesión. Recarga la distribución antes de guardar.', 'conflicto' => true];
        }

        // "protegido" se recalcula aquí: el cliente nunca es autoridad.
        $historial = DB::table('funcion_asiento')
            ->whereIn('id_asiento', $originalIds)
            ->lockForUpdate()
            ->pluck('id_asiento')
            ->map(fn ($id) => (int) $id)
            ->all();

        $draftById = [];
        foreach ($borrador as $asiento) {
            if ($asiento['id'] > 0) {
                $draftById[$asiento['id']] = $asiento;
            }
        }

        // Valida todos los asientos protegidos antes de la primera escritura.
        foreach ($this->asientosOriginales as $original) {
            $id = (int) $original['id'];
            $old = $persistidos[$id];
            $protegido = in_array($id, $historial, true);
            $draft = $draftById[$id] ?? null;

            if ($protegido && $draft === null) {
                return ['mensaje' => "No se puede eliminar el asiento {$old->fila}-{$old->numero}: tiene historial de funciones.", 'conflicto' => false];
            }

            if ($protegido && (
                (string) $draft['fila'] !== (string) $old->fila
                || (int) $draft['numero'] !== (int) $old->numero
                || (string) $draft['tipo'] !== (string) $old->tipo
            )) {
                return ['mensaje' => "El asiento {$old->fila}-{$old->numero} tiene historial; solo puede cambiar su disponibilidad.", 'conflicto' => false];
            }
        }

        // Evita colisiones temporales del índice único (id_sala, fila, numero) al
        // intercambiar o mover ubicaciones dentro de la misma transacción.
        $filasTemporales = [];
        $indiceTemporal = 0;
        foreach ($this->asientosOriginales as $original) {
            $id = (int) $original['id'];
            if (in_array($id, $historial, true)) {
                continue;
            }
            $draft = $draftById[$id] ?? null;
            if ($draft === null || (string) $draft['fila'] !== (string) $persistidos[$id]->fila || (int) $draft['numero'] !== (int) $persistidos[$id]->numero) {
                do {
                    $codigo = '~' . str_pad(base_convert((string) $indiceTemporal++, 10, 36), 4, '0', STR_PAD_LEFT);
                } while (in_array($codigo, $filasTemporales, true) || DB::table('asiento')->where('id_sala', $this->salaId)->where('fila', $codigo)->exists());
                $filasTemporales[] = $codigo;
                DB::table('asiento')->where('id_asiento', $id)->update(['fila' => $codigo, 'numero' => 1]);
            }
        }

        foreach ($this->asientosOriginales as $original) {
            $id = (int) $original['id'];
            $draft = $draftById[$id] ?? null;
            if ($draft === null) {
                DB::table('asiento')->where('id_asiento', $id)->delete();
                continue;
            }
            DB::table('asiento')->where('id_asiento', $id)->update([
                'fila' => $draft['fila'],
                'numero' => (int) $draft['numero'],
                'tipo' => $draft['tipo'],
                'activo' => (int) $draft['activo'],
            ]);
        }

        foreach ($borrador as $asiento) {
            if ($asiento['id'] < 0) {
                DB::table('asiento')->insert([
                    'id_sala' => $this->salaId,
                    'fila' => $asiento['fila'],
                    'numero' => (int) $asiento['numero'],
                    'tipo' => $asiento['tipo'],
                    'activo' => (int) $asiento['activo'],
                ]);
            }
        }

        DB::table('sala_pasillo')->where('id_sala', $this->salaId)->delete();
        foreach ($pasillos as $pasillo) {
            DB::table('sala_pasillo')->insert([
                'id_sala' => $this->salaId,
                'fila' => $pasillo['fila'],
                'despues_de_numero' => $pasillo['despues_de_numero'] ?? null,
                'orientacion' => $pasillo['orientacion'] ?? 'LONGITUDINAL',
                'fila_siguiente' => $pasillo['fila_siguiente'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return null;
    }

    /**
     * Valida la estructura lógica del borrador reconstruido.
     * Devuelve un mensaje de error o null si es válido.
     */
    private function validarBorrador(array $asientos, array $pasillos): ?string
    {
        // Cada ID persistido debe pertenecer al inventario original de esta sala
        // y aparecer una sola vez. Los IDs negativos son asientos nuevos.
        $idsOriginales = [];
        foreach ($this->asientosOriginales as $original) {
            $idOriginal = filter_var($original['id'] ?? null, FILTER_VALIDATE_INT);
            if ($idOriginal === false || $idOriginal === null || $idOriginal <= 0 || isset($idsOriginales[$idOriginal])) {
                return 'No se pudo verificar el inventario original de asientos. Recarga la distribución.';
            }
            $idsOriginales[$idOriginal] = true;
        }

        $idsBorrador = [];
        $vistos = [];
        foreach ($asientos as $asiento) {
            $id = $asiento['id'];

            if ($id > 0 && ! isset($idsOriginales[$id])) {
                return 'El borrador contiene un asiento que no pertenece al inventario original de esta sala. Recarga la distribución.';
            }
            if (isset($idsBorrador[$id])) {
                return 'El borrador contiene un identificador de asiento repetido.';
            }
            $idsBorrador[$id] = true;

            $fila = $asiento['fila'];
            $numero = $asiento['numero'];
            if (
                $fila !== strtoupper(trim($fila))
                || ! preg_match('/^[A-Z0-9]{1,5}$/', $fila)
                || $numero < 1 || $numero > 65535
                || ! in_array($asiento['tipo'], self::TIPOS, true)
                || ! in_array($asiento['activo'], [0, 1], true)
            ) {
                return 'Hay un asiento con datos inválidos. Revisa fila, número, tipo y disponibilidad.';
            }

            $clave = $fila . '|' . $numero;
            if (isset($vistos[$clave])) {
                return "La ubicación {$fila}-{$numero} está duplicada.";
            }
            $vistos[$clave] = true;
        }

        $filaList = collect($asientos)->pluck('fila')->unique()->values()->all();
        sort($filaList, SORT_STRING);

        $pasillosVistos = [];
        foreach ($pasillos as $pasillo) {
            $orientacion = $pasillo['orientacion'];
            $filaPasillo = $pasillo['fila'];

            if ($orientacion === 'LONGITUDINAL') {
                $despues = $pasillo['despues_de_numero'];
                if ($despues < 1 || $despues > 65534) {
                    return 'Hay un pasillo longitudinal con ubicación inválida.';
                }
                $clave = 'LONGITUDINAL|' . $filaPasillo . '|' . $despues;
            } else {
                $filaSiguiente = $pasillo['fila_siguiente'];
                if (! in_array($filaPasillo, $filaList, true) || ! in_array($filaSiguiente, $filaList, true)) {
                    return 'El pasillo transversal debe vincular filas existentes.';
                }
                $indiceFila = array_search($filaPasillo, $filaList, true);
                if (($filaList[$indiceFila + 1] ?? null) !== $filaSiguiente) {
                    return 'El pasillo transversal debe separar filas consecutivas.';
                }
                $clave = 'TRANSVERSAL|' . $filaPasillo . '|' . $filaSiguiente;
            }

            if (isset($pasillosVistos[$clave])) {
                return 'El borrador contiene pasillos duplicados.';
            }
            $pasillosVistos[$clave] = true;
        }

        return null;
    }

    private function cargarDistribucionPersistida(): void
    {
        $seats = DB::table('asiento')
            ->where('id_sala', $this->salaId)
            ->orderBy('fila')
            ->orderBy('numero')
            ->get();

        $protegidos = DB::table('funcion_asiento')
            ->whereIn('id_asiento', $seats->pluck('id_asiento'))
            ->distinct()
            ->pluck('id_asiento')
            ->map(fn ($v) => (int) $v)
            ->all();

        $this->asientosOriginales = $seats->map(fn ($a) => [
            'id' => (int) $a->id_asiento,
            'fila' => (string) $a->fila,
            'numero' => (int) $a->numero,
            'tipo' => (string) $a->tipo,
            'activo' => (int) $a->activo,
            'protegido' => in_array((int) $a->id_asiento, $protegidos, true),
        ])->all();

        $this->pasillosOriginales = DB::table('sala_pasillo')
            ->where('id_sala', $this->salaId)
            ->get()
            ->map(fn ($p) => (array) $p)
            ->all();
    }

    /** Distribución persistida en el formato que consume Alpine. */
    private function snapshot(): array
    {
        $longitudinales = [];
        $transversales = [];
        foreach ($this->pasillosOriginales as $p) {
            if (($p['orientacion'] ?? 'LONGITUDINAL') === 'TRANSVERSAL') {
                $transversales[] = [
                    'fila' => (string) $p['fila'],
                    'fila_siguiente' => (string) ($p['fila_siguiente'] ?? ''),
                ];
            } else {
                $longitudinales[] = [
                    'fila' => (string) $p['fila'],
                    'despues_de_numero' => (int) $p['despues_de_numero'],
                ];
            }
        }

        return [
            'asientos' => $this->asientosOriginales,
            'pasillos' => [
                'longitudinales' => $longitudinales,
                'transversales' => $transversales,
            ],
        ];
    }

    private function normalizarAsientos(array $rows): array
    {
        $out = [];
        foreach ($rows as $a) {
            $out[] = [
                'id' => (int) $a['id'],
                'fila' => (string) $a['fila'],
                'numero' => (int) $a['numero'],
                'tipo' => (string) $a['tipo'],
                'activo' => (int) $a['activo'],
            ];
        }
        usort($out, fn ($a, $b) => [$a['id'], $a['fila'], $a['numero']] <=> [$b['id'], $b['fila'], $b['numero']]);

        return $out;
    }

    private function normalizarPasillos(array $rows): array
    {
        $out = [];
        foreach ($rows as $p) {
            $out[] = [
                'orientacion' => $p['orientacion'] ?? 'LONGITUDINAL',
                'fila' => (string) $p['fila'],
                'fila_siguiente' => $p['fila_siguiente'] ?? null,
                'despues_de_numero' => isset($p['despues_de_numero']) ? (int) $p['despues_de_numero'] : null,
            ];
        }
        usort($out, fn ($a, $b) => serialize($a) <=> serialize($b));

        return $out;
    }

    private function falla(string $mensaje, bool $conflicto = false): array
    {
        return ['ok' => false, 'mensaje' => $mensaje, 'conflicto' => $conflicto];
    }
}
