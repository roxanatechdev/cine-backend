<div
    x-data="{
        selectedSeatIds: @js(array_fill_keys(array_map('intval', $asientosSeleccionados), true)),
        isSelected(id) {
            return this.selectedSeatIds[Number(id)] === true;
        },
        selectedIds() {
            return Object.keys(this.selectedSeatIds).filter(id => this.selectedSeatIds[id] === true).map(Number);
        },
        toggleSeat(id, element) {
            id = Number(id);
            const startedAt = performance.now();
            const wasSelected = this.isSelected(id);
            console.debug('[CINEPREMIUM selección] Clic recibido', {
                asientoId: id,
                accion: wasSelected ? 'deseleccionar' : 'seleccionar',
                seleccionAntes: Object.keys(this.selectedSeatIds).length,
                hora: new Date().toISOString()
            });

            if (wasSelected) delete this.selectedSeatIds[id];
            else this.selectedSeatIds[id] = true;

            console.debug('[CINEPREMIUM selección] Estado local actualizado', {
                asientoId: id,
                seleccionado: this.isSelected(id),
                totalSeleccionados: Object.keys(this.selectedSeatIds).length,
                tiempoEstadoMs: Number((performance.now() - startedAt).toFixed(2))
            });

            this.$nextTick(() => requestAnimationFrame(() => {
                console.debug('[CINEPREMIUM selección] Comprobación visual', {
                    asientoId: id,
                    claseBordeRojoPresente: element?.classList.contains('dist-asiento--seleccionado') ?? false,
                    seleccionadoEnAlpine: this.isSelected(id),
                    totalSeleccionados: Object.keys(this.selectedSeatIds).length,
                    tiempoHastaComprobacionMs: Number((performance.now() - startedAt).toFixed(2))
                });
            }));
        },
        async syncSelectionAndCall(action) {
            this.$wire.set('asientosSeleccionados', this.selectedIds(), true);
            await this.$wire.call(action);
        }
    }"
    x-init="$watch('$wire.modoSeleccionMultiple', value => { if (!value) selectedSeatIds = {}; })"
>
    @php
        $distribucion = $this->asientos;
        $seleccion = null;
        if ($asientoSeleccionadoId !== null) {
            foreach ($distribucion['asientos'] as $asientosFila) {
                foreach ($asientosFila as $asientoCelda) {
                    if ($asientoCelda['id'] === $asientoSeleccionadoId) { $seleccion = $asientoCelda; break 2; }
                }
            }
        }
        $tipoOptions = ['ESTANDAR' => 'Estándar', 'PREFERENCIAL' => 'Preferencial', 'ACCESIBLE' => 'Accesible'];
        $activoOptions = ['1' => 'Activo', '0' => 'Inactivo'];
    @endphp

    <div class="row g-3 mb-3" aria-label="Resumen de distribución">
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Asientos registrados</div><div class="h3 mb-0">{{ $distribucion['total'] }}</div></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Activos</div><div class="h3 mb-0">{{ $distribucion['activos'] }}</div></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Inactivos</div><div class="h3 mb-0">{{ $distribucion['inactivos'] }}</div></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Con historial</div><div class="h3 mb-0">{{ $distribucion['protegidos'] }}</div></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Pasillos</div><div class="h3 mb-0">{{ $distribucion['total_pasillos'] }}</div></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Capacidad informativa</div><div class="h3 mb-0">{{ $this->sala->capacidad ?? 'No especificada' }}</div></div></div>
    </div>

    <div class="panel rounded-3 p-3 mb-3" aria-labelledby="dist-validacion-titulo">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 id="dist-validacion-titulo" class="h6 mb-0">Validación de la distribución</h2>
            @if ($distribucion['advertencias'] === [])
                <span class="badge text-bg-success">Sin huecos internos detectados</span>
            @else
                <span class="badge text-bg-warning">{{ count($distribucion['advertencias']) }} advertencia(s)</span>
            @endif
        </div>
        @if ($distribucion['advertencias'] === [])
            <p class="small muted mb-0 mt-2">No se detectaron posiciones vacías entre el primer y el último asiento de cada fila. Esta revisión es informativa y no bloquea la edición.</p>
        @else
            <ul class="small mb-0 mt-2">
                @foreach ($distribucion['advertencias'] as $indice => $advertencia)
                    <li wire:key="advertencia-distribucion-{{ $advertencia['fila'] }}-{{ $indice }}">{{ $advertencia['mensaje'] }}</li>
                @endforeach
            </ul>
            <p class="small muted mb-0 mt-2">Los huecos pueden ser intencionales. Esta revisión no modifica ni renumera asientos.</p>
        @endif
    </div>

    <div class="panel rounded-3 p-3 p-md-4 mb-3">
        <h2 class="h5 mb-3">Crear asientos por fila</h2>
        <p class="small muted">Genera un rango de asientos de una sola vez. Las posiciones que ya existen se omiten y no se modifican.</p>
        <form wire:submit="previsualizarCreacionMasiva">
            <div class="row g-3">
                <div class="col-6 col-md-2">
                    <label for="dist-fila-masiva" class="form-label">Fila</label>
                    <input id="dist-fila-masiva" type="text" maxlength="5" class="form-control @error('filaMasiva') is-invalid @enderror" wire:model="filaMasiva" required>
                    @error('filaMasiva')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-2">
                    <label for="dist-inicio-masivo" class="form-label">Número inicial</label>
                    <input id="dist-inicio-masivo" type="number" min="1" max="65535" class="form-control @error('numeroInicioMasivo') is-invalid @enderror" wire:model="numeroInicioMasivo" required>
                    @error('numeroInicioMasivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-2">
                    <label for="dist-fin-masivo" class="form-label">Número final</label>
                    <input id="dist-fin-masivo" type="number" min="1" max="65535" class="form-control @error('numeroFinMasivo') is-invalid @enderror" wire:model="numeroFinMasivo" required>
                    @error('numeroFinMasivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-3">
                    <label for="dist-tipo-masivo" class="form-label">Tipo</label>
                    <select id="dist-tipo-masivo" class="form-select @error('tipoMasivo') is-invalid @enderror" wire:model="tipoMasivo" required>
                        @foreach ($tipoOptions as $valor => $etiqueta)<option value="{{ $valor }}">{{ $etiqueta }}</option>@endforeach
                    </select>
                    @error('tipoMasivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-3">
                    <label for="dist-activo-masivo" class="form-label">Disponibilidad</label>
                    <select id="dist-activo-masivo" class="form-select @error('activoMasivo') is-invalid @enderror" wire:model="activoMasivo" required>
                        @foreach ($activoOptions as $valor => $etiqueta)<option value="{{ $valor }}">{{ $etiqueta }}</option>@endforeach
                    </select>
                    @error('activoMasivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-outline-light" wire:loading.attr="disabled" wire:target="previsualizarCreacionMasiva">Previsualizar rango</button>
            </div>
        </form>
        @if ($vistaPreviaMasiva !== [])
            <div class="alert alert-info mt-3 mb-0" role="status">
                <strong>Vista previa: fila {{ $vistaPreviaMasiva['fila'] }}, números {{ $vistaPreviaMasiva['inicio'] }}–{{ $vistaPreviaMasiva['fin'] }}.</strong>
                Se crearán {{ $vistaPreviaMasiva['faltantes'] }} asiento(s); {{ $vistaPreviaMasiva['existentes'] }} posición(es) ya existen y se conservarán sin cambios.
                @if ($vistaPreviaMasiva['faltantes'] > 0)
                    <div class="mt-2">Números nuevos: {{ implode(', ', $vistaPreviaMasiva['numeros_faltantes']) }}</div>
                @endif
                <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-outline-secondary" wire:click="$set('vistaPreviaMasiva', [])">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="crearAsientosMasivos" wire:confirm="¿Crear los asientos faltantes de esta fila? Los asientos existentes no se modificarán." wire:loading.attr="disabled" wire:target="crearAsientosMasivos" @disabled($vistaPreviaMasiva['faltantes'] === 0)>Confirmar creación</button>
                </div>
            </div>
        @endif
    </div>

    @if (!$modoSeleccionMultiple)
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-outline-light" wire:click="activarSeleccionMultiple">Seleccionar varios asientos</button>
        </div>
    @else
        <div class="panel rounded-3 p-3 mb-3" aria-labelledby="dist-edicion-masiva-titulo">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h2 id="dist-edicion-masiva-titulo" class="h5 mb-0">Edición masiva de asientos</h2>
                <span class="small muted"><span x-text="Object.keys(selectedSeatIds).length">{{ count($asientosSeleccionados) }}</span> seleccionado(s)</span>
            </div>
            <p class="small muted">Haz clic en los asientos del plano para agregarlos o quitarlos de la selección. Los asientos con historial pueden cambiar de disponibilidad, pero no de tipo.</p>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="dist-tipo-edicion-masiva" class="form-label">Nuevo tipo (opcional)</label>
                    <select id="dist-tipo-edicion-masiva" class="form-select @error('tipoEdicionMasiva') is-invalid @enderror" wire:model="tipoEdicionMasiva">
                        <option value="">No cambiar el tipo</option>
                        @foreach ($tipoOptions as $valor => $etiqueta)<option value="{{ $valor }}">{{ $etiqueta }}</option>@endforeach
                    </select>
                    @error('tipoEdicionMasiva')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="dist-activo-edicion-masiva" class="form-label">Disponibilidad (opcional)</label>
                    <select id="dist-activo-edicion-masiva" class="form-select @error('activoEdicionMasiva') is-invalid @enderror" wire:model="activoEdicionMasiva">
                        <option value="">No cambiar la disponibilidad</option>
                        @foreach ($activoOptions as $valor => $etiqueta)<option value="{{ $valor }}">{{ $etiqueta }}</option>@endforeach
                    </select>
                    @error('activoEdicionMasiva')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            @error('asientosSeleccionados')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-outline-secondary" wire:click="cancelarSeleccionMultiple">Cancelar selección</button>
                <button type="button" class="btn btn-outline-light" x-on:click.prevent="syncSelectionAndCall('previsualizarEdicionMasiva')" wire:loading.attr="disabled" wire:target="previsualizarEdicionMasiva">Previsualizar cambios</button>
            </div>
            @if ($vistaPreviaEdicionMasiva !== [])
                <div class="alert alert-info mt-3 mb-0" role="status">
                    <strong>Vista previa:</strong> {{ $vistaPreviaEdicionMasiva['seleccionados'] }} asiento(s) seleccionados; {{ $vistaPreviaEdicionMasiva['protegidos'] }} con historial.
                    @if ($vistaPreviaEdicionMasiva['tipo'] !== '')
                        <div>{{ $vistaPreviaEdicionMasiva['modificables_tipo'] }} cambiarán de tipo; {{ $vistaPreviaEdicionMasiva['tipo_bloqueado'] }} conservarán su tipo por protección del historial.</div>
                    @endif
                    @if ($vistaPreviaEdicionMasiva['activo'] !== '')
                        <div>{{ $vistaPreviaEdicionMasiva['afectados_estado'] }} asiento(s) cambiarán a {{ $vistaPreviaEdicionMasiva['activo'] === '1' ? 'activo' : 'inactivo' }}.</div>
                    @endif
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-primary" x-on:click.prevent="if (window.confirm('¿Aplicar los cambios a los asientos seleccionados? Se respetarán las restricciones del historial.')) syncSelectionAndCall('aplicarEdicionMasiva')" wire:loading.attr="disabled" wire:target="aplicarEdicionMasiva">Confirmar cambios</button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="panel rounded-3 p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="h5 mb-0">Plano de asientos</h2>
            <div class="d-flex flex-wrap gap-3 small" aria-label="Leyenda de asientos">
                <span><span class="dist-leyenda dist-leyenda--activo"></span>Activo</span>
                <span><span class="dist-leyenda dist-leyenda--inactivo"></span>Inactivo</span>
                <span><span class="dist-leyenda dist-leyenda--protegido"></span>Con historial</span>
                <span><span class="dist-leyenda dist-leyenda--vacio"></span>Vacío</span>
                <span><span class="dist-leyenda dist-leyenda--pasillo"></span>Pasillo</span>
            </div>
        </div>

        <div class="dist-pantalla mb-4"><span class="material-symbols-outlined me-2" aria-hidden="true">movie</span>PANTALLA</div>
        <div class="dist-grid-wrap">
            <table class="dist-grid" aria-label="Distribución visual de asientos de {{ $this->sala->nombre }}">
                <thead><tr><th scope="col" class="dist-fila-label">Fila</th>
                    @foreach ($distribucion['columnas'] as $columna)
                        <th scope="col">{{ $columna }}</th>
                        @if (!$loop->last)<th scope="col" class="dist-pasillo-header" aria-label="Pasillo después de {{ $columna }}"></th>@endif
                    @endforeach
                </tr></thead>
                <tbody>
                    @foreach ($distribucion['filas'] as $filaPlano)
                    <tr wire:key="fila-{{ $filaPlano }}">
                        <th scope="row" class="dist-fila-label">{{ $filaPlano }}</th>
                        @foreach ($distribucion['columnas'] as $columna)
                            @php $celda = $distribucion['asientos'][$filaPlano][$columna] ?? null; @endphp
                            <td>
                                <button type="button"
                                    class="dist-asiento {{ $celda ? ($celda['activo'] ? 'dist-asiento--activo' : 'dist-asiento--inactivo') : 'dist-asiento--vacio' }} {{ $celda && $celda['protegido'] ? 'dist-asiento--protegido' : '' }} {{ !$modoSeleccionMultiple && $asientoSeleccionadoId === ($celda['id'] ?? null) ? 'dist-asiento--seleccionado' : '' }} "
                                    @if ($modoSeleccionMultiple)
                                        @if ($celda)
                                            x-on:click.prevent.stop="toggleSeat({{ (int) $celda['id'] }}, $el)"
                                            x-bind:class="{ 'dist-asiento--seleccionado': isSelected({{ (int) $celda['id'] }}) }"
                                        @endif
                                    @else
                                        wire:click="seleccionarCelda('{{ $filaPlano }}', {{ $columna }})"
                                    @endif
                                    wire:key="celda-{{ $filaPlano }}-{{ $columna }}"
                                    aria-label="{{ $celda ? 'Asiento ' . $filaPlano . '-' . $columna . ($celda['protegido'] ? ', protegido por historial' : '') : 'Agregar asiento ' . $filaPlano . '-' . $columna }}"
                                    title="{{ $celda ? $filaPlano . '-' . $columna . ' · ' . ucfirst(strtolower($celda['tipo'])) . ($celda['protegido'] ? ' · Con historial' : '') : 'Agregar asiento ' . $filaPlano . '-' . $columna }}">
                                    @if ($celda)
                                        @if ($celda['protegido'])<span class="material-symbols-outlined dist-lock" aria-hidden="true">lock</span>@endif
                                        {{ $columna }}
                                    @else
                                        <span class="material-symbols-outlined" aria-hidden="true">add</span>
                                    @endif
                                </button>
                            </td>
                            @if (!$loop->last)
                                @php $tienePasillo = $distribucion['pasillos'][$filaPlano][$columna] ?? false; @endphp
                                <td class="dist-pasillo-cell {{ $tienePasillo ? 'dist-pasillo-cell--activo' : '' }}">
                                    <button type="button"
                                        class="dist-pasillo"
                                        wire:click="alternarPasillo('{{ $filaPlano }}', {{ $columna }})"
                                        @if ($tienePasillo) wire:confirm="¿Quitar el pasillo entre {{ $filaPlano }}-{{ $columna }} y {{ $filaPlano }}-{{ $columna + 1 }}?" @endif
                                        wire:key="pasillo-{{ $filaPlano }}-{{ $columna }}"
                                        aria-label="{{ $tienePasillo ? 'Quitar' : 'Agregar' }} pasillo entre {{ $filaPlano }}-{{ $columna }} y {{ $filaPlano }}-{{ $columna + 1 }}"
                                        title="{{ $tienePasillo ? 'Quitar pasillo' : 'Agregar pasillo' }}">
                                        @if ($tienePasillo)<span aria-hidden="true">↔</span>@else<span aria-hidden="true">·</span>@endif
                                    </button>
                                </td>
                            @endif
                        @endforeach
                    </tr>
                    @if (!$loop->last)
                        @php
                            $filaSiguiente = $distribucion['filas'][$loop->index + 1];
                            $tienePasilloTransversal = $distribucion['pasillos_transversales'][$filaPlano][$filaSiguiente] ?? false;
                        @endphp
                        <tr wire:key="separador-transversal-{{ $filaPlano }}-{{ $filaSiguiente }}" class="dist-pasillo-transversal-row">
                            <td colspan="{{ count($distribucion['columnas']) * 2 }}" class="text-center py-1">
                                <button type="button"
                                    class="dist-pasillo dist-pasillo-transversal {{ $tienePasilloTransversal ? 'dist-pasillo-cell--activo' : '' }}"
                                    wire:click="alternarPasilloTransversal('{{ $filaPlano }}', '{{ $filaSiguiente }}')"
                                    @if ($tienePasilloTransversal) wire:confirm="¿Quitar el pasillo transversal entre las filas {{ $filaPlano }} y {{ $filaSiguiente }}?" @endif
                                    aria-label="{{ $tienePasilloTransversal ? 'Quitar' : 'Agregar' }} pasillo transversal entre las filas {{ $filaPlano }} y {{ $filaSiguiente }}"
                                    title="{{ $tienePasilloTransversal ? 'Quitar pasillo transversal' : 'Agregar pasillo transversal' }}">
                                    {{ $tienePasilloTransversal ? '↕ Pasillo transversal' : '· Agregar pasillo transversal' }}
                                </button>
                            </td>
                        </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($distribucion['fuera_de_rango'] > 0)
            <div class="alert alert-info mt-3 mb-0" role="status">Hay {{ $distribucion['fuera_de_rango'] }} asiento(s) con número superior a 30. Se conservan en los datos; esta vista muestra hasta la columna 30 para mantener el plano manejable.</div>
        @endif
        <p class="small muted mt-3 mb-0">Usa los separadores entre números para los pasillos longitudinales y los separadores entre filas para los transversales. Los pasillos se guardan sin renumerar ni modificar los asientos. Los asientos con candado están vinculados a funciones y conservan su identidad histórica. La selección de una celda vacía permite crear un asiento nuevo.</p>
    </div>

    <div class="panel rounded-3 p-3 p-md-4">
        <h2 class="h5 mb-3">{{ $seleccion ? 'Asiento ' . $seleccion['fila'] . '-' . $seleccion['numero'] : 'Nueva ubicación' }}</h2>
        @if ($seleccion && $seleccion['protegido'])
            <div class="alert alert-warning" role="status">Este asiento tiene historial de funciones. No se puede cambiar su tipo ni su ubicación, ni eliminarlo; solo su disponibilidad administrativa.</div>
        @elseif ($seleccion)
            <div class="alert alert-info" role="status">Este asiento no tiene historial asociado: puedes cambiar su fila, número, tipo o disponibilidad, o eliminarlo.</div>
        @endif
        <form wire:submit="guardarAsiento">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label for="dist-fila" class="form-label">Fila</label>
                    <input id="dist-fila" type="text" maxlength="5" class="form-control @error('fila') is-invalid @enderror" wire:model="fila" @disabled($seleccion && $seleccion['protegido']) required>
                    @error('fila')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-3">
                    <label for="dist-numero" class="form-label">Número</label>
                    <input id="dist-numero" type="number" min="1" max="65535" class="form-control @error('numero') is-invalid @enderror" wire:model="numero" @disabled($seleccion && $seleccion['protegido']) required>
                    @error('numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-3">
                    <label for="dist-tipo" class="form-label">Tipo de asiento</label>
                    <select id="dist-tipo" class="form-select @error('tipo') is-invalid @enderror" wire:model="tipo" @disabled($seleccion && $seleccion['protegido']) required>
                        @foreach ($tipoOptions as $valor => $etiqueta)<option value="{{ $valor }}">{{ $etiqueta }}</option>@endforeach
                    </select>
                    @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-3">
                    <label for="dist-activo" class="form-label">Disponibilidad</label>
                    <select id="dist-activo" class="form-select @error('activo') is-invalid @enderror" wire:model="activo" required>
                        @foreach ($activoOptions as $valor => $etiqueta)<option value="{{ $valor }}">{{ $etiqueta }}</option>@endforeach
                    </select>
                    @error('activo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                @if ($seleccion)
                    <a href="{{ route('admin.asientos.edit', $seleccion['id']) }}" class="btn btn-outline-light">Ver ficha completa</a>
                    @if (!$seleccion['protegido'])
                        <button type="button" class="btn btn-outline-danger" wire:click="eliminarAsiento" wire:confirm="¿Eliminar el asiento {{ $seleccion['fila'] }}-{{ $seleccion['numero'] }}? Esta acción no se puede deshacer." wire:loading.attr="disabled" wire:target="eliminarAsiento">Eliminar asiento</button>
                    @endif
                @endif
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="guardarAsiento">
                    <span wire:loading.remove wire:target="guardarAsiento">{{ $seleccion ? 'Guardar cambios' : 'Agregar asiento' }}</span>
                    <span wire:loading wire:target="guardarAsiento">Guardando…</span>
                </button>
            </div>
            @error('asientoSeleccionadoId')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </form>
        <div class="small muted mt-3" role="status" aria-live="polite" x-data="{mensaje: ''}" x-on:distribucion-guardada.window="mensaje = $event.detail.mensaje; setTimeout(() => mensaje = '', 3500)" x-text="mensaje"></div>
    </div>

</div>
