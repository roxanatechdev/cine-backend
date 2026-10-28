<?php

namespace App\Livewire\Admin\Salas;

use App\Models\Sala;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EditorDistribucionSala extends Component
{
    #[Locked] public int $salaId;
    #[Locked] public array $asientosOriginales = [];
    #[Locked] public array $pasillosOriginales = [];
    public array $borradorAsientos = [];
    public array $borradorPasillos = [];
    public bool $hayCambiosPendientes = false;
    #[Locked] public int $siguienteIdTemporal = -1;

    #[Locked] public ?int $asientoSeleccionadoId = null;
    public string $fila = 'A'; public int $numero = 1; public string $tipo = 'ESTANDAR'; public string $activo = '1';
    public string $filaMasiva = 'A'; public int $numeroInicioMasivo = 1; public int $numeroFinMasivo = 10;
    public string $tipoMasivo = 'ESTANDAR'; public string $activoMasivo = '1'; public array $vistaPreviaMasiva = [];
    public bool $modoSeleccionMultiple = false; public array $asientosSeleccionados = [];
    public string $tipoEdicionMasiva = ''; public string $activoEdicionMasiva = ''; public array $vistaPreviaEdicionMasiva = [];

    public function mount(int $salaId): void
    {
        $this->salaId = $salaId;
        $this->cargarDistribucionPersistida();
    }

    #[Computed]
    public function sala(): Sala { return Sala::query()->findOrFail($this->salaId); }

    #[Computed]
    public function asientos(): array
    {
        // El renderizado no debe fallar si llega un borrador manipulado o incompleto.
        // Los registros inválidos se omiten solo de la representación; guardarDistribucion()
        // conserva la responsabilidad de rechazarlos mediante validarBorrador().
        $seats = collect(is_array($this->borradorAsientos) ? $this->borradorAsientos : [])
            ->filter(fn ($a) => is_array($a) && array_key_exists('fila', $a) && array_key_exists('numero', $a))
            ->values();
        $pasillos = collect(is_array($this->borradorPasillos) ? $this->borradorPasillos : []);
        $longitudinales = $pasillos->where('orientacion', 'LONGITUDINAL');
        $transversales = $pasillos->where('orientacion', 'TRANSVERSAL');
        $filas = $seats->pluck('fila')->merge($longitudinales->pluck('fila'))->merge($transversales->pluck('fila'))->merge($transversales->pluck('fila_siguiente'))->filter()->unique()->sort()->values();
        if ($filas->isEmpty()) $filas = collect(['A','B','C','D','E','F']);
        $maximo = (int) ($seats->max('numero') ?? 0);
        $columnas = range(1, min(max($maximo, 12), 30));
        $pasillosPorFila = []; foreach ($longitudinales as $p) $pasillosPorFila[$p['fila']][(int)$p['despues_de_numero']] = true;
        $transversalesPorPar = []; foreach ($transversales as $p) $transversalesPorPar[$p['fila']][$p['fila_siguiente']] = true;
        $porUbicacion = []; foreach ($seats as $a) { if (!is_array($a)) continue; $filaAsiento = (string) ($a['fila'] ?? ''); $numeroAsiento = (int) ($a['numero'] ?? 0); $porUbicacion[$filaAsiento][$numeroAsiento] = ['id'=>(int)($a['id'] ?? 0),'fila'=>$filaAsiento,'numero'=>$numeroAsiento,'tipo'=>(string)($a['tipo'] ?? ''),'activo'=>(int)($a['activo'] ?? 0),'protegido'=>(bool)($a['protegido'] ?? false)]; }
        $advertencias=[]; $huecos=0; $filasConHuecos=0; $porFila=$seats->groupBy('fila');
        foreach (array_keys($pasillosPorFila) as $f) if (!$porFila->has($f)) $advertencias[]=['tipo'=>'fila_sin_asientos','fila'=>$f,'cantidad'=>0,'numeros'=>[],'mensaje'=>"La fila {$f} tiene pasillos registrados, pero no tiene asientos."];
        foreach ($porFila as $f=>$grupo) { $nums=$grupo->pluck('numero')->map(fn($n)=>(int)$n)->unique()->sort()->values(); if($nums->count()<2) continue; $missing=[]; for($n=$nums->first()+1;$n<$nums->last();$n++) if(!$nums->contains($n)) $missing[]=$n; if($missing){$huecos+=count($missing);$filasConHuecos++;$advertencias[]=['tipo'=>'huecos','fila'=>$f,'cantidad'=>count($missing),'numeros'=>$missing,'mensaje'=>'La fila '.$f.' tiene posiciones vacías dentro de su rango: '.implode(', ',$missing).'.'];} }
        return ['filas'=>$filas->all(),'columnas'=>$columnas,'asientos'=>$porUbicacion,'pasillos'=>$pasillosPorFila,'pasillos_transversales'=>$transversalesPorPar,'total'=>$seats->count(),'activos'=>$seats->where('activo',1)->count(),'inactivos'=>$seats->where('activo',0)->count(),'protegidos'=>$seats->where('protegido',true)->count(),'total_pasillos'=>$pasillos->count(),'huecos'=>$huecos,'filas_con_huecos'=>$filasConHuecos,'advertencias'=>$advertencias,'fuera_de_rango'=>$seats->filter(fn($a)=>(int)$a['numero']>30)->count()];
    }

    private function cargarDistribucionPersistida(): void
    {
        $seats=DB::table('asiento as a')->where('a.id_sala',$this->salaId)->orderBy('a.fila')->orderBy('a.numero')->get();
        $ids=$seats->pluck('id_asiento');
        $protected=DB::table('funcion_asiento')->whereIn('id_asiento',$ids)->distinct()->pluck('id_asiento')->map(fn($v)=>(int)$v)->all();
        $this->asientosOriginales=$seats->map(fn($a)=>['id'=>(int)$a->id_asiento,'fila'=>(string)$a->fila,'numero'=>(int)$a->numero,'tipo'=>(string)$a->tipo,'activo'=>(int)$a->activo,'protegido'=>in_array((int)$a->id_asiento,$protected,true)])->all();
        $this->borradorAsientos=$this->asientosOriginales;
        $this->pasillosOriginales=DB::table('sala_pasillo')->where('id_sala',$this->salaId)->get()->map(fn($p)=>(array)$p)->all();
        $this->borradorPasillos=$this->pasillosOriginales;
        $this->hayCambiosPendientes=false; $this->siguienteIdTemporal=-1;
    }

    private function marcarBorradorModificado(): void { $this->hayCambiosPendientes = $this->normalizarAsientos($this->borradorAsientos) !== $this->normalizarAsientos($this->asientosOriginales) || $this->normalizarPasillos($this->borradorPasillos) !== $this->normalizarPasillos($this->pasillosOriginales); unset($this->asientos); }
    private function normalizarAsientos(array $rows): array { $out=[]; foreach($rows as $a) $out[]=['id'=>(int)$a['id'],'fila'=>(string)$a['fila'],'numero'=>(int)$a['numero'],'tipo'=>(string)$a['tipo'],'activo'=>(int)$a['activo']]; usort($out,fn($a,$b)=>[$a['id'],$a['fila'],$a['numero']] <=> [$b['id'],$b['fila'],$b['numero']]); return $out; }
    private function normalizarPasillos(array $rows): array { $out=[]; foreach($rows as $p) $out[]=['orientacion'=>$p['orientacion']??'LONGITUDINAL','fila'=>(string)$p['fila'],'fila_siguiente'=>$p['fila_siguiente']??null,'despues_de_numero'=>isset($p['despues_de_numero'])?(int)$p['despues_de_numero']:null]; usort($out,fn($a,$b)=>serialize($a)<=>serialize($b)); return $out; }

    public function guardarDistribucion(): void
    {
        $this->validarBorrador();

        // Validamos las reglas que dependen del estado persistido antes de escribir.
        // Si una regla falla, la transacción termina sin escrituras y la excepción se
        // comunica después, evitando rollback de savepoint por un rechazo esperado.
        $errorValidacion = DB::transaction(function () {
            $sala = DB::table('sala')->where('id_sala', $this->salaId)->lockForUpdate()->first();
            if (!$sala) {
                return 'La sala ya no existe.';
            }

            $persistidos = DB::table('asiento')->where('id_sala', $this->salaId)->lockForUpdate()->get()->keyBy('id_asiento');
            $originalIds = collect($this->asientosOriginales)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $currentIds = $persistidos->keys()->map(fn ($id) => (int) $id)->all();
            if (array_diff($originalIds, $currentIds) || array_diff($currentIds, $originalIds)) {
                return 'La sala cambió en otra sesión. Cancela y vuelve a cargar la distribución antes de guardar.';
            }

            // Evita sobrescribir cambios realizados por otra sesión después de abrir
            // el editor, aunque los identificadores de los asientos sigan siendo iguales.
            $snapshotPersistido = $persistidos->map(fn ($asiento) => [
                'id' => (int) $asiento->id_asiento,
                'fila' => (string) $asiento->fila,
                'numero' => (int) $asiento->numero,
                'tipo' => (string) $asiento->tipo,
                'activo' => (int) $asiento->activo,
            ])->values()->all();

            if ($this->normalizarAsientos($snapshotPersistido) !== $this->normalizarAsientos($this->asientosOriginales)) {
                return 'Los asientos cambiaron en otra sesión. Cancela y vuelve a cargar la distribución antes de guardar.';
            }

            // Los pasillos también forman parte de la distribución y deben conservar
            // la misma versión que se cargó al abrir el editor.
            $pasillosPersistidos = DB::table('sala_pasillo')
                ->where('id_sala', $this->salaId)
                ->lockForUpdate()
                ->get()
                ->map(fn ($pasillo) => (array) $pasillo)
                ->all();

            if ($this->normalizarPasillos($pasillosPersistidos) !== $this->normalizarPasillos($this->pasillosOriginales)) {
                return 'Los pasillos cambiaron en otra sesión. Cancela y vuelve a cargar la distribución antes de guardar.';
            }

            $historial = DB::table('funcion_asiento')->whereIn('id_asiento', $originalIds)->lockForUpdate()->pluck('id_asiento')->map(fn ($id) => (int) $id)->all();
            $draftById = [];
            foreach ($this->borradorAsientos as $asiento) {
                if ((int) $asiento['id'] > 0) {
                    $draftById[(int) $asiento['id']] = $asiento;
                }
            }

            // Validar todos los asientos protegidos antes de la primera escritura.
            foreach ($this->asientosOriginales as $original) {
                $id = (int) $original['id'];
                $old = $persistidos[$id];
                $protegido = in_array($id, $historial, true);
                $draft = $draftById[$id] ?? null;

                if ($protegido && $draft === null) {
                    return "No se puede eliminar el asiento {$old->fila}-{$old->numero}: tiene historial de funciones.";
                }

                if ($protegido && (
                    (string) $draft['fila'] !== (string) $old->fila
                    || (int) $draft['numero'] !== (int) $old->numero
                    || (string) $draft['tipo'] !== (string) $old->tipo
                )) {
                    return "El asiento {$old->fila}-{$old->numero} tiene historial; solo puede cambiar su disponibilidad.";
                }
            }

            // Evita colisiones temporales del índice único (id_sala, fila, numero)
            // al intercambiar ubicaciones dentro de la misma transacción.
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

            foreach ($this->borradorAsientos as $asiento) {
                if ((int) $asiento['id'] < 0) {
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
            foreach ($this->borradorPasillos as $pasillo) {
                DB::table('sala_pasillo')->insert([
                    'id_sala' => $this->salaId,
                    'fila' => $pasillo['fila'],
                    'despues_de_numero' => $pasillo['despues_de_numero'] ?? null,
                    'orientacion' => $pasillo['orientacion'] ?? 'LONGITUDINAL',
                    'fila_siguiente' => $pasillo['fila_siguiente'] ?? null,
                    'created_at' => $pasillo['created_at'] ?? now(),
                    'updated_at' => now(),
                ]);
            }

            return null;
        });

        if ($errorValidacion !== null) {
            throw ValidationException::withMessages(['distribucion' => $errorValidacion]);
        }

        $this->cargarDistribucionPersistida();
        $this->asientoSeleccionadoId = null;
        $this->vistaPreviaMasiva = [];
        $this->cancelarSeleccionMultiple();
        $this->resetValidation();
        $this->dispatch('editor-mensaje', mensaje: 'Distribución guardada correctamente.', tipo: 'success');
    }

    public function cancelarDistribucion(): void
    {
        // Cancelar siempre vuelve a leer la base de datos: el borrador del cliente
        // nunca se usa como fuente para reconstruir el estado persistido.
        $this->cargarDistribucionPersistida();
        $this->asientoSeleccionadoId = null;
        $this->fila = 'A';
        $this->numero = 1;
        $this->tipo = 'ESTANDAR';
        $this->activo = '1';
        $this->vistaPreviaMasiva = [];
        $this->filaMasiva = 'A';
        $this->numeroInicioMasivo = 1;
        $this->numeroFinMasivo = 10;
        $this->tipoMasivo = 'ESTANDAR';
        $this->activoMasivo = '1';
        $this->cancelarSeleccionMultiple();
        $this->resetValidation();
        $this->dispatch('editor-mensaje', mensaje: 'Cambios pendientes descartados; se restauró la distribución guardada.', tipo: 'info');
    }

    private function validarBorrador(): void
    {
        // Los identificadores del borrador no son autoridad: cada ID persistido
        // debe pertenecer al inventario original de esta sala y aparecer una sola vez.
        // Los IDs negativos solo representan asientos nuevos todavía no persistidos.
        if (!is_array($this->borradorAsientos) || !is_array($this->borradorPasillos)) {
            throw ValidationException::withMessages([
                'distribucion' => 'La estructura del borrador no es válida. Cancela y vuelve a cargar la distribución.',
            ]);
        }

        $idsOriginales = [];
        foreach ($this->asientosOriginales as $original) {
            if (!is_array($original)) {
                throw ValidationException::withMessages([
                    'distribucion' => 'No se pudo verificar el inventario original de asientos. Cancela y vuelve a cargar la distribución.',
                ]);
            }
            $idOriginal = filter_var($original['id'] ?? null, FILTER_VALIDATE_INT);
            if ($idOriginal === false || $idOriginal === null || $idOriginal <= 0 || isset($idsOriginales[$idOriginal])) {
                throw ValidationException::withMessages([
                    'distribucion' => 'No se pudo verificar el inventario original de asientos. Cancela y vuelve a cargar la distribución.',
                ]);
            }
            $idsOriginales[$idOriginal] = true;
        }

        $idsBorrador = [];
        foreach ($this->borradorAsientos as $asiento) {
            if (!is_array($asiento)) {
                throw ValidationException::withMessages([
                    'distribucion' => 'El borrador contiene un registro de asiento con estructura inválida.',
                ]);
            }
            $id = filter_var($asiento['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id === null || $id === 0) {
                throw ValidationException::withMessages([
                    'distribucion' => 'Hay un asiento con identificador inválido en el borrador.',
                ]);
            }

            if ($id > 0 && !isset($idsOriginales[$id])) {
                throw ValidationException::withMessages([
                    'distribucion' => 'El borrador contiene un asiento que no pertenece al inventario original de esta sala. Cancela y vuelve a cargar la distribución.',
                ]);
            }

            if (isset($idsBorrador[$id])) {
                throw ValidationException::withMessages([
                    'distribucion' => 'El borrador contiene un identificador de asiento repetido.',
                ]);
            }

            foreach (['fila', 'numero', 'tipo', 'activo'] as $campoObligatorio) {
                if (!array_key_exists($campoObligatorio, $asiento)) {
                    throw ValidationException::withMessages([
                        'distribucion' => 'El borrador contiene un registro de asiento incompleto.',
                    ]);
                }
            }

            $idsBorrador[$id] = true;
        }

        $seen = [];
        $rows = [];
        foreach ($this->borradorAsientos as $asiento) {
            $row = (string) ($asiento['fila'] ?? '');
            $numero = filter_var($asiento['numero'] ?? null, FILTER_VALIDATE_INT);
            $tipo = $asiento['tipo'] ?? '';
            $activo = filter_var($asiento['activo'] ?? null, FILTER_VALIDATE_INT);

            if ($row !== strtoupper(trim($row)
                ) || !preg_match('/^[A-Z0-9]{1,5}$/', $row)
                || $numero === false || $numero === null || $numero < 1 || $numero > 65535
                || !in_array($tipo, ['ESTANDAR', 'PREFERENCIAL', 'ACCESIBLE'], true)
                || !in_array($activo, [0, 1], true)) {
                throw ValidationException::withMessages([
                    'distribucion' => 'Hay un asiento con datos inválidos. Revisa fila, número, tipo y disponibilidad.',
                ]);
            }

            $key = $row . '|' . $numero;
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'distribucion' => "La ubicación {$row}-{$numero} está duplicada.",
                ]);
            }
            $seen[$key] = true;
            $rows[] = ['id' => (int) $asiento['id'], 'fila' => $row, 'numero' => $numero, 'tipo' => $tipo, 'activo' => $activo];
        }

        $filaList = collect($rows)->pluck('fila')->unique()->sort()->values()->all();
        $pasillosVistos = [];
        foreach ($this->borradorPasillos as $pasillo) {
            if (!is_array($pasillo)) {
                throw ValidationException::withMessages([
                    'distribucion' => 'El borrador contiene un pasillo con estructura inválida.',
                ]);
            }

            $orientacion = $pasillo['orientacion'] ?? 'LONGITUDINAL';
            $filaPasillo = (string) ($pasillo['fila'] ?? '');
            if ($filaPasillo !== strtoupper(trim($filaPasillo)) || !preg_match('/^[A-Z0-9]{1,5}$/', $filaPasillo)) {
                throw ValidationException::withMessages([
                    'distribucion' => 'Hay un pasillo con una fila inválida.',
                ]);
            }

            if ($orientacion === 'LONGITUDINAL') {
                $despues = filter_var($pasillo['despues_de_numero'] ?? null, FILTER_VALIDATE_INT);
                if ($despues === false || $despues === null || $despues < 1 || $despues > 65534) {
                    throw ValidationException::withMessages([
                        'distribucion' => 'Hay un pasillo longitudinal con ubicación inválida.',
                    ]);
                }
                $key = 'LONGITUDINAL|' . $filaPasillo . '|' . $despues;
            } elseif ($orientacion === 'TRANSVERSAL') {
                $filaSiguiente = $pasillo['fila_siguiente'] ?? null;
                if (!is_string($filaSiguiente)
                    || $filaSiguiente !== strtoupper(trim($filaSiguiente))
                    || !preg_match('/^[A-Z0-9]{1,5}$/', $filaSiguiente)
                    || !in_array($filaPasillo, $filaList, true)
                    || !in_array($filaSiguiente, $filaList, true)) {
                    throw ValidationException::withMessages([
                        'distribucion' => 'El pasillo transversal debe vincular filas existentes.',
                    ]);
                }

                $key = 'TRANSVERSAL|' . $filaPasillo . '|' . $filaSiguiente;
            } else {
                throw ValidationException::withMessages([
                    'distribucion' => 'La orientación de un pasillo no es válida.',
                ]);
            }

            if (isset($pasillosVistos[$key])) {
                throw ValidationException::withMessages([
                    'distribucion' => 'El borrador contiene pasillos duplicados.',
                ]);
            }
            $pasillosVistos[$key] = true;
        }

        foreach ($this->borradorPasillos as $pasillo) {
            if (($pasillo['orientacion'] ?? 'LONGITUDINAL') !== 'TRANSVERSAL') {
                continue;
            }

            $indiceFila = array_search($pasillo['fila'], $filaList, true);
            if ($indiceFila === false || ($filaList[$indiceFila + 1] ?? null) !== ($pasillo['fila_siguiente'] ?? null)) {
                throw ValidationException::withMessages([
                    'distribucion' => 'El pasillo transversal debe separar filas consecutivas.',
                ]);
            }
        }
    }

    public function alternarPasillo(string $fila,int $despuesDeNumero):void{$fila=strtoupper(trim($fila));validator(['fila'=>$fila,'despuesDeNumero'=>$despuesDeNumero],['fila'=>['required','regex:/^[A-Z0-9]{1,5}$/'],'despuesDeNumero'=>['required','integer','min:1','max:65534']])->validate();$idx=null;foreach($this->borradorPasillos as $i=>$p)if(($p['orientacion']??'LONGITUDINAL')==='LONGITUDINAL'&&$p['fila']===$fila&&(int)$p['despues_de_numero']===$despuesDeNumero){$idx=$i;break;}if($idx!==null)array_splice($this->borradorPasillos,$idx,1);else $this->borradorPasillos[]=['id_pasillo'=>null,'id_sala'=>$this->salaId,'fila'=>$fila,'despues_de_numero'=>$despuesDeNumero,'orientacion'=>'LONGITUDINAL','fila_siguiente'=>null];$this->marcarBorradorModificado();$this->dispatch('editor-mensaje',mensaje:'Pasillo actualizado en el borrador. Guarda la distribución para persistirlo.', tipo: 'info');}

    public function alternarPasilloTransversal(string $fila,string $filaSiguiente):void{$fila=strtoupper(trim($fila));$filaSiguiente=strtoupper(trim($filaSiguiente));$filas=collect($this->borradorAsientos)->pluck('fila')->unique()->sort()->values()->all();$idx=array_search($fila,$filas,true);if($idx===false||($filas[$idx+1]??null)!==$filaSiguiente)throw ValidationException::withMessages(['distribucion'=>'El pasillo transversal debe separar dos filas existentes y consecutivas.']);$found=null;foreach($this->borradorPasillos as $i=>$p)if(($p['orientacion']??'LONGITUDINAL')==='TRANSVERSAL'&&$p['fila']===$fila&&($p['fila_siguiente']??null)===$filaSiguiente){$found=$i;break;}if($found!==null)array_splice($this->borradorPasillos,$found,1);else $this->borradorPasillos[]=['id_pasillo'=>null,'id_sala'=>$this->salaId,'fila'=>$fila,'fila_siguiente'=>$filaSiguiente,'despues_de_numero'=>null,'orientacion'=>'TRANSVERSAL'];$this->marcarBorradorModificado();$this->dispatch('editor-mensaje',mensaje:'Pasillo transversal actualizado en el borrador. Guarda la distribución para persistirlo.', tipo: 'info');}

    public function seleccionarCelda(string $fila,int $numero):void{$this->validarUbicacion($fila,$numero);if($this->modoSeleccionMultiple){$a=$this->buscarAsiento($fila,$numero);if($a){$id=(int)$a['id'];$this->asientosSeleccionados=in_array($id,array_map('intval',$this->asientosSeleccionados),true)?array_values(array_diff(array_map('intval',$this->asientosSeleccionados),[$id])):array_values(array_unique([...array_map('intval',$this->asientosSeleccionados),$id]));$this->vistaPreviaEdicionMasiva=[];}return;}$this->resetValidation();$a=$this->buscarAsiento($fila,$numero);$this->fila=$fila;$this->numero=$numero;$this->asientoSeleccionadoId=$a?(int)$a['id']:null;$this->tipo=$a['tipo']??'ESTANDAR';$this->activo=(string)($a['activo']??1);}
    public function pintarAsiento(int $id, string $propiedad, string $valor): void
    {
        validator(
            ['id' => $id, 'propiedad' => $propiedad, 'valor' => $valor],
            [
                'id' => ['required', 'integer', 'not_in:0'],
                'propiedad' => ['required', Rule::in(['tipo', 'activo'])],
                'valor' => ['required', 'string', Rule::in($propiedad === 'tipo' ? ['ESTANDAR', 'PREFERENCIAL', 'ACCESIBLE'] : ['0', '1'])],
            ],
        )->validate();

        foreach ($this->borradorAsientos as &$asiento) {
            if ((int) $asiento['id'] !== $id) {
                continue;
            }

            if ($propiedad === 'tipo' && (bool) ($asiento['protegido'] ?? false)) {
                unset($asiento);
                throw ValidationException::withMessages([
                    'distribucion' => 'Este asiento tiene historial de funciones y no se puede cambiar de tipo.',
                ]);
            }

            $nuevoValor = $propiedad === 'activo' ? (int) $valor : $valor;
            if ((string) ($asiento[$propiedad] ?? '') === (string) $nuevoValor) {
                unset($asiento);
                return;
            }

            $asiento[$propiedad] = $nuevoValor;
            unset($asiento);
            $this->marcarBorradorModificado();
            $etiqueta = $propiedad === 'tipo'
                ? 'tipo ' . strtolower($valor)
                : 'disponibilidad ' . ($valor === '1' ? 'activa' : 'inactiva');
            $this->dispatch('editor-mensaje', mensaje: 'Pincel aplicado al borrador: ' . $etiqueta . '. Guarda la distribución para persistir el cambio.');
            return;
        }

        unset($asiento);
        throw ValidationException::withMessages([
            'distribucion' => 'El asiento ya no está disponible en el borrador. Selecciona otra vez la distribución.',
        ]);
    }

    private function buscarAsiento(string $fila,int $numero):?array{foreach($this->borradorAsientos as $a)if($a['fila']===$fila&&(int)$a['numero']===$numero)return $a;return null;}

    public function guardarAsiento():void{$fila=strtoupper(trim($this->fila));$data=validator(['fila'=>$fila,'numero'=>$this->numero,'tipo'=>$this->tipo,'activo'=>$this->activo],['fila'=>['required','string','max:5','regex:/^[A-Z0-9]+$/'],'numero'=>['required','integer','min:1','max:65535'],'tipo'=>['required',Rule::in(['ESTANDAR','PREFERENCIAL','ACCESIBLE'])],'activo'=>['required',Rule::in(['0','1'])]])->validate();foreach($this->borradorAsientos as $i=>$a){if((int)$a['id']===(int)$this->asientoSeleccionadoId&&$this->asientoSeleccionadoId!==null)continue;if($a['fila']===$fila&&(int)$a['numero']===(int)$data['numero'])throw ValidationException::withMessages(['numero'=>'Ya existe un asiento en esa fila y número.']);}
        if($this->asientoSeleccionadoId!==null){$found=false;foreach($this->borradorAsientos as &$a)if((int)$a['id']===$this->asientoSeleccionadoId){$found=true;if($a['protegido']&&($a['fila']!==$fila||(int)$a['numero']!==(int)$data['numero']||$a['tipo']!==$data['tipo']))throw ValidationException::withMessages(['tipo'=>'Este asiento tiene historial. Solo puedes cambiar su disponibilidad.']);$a['fila']=$fila;$a['numero']=(int)$data['numero'];$a['tipo']=$data['tipo'];$a['activo']=(int)$data['activo'];break;}unset($a);if(!$found)throw ValidationException::withMessages(['asientoSeleccionadoId'=>'El asiento ya no está en el borrador.']);}
        else{$this->borradorAsientos[]=['id'=>$this->siguienteIdTemporal--,'fila'=>$fila,'numero'=>(int)$data['numero'],'tipo'=>$data['tipo'],'activo'=>(int)$data['activo'],'protegido'=>false];$this->asientoSeleccionadoId=null;}
        $this->fila=$fila;$this->numero=(int)$data['numero'];$this->marcarBorradorModificado();$this->dispatch('editor-mensaje',mensaje:'Cambios del asiento pendientes de guardar.');}

    public function eliminarAsiento():void{if($this->asientoSeleccionadoId===null)throw ValidationException::withMessages(['asientoSeleccionadoId'=>'Selecciona un asiento antes de eliminarlo.']);foreach($this->borradorAsientos as $i=>$a)if((int)$a['id']===$this->asientoSeleccionadoId){if($a['protegido'])throw ValidationException::withMessages(['asientoSeleccionadoId'=>'No puedes eliminar este asiento porque está vinculado al historial de funciones.']);array_splice($this->borradorAsientos,$i,1);break;}$this->asientoSeleccionadoId=null;$this->fila='A';$this->numero=1;$this->tipo='ESTANDAR';$this->activo='1';$this->marcarBorradorModificado();$this->dispatch('editor-mensaje',mensaje:'Eliminación pendiente de guardar.');}

    public function previsualizarCreacionMasiva():void{$d=$this->validarDatosMasivos();$fila=strtoupper(trim($d['filaMasiva']));$nums=range((int)$d['numeroInicioMasivo'],(int)$d['numeroFinMasivo']);$existing=collect($this->borradorAsientos)->where('fila',$fila)->pluck('numero')->map(fn($n)=>(int)$n)->all();$missing=array_values(array_filter($nums,fn($n)=>!in_array($n,$existing,true)));$this->vistaPreviaMasiva=['fila'=>$fila,'inicio'=>(int)$d['numeroInicioMasivo'],'fin'=>(int)$d['numeroFinMasivo'],'total'=>count($nums),'existentes'=>count($nums)-count($missing),'faltantes'=>count($missing),'numeros_faltantes'=>$missing,'tipo'=>$d['tipoMasivo'],'activo'=>(int)$d['activoMasivo']];}
    public function crearAsientosMasivos():void{$d=$this->validarDatosMasivos();$fila=strtoupper(trim($d['filaMasiva']));$nums=range((int)$d['numeroInicioMasivo'],(int)$d['numeroFinMasivo']);$exists=collect($this->borradorAsientos)->where('fila',$fila)->pluck('numero')->map(fn($n)=>(int)$n)->all();$count=0;foreach($nums as $n)if(!in_array($n,$exists,true)){$this->borradorAsientos[]=['id'=>$this->siguienteIdTemporal--,'fila'=>$fila,'numero'=>$n,'tipo'=>$d['tipoMasivo'],'activo'=>(int)$d['activoMasivo'],'protegido'=>false];$count++;}$this->vistaPreviaMasiva=[];$this->marcarBorradorModificado();$this->fila=$fila;$this->numero=(int)$d['numeroInicioMasivo'];$this->dispatch('editor-mensaje',mensaje:$count?"Se agregaron {$count} asiento(s) al borrador; guarda para persistirlos.":'No hay asientos faltantes en ese rango.');}

    public function activarSeleccionMultiple():void{$this->modoSeleccionMultiple=true;$this->asientosSeleccionados=[];$this->vistaPreviaEdicionMasiva=[];$this->resetValidation();}
    public function cancelarSeleccionMultiple():void{$this->modoSeleccionMultiple=false;$this->asientosSeleccionados=[];$this->vistaPreviaEdicionMasiva=[];$this->tipoEdicionMasiva='';$this->activoEdicionMasiva='';$this->resetValidation();}
    public function intercambiarPosicionesSeleccionadas(): void
    {
        $ids = $this->idsSeleccionadosValidos();
        if (count($ids) !== 2) {
            throw ValidationException::withMessages(['asientosSeleccionados' => 'Selecciona exactamente dos asientos para intercambiar sus posiciones.']);
        }

        $indices = [];
        foreach ($this->borradorAsientos as $index => $asiento) {
            if (in_array((int) $asiento['id'], $ids, true)) {
                $indices[(int) $asiento['id']] = $index;
            }
        }

        if (count($indices) !== 2 || min($ids) < 1) {
            throw ValidationException::withMessages(['asientosSeleccionados' => 'Solo puedes intercambiar dos asientos existentes en esta sala.']);
        }

        $primero = $indices[$ids[0]];
        $segundo = $indices[$ids[1]];
        if ($this->borradorAsientos[$primero]['protegido'] || $this->borradorAsientos[$segundo]['protegido']) {
            throw ValidationException::withMessages(['asientosSeleccionados' => 'No se pueden intercambiar asientos vinculados al historial de funciones.']);
        }

        $ubicacionPrimera = ['fila' => $this->borradorAsientos[$primero]['fila'], 'numero' => $this->borradorAsientos[$primero]['numero']];
        $this->borradorAsientos[$primero]['fila'] = $this->borradorAsientos[$segundo]['fila'];
        $this->borradorAsientos[$primero]['numero'] = $this->borradorAsientos[$segundo]['numero'];
        $this->borradorAsientos[$segundo]['fila'] = $ubicacionPrimera['fila'];
        $this->borradorAsientos[$segundo]['numero'] = $ubicacionPrimera['numero'];

        $this->cancelarSeleccionMultiple();
        $this->marcarBorradorModificado();
        $this->dispatch('editor-mensaje', mensaje: 'Posiciones intercambiadas en el borrador. Guarda la distribución para persistir el cambio.');
    }

    public function previsualizarEdicionMasiva():void{$d=$this->validarEdicionMasiva();$ids=$this->idsSeleccionadosValidos();if(!$ids)throw ValidationException::withMessages(['asientosSeleccionados'=>'Selecciona al menos un asiento existente.']);$seats=collect($this->borradorAsientos)->whereIn('id',$ids);$protected=$seats->where('protegido',true)->count();$this->vistaPreviaEdicionMasiva=['seleccionados'=>$seats->count(),'protegidos'=>$protected,'tipo'=>$d['tipoEdicionMasiva'],'activo'=>$d['activoEdicionMasiva'],'modificables_tipo'=>$d['tipoEdicionMasiva']!==''?$seats->where('protegido',false)->count():0,'tipo_bloqueado'=>$d['tipoEdicionMasiva']!==''?$protected:0,'afectados_estado'=>$d['activoEdicionMasiva']!==''?$seats->count():0];}
    public function aplicarEdicionMasiva():void{$d=$this->validarEdicionMasiva();$ids=$this->idsSeleccionadosValidos();if(!$ids)throw ValidationException::withMessages(['asientosSeleccionados'=>'Selecciona al menos un asiento existente.']);if($d['tipoEdicionMasiva']===''&&$d['activoEdicionMasiva']==='')throw ValidationException::withMessages(['tipoEdicionMasiva'=>'Elige al menos un cambio: tipo o disponibilidad.']);$updated=0;foreach($this->borradorAsientos as &$a)if(in_array((int)$a['id'],$ids,true)){if($d['activoEdicionMasiva']!=='')$a['activo']=(int)$d['activoEdicionMasiva'];if($d['tipoEdicionMasiva']!==''&&!$a['protegido'])$a['tipo']=$d['tipoEdicionMasiva'];$updated++;}unset($a);$this->cancelarSeleccionMultiple();$this->marcarBorradorModificado();$this->dispatch('editor-mensaje',mensaje:"Se actualizaron {$updated} asiento(s) en el borrador. Guarda para persistir los cambios.");}
    public function eliminarAsientosMasivos(): void
    {
        $ids = $this->idsSeleccionadosValidos();
        if ($ids === []) {
            throw ValidationException::withMessages(['asientosSeleccionados' => 'Selecciona al menos un asiento para eliminar.']);
        }

        $seleccionados = collect($this->borradorAsientos)->filter(fn ($asiento) => in_array((int) $asiento['id'], $ids, true));
        $protegidos = $seleccionados->filter(fn ($asiento) => (bool) ($asiento['protegido'] ?? false))->count();
        $eliminables = $seleccionados->filter(fn ($asiento) => ! (bool) ($asiento['protegido'] ?? false))->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($eliminables === []) {
            throw ValidationException::withMessages(['asientosSeleccionados' => 'Los asientos seleccionados están protegidos por el historial de funciones y no se pueden eliminar.']);
        }

        $this->borradorAsientos = array_values(array_filter(
            $this->borradorAsientos,
            fn ($asiento) => ! in_array((int) $asiento['id'], $eliminables, true)
        ));

        $eliminados = count($eliminables);
        $this->cancelarSeleccionMultiple();
        $this->asientoSeleccionadoId = null;
        $this->marcarBorradorModificado();
        $mensaje = "Se quitaron {$eliminados} asiento(s) del borrador.";
        if ($protegidos > 0) {
            $mensaje .= " {$protegidos} asiento(s) con historial se conservaron.";
        }
        $mensaje .= ' Guarda la distribución para persistir los cambios.';
        $this->dispatch('editor-mensaje', mensaje: $mensaje);
    }

    private function validarEdicionMasiva():array{return $this->validate(['tipoEdicionMasiva'=>['nullable','string',Rule::in(['','ESTANDAR','PREFERENCIAL','ACCESIBLE'])],'activoEdicionMasiva'=>['nullable','string',Rule::in(['','0','1'])]]);}
    private function idsSeleccionadosValidos():array{return array_values(array_unique(array_filter(array_map(fn($id)=>filter_var($id,FILTER_VALIDATE_INT)?(int)$id:null,$this->asientosSeleccionados),fn($id)=>$id!==null)));}
    private function validarDatosMasivos():array{$d=$this->validate(['filaMasiva'=>['required','string','max:5','regex:/^[A-Za-z0-9]+$/'],'numeroInicioMasivo'=>['required','integer','min:1','max:65535'],'numeroFinMasivo'=>['required','integer','gte:numeroInicioMasivo','max:65535'],'tipoMasivo'=>['required',Rule::in(['ESTANDAR','PREFERENCIAL','ACCESIBLE'])],'activoMasivo'=>['required',Rule::in(['0','1'])]],['filaMasiva.regex'=>'La fila solo puede contener letras y números.','numeroFinMasivo.gte'=>'El número final debe ser igual o mayor que el inicial.']);if((int)$d['numeroFinMasivo']-(int)$d['numeroInicioMasivo']>99)throw ValidationException::withMessages(['numeroFinMasivo'=>'Puedes generar como máximo 100 posiciones por operación.']);return $d;}
    public function render(){return view('livewire.admin.salas.editor-distribucion-sala');}
    private function validarUbicacion(string $fila,int $numero):void{validator(['fila'=>$fila,'numero'=>$numero],['fila'=>['required','string','max:5'],'numero'=>['required','integer','min:1','max:65535']])->validate();}
}
