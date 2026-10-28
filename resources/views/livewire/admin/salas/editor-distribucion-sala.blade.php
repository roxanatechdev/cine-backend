<div
    class="dist-editor-shell"
    x-data="{
        zoomLevel: 100,
        minZoom: 50,
        maxZoom: 150,
        panMode: false,
        activeTool: 'select',
        brushType: 'ESTANDAR',
        brushAvailability: '1',
        toolMessage: 'Herramienta Seleccionar activa. Elige un asiento para ver o editar sus detalles.',
        activateTool(tool) {
            this.activeTool = tool;
            this.panMode = tool === 'pan';
            this.panPointerId = null;
            this.panMoved = false;
            const messages = {
                select: 'Herramienta Seleccionar activa. Toca un asiento para editarlo.',
                'brush-type': 'Pincel de tipo activo: ' + ({ESTANDAR: 'Estándar', PREFERENCIAL: 'Preferencial', ACCESIBLE: 'Accesible'}[this.brushType] || this.brushType) + '. Toca los asientos que quieras pintar.',
                'brush-availability': 'Pincel de disponibilidad activo: ' + (this.brushAvailability === '1' ? 'Activo' : 'Inactivo') + '. Toca los asientos que quieras pintar.',
                pan: 'Mover pizarra activo. Arrastra para desplazarte; elige otra herramienta para editar asientos.'
            };
            this.toolMessage = messages[tool] || messages.select;
        },
        chooseTypeBrush(type) { this.brushType = type; this.activateTool('brush-type'); },
        chooseAvailabilityBrush(value) { this.brushAvailability = value; this.activateTool('brush-availability'); },
        async handleCellClick(fila, numero, id, protectedSeat = false) {
            if (this.panMode || this.pinchGesture || this.pinchMoved) return;
            if (this.activeTool === 'brush-type') {
                if (protectedSeat) { this.toolMessage = 'Asiento protegido por historial: su tipo no se puede cambiar. Puedes usar el pincel de disponibilidad.'; return; }
                if (!id) { this.toolMessage = 'El pincel solo pinta asientos existentes. Usa Seleccionar para crear uno en una celda vacía.'; return; }
                try { await this.$wire.pintarAsiento(Number(id), 'tipo', this.brushType); this.toolMessage = 'Pincel aplicado: ' + this.brushType.toLowerCase() + '. Puedes seguir pintando.'; }
                catch (error) { this.toolMessage = 'No se pudo pintar este asiento. Revisa el mensaje de validación.'; }
                return;
            }
            if (this.activeTool === 'brush-availability') {
                if (!id) { this.toolMessage = 'El pincel solo pinta asientos existentes. Usa Seleccionar para crear uno en una celda vacía.'; return; }
                try { await this.$wire.pintarAsiento(Number(id), 'activo', this.brushAvailability); this.toolMessage = 'Disponibilidad aplicada: ' + (this.brushAvailability === '1' ? 'activo' : 'inactivo') + '. Puedes seguir pintando.'; }
                catch (error) { this.toolMessage = 'No se pudo pintar este asiento. Revisa el mensaje de validación.'; }
                return;
            }
            if (this.activeTool !== 'select') return;
            if (this.$wire.modoSeleccionMultiple && id) { this.toggleSeat(Number(id)); return; }
            await this.$wire.seleccionarCelda(fila, Number(numero));
        },
        panPointerId: null,
        panStartX: 0,
        panStartY: 0,
        panScrollLeft: 0,
        panScrollTop: 0,
        panMoved: false,
        activePointers: {},
        pinchStartDistance: 0,
        pinchStartZoom: 100,
        pinchCenterX: 0,
        pinchCenterY: 0,
        pinchGesture: false,
        pinchMoved: false,
        changeZoom(delta) { this.zoomLevel = Math.max(this.minZoom, Math.min(this.maxZoom, this.zoomLevel + delta)); },
        resetZoom() { this.zoomLevel = 100; },
        togglePanMode() {
            this.panMode = !this.panMode;
            this.panPointerId = null;
            this.panMoved = false;
        },
        startPan(event) {
            if (!this.$refs.boardViewport) return;
            this.activePointers[event.pointerId] = { x: event.clientX, y: event.clientY };
            const pointers = Object.values(this.activePointers);
            if (pointers.length === 2) {
                const dx = pointers[1].x - pointers[0].x;
                const dy = pointers[1].y - pointers[0].y;
                this.pinchStartDistance = Math.max(1, Math.hypot(dx, dy));
                this.pinchStartZoom = this.zoomLevel;
                const rect = this.$refs.boardViewport.getBoundingClientRect();
                this.pinchCenterX = ((pointers[0].x + pointers[1].x) / 2) - rect.left;
                this.pinchCenterY = ((pointers[0].y + pointers[1].y) / 2) - rect.top;
                this.pinchGesture = true;
                this.pinchMoved = false;
                this.panPointerId = null;
                return;
            }
            if (!this.panMode || event.button !== 0) return;
            const viewport = this.$refs.boardViewport;
            this.panPointerId = event.pointerId;
            this.panStartX = event.clientX;
            this.panStartY = event.clientY;
            this.panScrollLeft = viewport.scrollLeft;
            this.panScrollTop = viewport.scrollTop;
            this.panMoved = false;
            if (viewport.setPointerCapture) viewport.setPointerCapture(event.pointerId);
        },
        movePan(event) {
            if (!this.$refs.boardViewport) return;
            if (this.activePointers[event.pointerId]) {
                this.activePointers[event.pointerId] = { x: event.clientX, y: event.clientY };
                const pointers = Object.values(this.activePointers);
                if (pointers.length >= 2 && this.pinchGesture) {
                    const dx = pointers[1].x - pointers[0].x;
                    const dy = pointers[1].y - pointers[0].y;
                    const distance = Math.max(1, Math.hypot(dx, dy));
                    const nextZoom = Math.max(this.minZoom, Math.min(this.maxZoom, Math.round(this.pinchStartZoom * distance / this.pinchStartDistance)));
                    const viewport = this.$refs.boardViewport;
                    const oldScale = this.zoomLevel / 100;
                    const focalX = (viewport.scrollLeft + this.pinchCenterX) / oldScale;
                    const focalY = (viewport.scrollTop + this.pinchCenterY) / oldScale;
                    this.zoomLevel = nextZoom;
                    const newScale = nextZoom / 100;
                    this.$nextTick(() => {
                        viewport.scrollLeft = Math.max(0, focalX * newScale - this.pinchCenterX);
                        viewport.scrollTop = Math.max(0, focalY * newScale - this.pinchCenterY);
                    });
                    if (Math.abs(nextZoom - this.pinchStartZoom) >= 1) this.pinchMoved = true;
                    event.preventDefault();
                    return;
                }
            }
            if (!this.panMode || this.panPointerId !== event.pointerId) return;
            const dx = event.clientX - this.panStartX;
            const dy = event.clientY - this.panStartY;
            if (Math.abs(dx) > 3 || Math.abs(dy) > 3) this.panMoved = true;
            this.$refs.boardViewport.scrollLeft = this.panScrollLeft - dx;
            this.$refs.boardViewport.scrollTop = this.panScrollTop - dy;
            if (this.panMoved) event.preventDefault();
        },
        endPan(event) {
            delete this.activePointers[event.pointerId];
            if (this.panPointerId === event.pointerId) {
                this.panPointerId = null;
                if (this.panMoved) setTimeout(() => { this.panMoved = false; }, 0);
            }
            if (Object.keys(this.activePointers).length < 2 && this.pinchGesture) {
                setTimeout(() => {
                    this.pinchGesture = false;
                    this.pinchMoved = false;
                }, 0);
            }
            if (this.$refs.boardViewport?.hasPointerCapture?.(event.pointerId)) this.$refs.boardViewport.releasePointerCapture(event.pointerId);
        },
        blockPanClick(event) {
            if (!this.panMode && !this.pinchGesture && !this.pinchMoved) return;
            event.preventDefault();
            event.stopPropagation();
        },
        fitBoard() {
            const wrap = this.$refs.boardViewport;
            const table = this.$refs.boardTable;
            if (!wrap || !table) return;
            const currentScale = this.zoomLevel / 100;
            const naturalWidth = table.getBoundingClientRect().width / currentScale;
            const availableWidth = Math.max(1, wrap.clientWidth - 24);
            this.zoomLevel = Math.max(this.minZoom, Math.min(100, Math.floor((availableWidth / naturalWidth) * 100)));
        },
        selectedSeatIds: @js(array_fill_keys(array_map('intval', $asientosSeleccionados), true)),
        isSelected(id) {
            return this.selectedSeatIds[Number(id)] === true;
        },
        selectedIds() {
            return Object.keys(this.selectedSeatIds).filter(id => this.selectedSeatIds[id] === true).map(Number);
        },
        toggleSeat(id, element = null) {
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

    <div class="panel rounded-3 p-3 mb-3 dist-save-panel" aria-label="Guardar o cancelar distribución">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="fw-semibold" role="status" aria-live="polite" aria-atomic="true">{{ $hayCambiosPendientes ? 'Hay cambios pendientes' : 'Distribución guardada' }}</div>
                <div class="small muted">{{ $hayCambiosPendientes ? 'Los cambios solo están en el borrador del editor. Aún no se han escrito en la base de datos.' : 'No hay cambios pendientes de guardar.' }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" wire:click="cancelarDistribucion" @disabled(!$hayCambiosPendientes) @if($hayCambiosPendientes) wire:confirm="¿Descartar todos los cambios pendientes y recuperar la distribución guardada?" @endif wire:loading.attr="disabled" wire:target="cancelarDistribucion,guardarDistribucion">Cancelar cambios</button>
                <button type="button" class="btn btn-primary" wire:click="guardarDistribucion" @disabled(!$hayCambiosPendientes) wire:loading.attr="disabled" wire:target="guardarDistribucion,cancelarDistribucion">
                    <span wire:loading.remove wire:target="guardarDistribucion">Guardar distribución</span>
                    <span wire:loading wire:target="guardarDistribucion">Aplicando…</span>
                </button>
            </div>
        </div>
        @error('distribucion')<div class="alert alert-danger mt-3 mb-0" role="alert">{{ $message }}</div>@enderror
    </div>

    <div class="dist-feedback mb-3" role="status" aria-live="polite" aria-atomic="true"
         x-data="{ mensaje: '', tipo: 'info', temporizador: null }"
         x-on:editor-mensaje.window="mensaje = $event.detail.mensaje; tipo = $event.detail.tipo || 'info'; if (temporizador) clearTimeout(temporizador); temporizador = setTimeout(() => mensaje = '', 4500)"
         x-bind:class="mensaje ? 'dist-feedback--visible dist-feedback--' + tipo : ''"
         x-cloak>
        <span class="dist-feedback__icon" aria-hidden="true" x-text="tipo === 'success' ? '✓' : (tipo === 'warning' ? '!' : 'i')"></span>
        <span x-text="mensaje"></span>
        <button type="button" class="dist-feedback__close" x-on:click="mensaje = ''" aria-label="Cerrar mensaje">×</button>
    </div>

    <div class="dist-workspace" aria-label="Área de trabajo de distribución">
        <aside class="dist-workspace__tools" aria-label="Herramientas y creación">
    <details class="panel rounded-3 mb-3 dist-section dist-create-panel dist-foldable-panel">
        <summary class="dist-foldable-heading">
            <span class="h5 mb-0">Crear asientos por fila</span>
            <span class="small muted">Crear un rango sin modificar posiciones existentes</span>
        </summary>
        <div class="dist-foldable-content p-3 pt-0">

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
                    <button type="button" class="btn btn-primary" wire:click="crearAsientosMasivos" wire:loading.attr="disabled" wire:target="crearAsientosMasivos" @disabled($vistaPreviaMasiva['faltantes'] === 0)>Crear en el borrador</button>
                </div>
            </div>
        @endif
        </div>
    </details>

    <section class="dist-toolstrip mb-3" aria-label="Herramientas de selección y pinceles">
        <div class="dist-toolstrip__intro">
            <div>
                <h2 class="h6 mb-1">Herramientas de la pizarra</h2>
                <p class="small muted mb-0">Selecciona una herramienta; los pinceles permanecen activos hasta que elijas otra.</p>
            </div>
            <span class="badge" x-bind:class="activeTool === 'select' ? 'text-bg-secondary' : (activeTool === 'pan' ? 'text-bg-info' : 'text-bg-warning')" x-text="({select:'Selección', 'brush-type':'Pincel de tipo', 'brush-availability':'Pincel de disponibilidad', pan:'Mover pizarra'})[activeTool]">Selección</span>
        </div>
        <div class="dist-toolstrip__actions dist-toolstrip__tools" role="toolbar" aria-label="Herramientas de la pizarra">
            <button type="button" class="btn btn-outline-light" x-on:click="activateTool('select')" x-bind:class="activeTool === 'select' ? 'dist-tool-active' : ''" x-bind:aria-pressed="(activeTool === 'select').toString()" aria-label="Seleccionar o editar asientos">↖ Seleccionar</button>
            <button type="button" class="btn btn-outline-light" x-on:click="chooseTypeBrush('ESTANDAR')" x-bind:class="activeTool === 'brush-type' && brushType === 'ESTANDAR' ? 'dist-tool-active' : ''" x-bind:aria-pressed="(activeTool === 'brush-type' && brushType === 'ESTANDAR').toString()">▧ Pincel estándar</button>
            <button type="button" class="btn btn-outline-light" x-on:click="chooseTypeBrush('PREFERENCIAL')" x-bind:class="activeTool === 'brush-type' && brushType === 'PREFERENCIAL' ? 'dist-tool-active' : ''" x-bind:aria-pressed="(activeTool === 'brush-type' && brushType === 'PREFERENCIAL').toString()">▧ Pincel preferencial</button>
            <button type="button" class="btn btn-outline-light" x-on:click="chooseTypeBrush('ACCESIBLE')" x-bind:class="activeTool === 'brush-type' && brushType === 'ACCESIBLE' ? 'dist-tool-active' : ''" x-bind:aria-pressed="(activeTool === 'brush-type' && brushType === 'ACCESIBLE').toString()">♿ Pincel accesible</button>
            <button type="button" class="btn btn-outline-light" x-on:click="chooseAvailabilityBrush('1')" x-bind:class="activeTool === 'brush-availability' && brushAvailability === '1' ? 'dist-tool-active' : ''" x-bind:aria-pressed="(activeTool === 'brush-availability' && brushAvailability === '1').toString()">● Pintar activos</button>
            <button type="button" class="btn btn-outline-light" x-on:click="chooseAvailabilityBrush('0')" x-bind:class="activeTool === 'brush-availability' && brushAvailability === '0' ? 'dist-tool-active' : ''" x-bind:aria-pressed="(activeTool === 'brush-availability' && brushAvailability === '0').toString()">○ Pintar inactivos</button>
            <button type="button" class="btn btn-outline-light" x-on:click="activateTool('pan')" x-bind:class="activeTool === 'pan' ? 'dist-tool-active' : ''" x-bind:aria-pressed="(activeTool === 'pan').toString()">✥ Mover pizarra</button>
            @if (!$modoSeleccionMultiple)
                <button type="button" class="btn btn-outline-light" wire:click="activarSeleccionMultiple" wire:loading.attr="disabled" wire:target="activarSeleccionMultiple">▦ Selección múltiple</button>
            @else
                <button type="button" class="btn btn-warning" wire:click="cancelarSeleccionMultiple" wire:loading.attr="disabled" wire:target="cancelarSeleccionMultiple">Salir de selección múltiple</button>
                <span class="dist-toolstrip__count" aria-live="polite"><strong x-text="selectedIds().length">{{ count($asientosSeleccionados) }}</strong> seleccionado(s)</span>
            @endif
        </div>
        <p class="dist-tool-message small mb-0" role="status" aria-live="polite" aria-atomic="true" x-text="toolMessage">Herramienta Seleccionar activa. Elige un asiento para ver o editar sus detalles.</p>
    </section>

    @if ($modoSeleccionMultiple)
        <div class="panel rounded-3 p-3 mb-3 dist-section dist-bulk-panel" x-ref="bulkPanel" aria-labelledby="dist-edicion-masiva-titulo">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h2 id="dist-edicion-masiva-titulo" class="h5 mb-0">Edición masiva de asientos</h2>
                <span class="small muted">Selecciona asientos en el plano y aplica una acción al conjunto.</span>
            </div>
            <p class="small muted">Haz clic en los asientos del plano para agregarlos o quitarlos de la selección. Los asientos con historial pueden cambiar de disponibilidad, pero no de tipo ni eliminarse. Las eliminaciones afectan solo al borrador hasta que guardes la distribución.</p>
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
                <button type="button" class="btn btn-outline-light" x-on:click.prevent="syncSelectionAndCall('intercambiarPosicionesSeleccionadas')" wire:loading.attr="disabled" wire:target="intercambiarPosicionesSeleccionadas" x-bind:disabled="selectedIds().length !== 2">Intercambiar posiciones</button>
                <button type="button" class="btn btn-outline-danger" x-on:click.prevent="syncSelectionAndCall('eliminarAsientosMasivos')" wire:loading.attr="disabled" wire:target="eliminarAsientosMasivos">Eliminar seleccionados del borrador</button>
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
                        <button type="button" class="btn btn-primary" x-on:click.prevent="syncSelectionAndCall('aplicarEdicionMasiva')" wire:loading.attr="disabled" wire:target="aplicarEdicionMasiva">Aplicar al borrador</button>
                    </div>
                </div>
            @endif
        </div>
    @endif

        </aside>
        <main class="dist-workspace__canvas" aria-label="Pizarra de asientos">
    {{-- Barra contextual: acciones rápidas para la selección actual. --}}
    @if ($modoSeleccionMultiple)
        <section class="dist-contextbar mb-3" aria-label="Acciones contextuales de la selección múltiple" aria-live="polite">
            <div class="dist-contextbar__identity">
                <span class="dist-contextbar__icon" aria-hidden="true">▦</span>
                <div>
                    <h2 class="h6 mb-1">Acciones de la selección</h2>
                    <p class="small muted mb-0"><strong x-text="selectedIds().length">{{ count($asientosSeleccionados) }}</strong> asiento(s) seleccionado(s). Las acciones se aplican al borrador.</p>
                </div>
            </div>
            <div class="dist-contextbar__actions">
                <button type="button" class="btn btn-outline-light" x-on:click="$refs.bulkPanel?.scrollIntoView({behavior: 'smooth', block: 'center'})" aria-controls="dist-edicion-masiva-titulo">Editar selección</button>
                <button type="button" class="btn btn-outline-light" x-on:click.prevent="syncSelectionAndCall('intercambiarPosicionesSeleccionadas')" x-bind:disabled="selectedIds().length !== 2" wire:loading.attr="disabled" wire:target="intercambiarPosicionesSeleccionadas">Intercambiar posiciones</button>
                <button type="button" class="btn btn-outline-secondary" wire:click="cancelarSeleccionMultiple" wire:loading.attr="disabled" wire:target="cancelarSeleccionMultiple">Terminar selección</button>
            </div>
        </section>
    @elseif ($seleccion)
        <section class="dist-contextbar mb-3" aria-label="Acciones contextuales del asiento seleccionado">
            <div class="dist-contextbar__identity">
                <span class="dist-contextbar__icon" aria-hidden="true">{{ $seleccion['protegido'] ? '🔒' : '▣' }}</span>
                <div>
                    <h2 class="h6 mb-1">Asiento {{ $seleccion['fila'] }}-{{ $seleccion['numero'] }}</h2>
                    <p class="small muted mb-0">{{ ucfirst(strtolower($seleccion['tipo'])) }} · {{ $seleccion['activo'] ? 'Activo' : 'Inactivo' }}{{ $seleccion['protegido'] ? ' · Protegido por historial' : '' }} · Cambios pendientes hasta guardar.</p>
                </div>
            </div>
            <div class="dist-contextbar__actions">
                <span class="dist-contextbar__label">Tipo</span>
                @foreach ($tipoOptions as $valor => $etiqueta)
                    <button type="button" class="btn btn-sm {{ $seleccion['tipo'] === $valor ? 'btn-primary' : 'btn-outline-light' }}" wire:click="pintarAsiento({{ (int) $seleccion['id'] }}, 'tipo', '{{ $valor }}')" @disabled($seleccion['protegido'] || $seleccion['tipo'] === $valor) wire:loading.attr="disabled" wire:target="pintarAsiento" aria-label="Cambiar asiento {{ $seleccion['fila'] }}-{{ $seleccion['numero'] }} a tipo {{ $etiqueta }}">{{ $etiqueta }}</button>
                @endforeach
                <span class="dist-contextbar__divider" aria-hidden="true"></span>
                <span class="dist-contextbar__label">Disponibilidad</span>
                <button type="button" class="btn btn-sm {{ $seleccion['activo'] ? 'btn-primary' : 'btn-outline-light' }}" wire:click="pintarAsiento({{ (int) $seleccion['id'] }}, 'activo', '1')" @disabled((bool) $seleccion['activo']) wire:loading.attr="disabled" wire:target="pintarAsiento">Activo</button>
                <button type="button" class="btn btn-sm {{ !$seleccion['activo'] ? 'btn-primary' : 'btn-outline-light' }}" wire:click="pintarAsiento({{ (int) $seleccion['id'] }}, 'activo', '0')" @disabled(!(bool) $seleccion['activo']) wire:loading.attr="disabled" wire:target="pintarAsiento">Inactivo</button>
                <button type="button" class="btn btn-outline-light btn-sm" x-on:click="$refs.detailsPanel?.scrollIntoView({behavior: 'smooth', block: 'center'}); document.getElementById('dist-fila')?.focus({preventScroll: true})">Más detalles</button>
            </div>
        </section>
    @endif

    <div class="panel rounded-3 p-3 p-md-4 mb-3 dist-board-panel">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 dist-board-heading">
            <h2 class="h5 mb-0">Plano de asientos</h2>
            <div class="d-flex flex-wrap gap-3 small" aria-label="Leyenda de asientos">
                <span><span class="dist-leyenda dist-leyenda--activo"></span>Activo</span>
                <span><span class="dist-leyenda dist-leyenda--inactivo"></span>Inactivo</span>
                <span><span class="dist-leyenda dist-leyenda--protegido"></span>Con historial</span>
                <span><span class="dist-leyenda dist-leyenda--vacio"></span>Vacío</span>
                <span><span class="dist-leyenda dist-leyenda--pasillo"></span>Pasillo</span>
            </div>
        </div>

        <div class="dist-pantalla mb-3"><span class="material-symbols-outlined me-2" aria-hidden="true">movie</span>PANTALLA</div>
        <div class="dist-board-controls" role="group" aria-label="Controles de visualización del plano">
            <div class="dist-board-controls__zoom">
                <button type="button" class="btn btn-outline-light btn-sm" x-on:click="changeZoom(-10)" x-bind:disabled="zoomLevel <= minZoom" aria-label="Alejar plano" title="Alejar">−</button>
                <output class="dist-zoom-value" aria-live="polite" aria-atomic="true" aria-label="Nivel de zoom" x-text="zoomLevel + '%'">100%</output>
                <button type="button" class="btn btn-outline-light btn-sm" x-on:click="changeZoom(10)" x-bind:disabled="zoomLevel >= maxZoom" aria-label="Acercar plano" title="Acercar">+</button>
            </div>
            <div class="dist-board-controls__actions">
                <button type="button" class="btn btn-outline-light btn-sm" x-on:click="fitBoard()">Ajustar sala</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" x-on:click="resetZoom()">Restablecer 100%</button>
                <button type="button" class="btn btn-outline-light btn-sm" x-on:click="activateTool(activeTool === 'pan' ? 'select' : 'pan')" x-bind:aria-pressed="panMode.toString()" x-bind:class="panMode ? 'dist-pan-active' : ''" aria-label="Activar o desactivar mover el plano" x-text="panMode ? 'Terminar movimiento' : 'Mover plano'">Mover plano</button>
            </div>
        </div>
        <p class="dist-interaction-status small muted mb-2" role="status" aria-live="polite" aria-atomic="true"
           x-text="toolMessage">
            Herramienta Seleccionar activa. Elige un asiento para ver o editar sus detalles.
        </p>
        <div class="dist-grid-wrap" x-ref="boardViewport" x-bind:class="panMode ? 'dist-grid-wrap--panning' : ''" x-on:pointerdown="startPan($event)" x-on:pointermove="movePan($event)" x-on:pointerup="endPan($event)" x-on:pointercancel="endPan($event)" x-on:lostpointercapture="endPan($event)" x-on:click.capture="blockPanClick($event)" tabindex="0" aria-label="Plano desplazable de asientos. Usa dos dedos para ampliar o reducir; activa Mover plano para arrastrarlo con un dedo.">
            <table class="dist-grid" x-ref="boardTable" x-bind:style="'zoom: ' + zoomLevel + '%'" aria-label="Distribución visual de asientos de {{ $this->sala->nombre }}">
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
                                    x-on:click.prevent.stop="handleCellClick('{{ $filaPlano }}', {{ $columna }}, {{ $celda ? (int) $celda['id'] : 'null' }}, {{ $celda && $celda['protegido'] ? 'true' : 'false' }})"
                                    @if ($modoSeleccionMultiple && $celda)
                                        x-bind:class="{ 'dist-asiento--seleccionado': isSelected({{ (int) $celda['id'] }}) }"
                                    @endif
                                    wire:key="celda-{{ $filaPlano }}-{{ $columna }}"
                                    aria-label="{{ $celda ? 'Asiento ' . $filaPlano . '-' . $columna . ', ' . ucfirst(strtolower($celda['tipo'])) . ', ' . ($celda['activo'] ? 'activo' : 'inactivo') . ($celda['protegido'] ? ', protegido por historial' : '') : 'Agregar asiento ' . $filaPlano . '-' . $columna }}"
                                    @if ($modoSeleccionMultiple)
                                        @if ($celda)
                                            x-bind:aria-pressed="isSelected({{ (int) $celda['id'] }}) ? 'true' : 'false'"
                                        @else
                                            aria-pressed="false"
                                        @endif
                                    @else
                                        aria-pressed="{{ $celda && $asientoSeleccionadoId === ($celda['id'] ?? null) ? 'true' : 'false' }}"
                                    @endif
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
        <p class="small muted mt-3 mb-0">Usa los separadores entre números para los pasillos longitudinales y los separadores entre filas para los transversales. Los cambios de pasillos quedan en el borrador hasta guardar la distribución; no se renumeran ni modifican asientos. Los asientos con candado están vinculados a funciones y conservan su identidad histórica. La selección de una celda vacía permite crear un asiento nuevo.</p>
    </div>

        </main>
        <aside class="dist-workspace__inspector" aria-label="Resumen y propiedades">
    <div class="row g-3 mb-3 dist-summary-grid" aria-label="Resumen de distribución">
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Asientos registrados</div><div class="h3 mb-0">{{ $distribucion['total'] }}</div></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Activos</div><div class="h3 mb-0">{{ $distribucion['activos'] }}</div></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Inactivos</div><div class="h3 mb-0">{{ $distribucion['inactivos'] }}</div></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Con historial</div><div class="h3 mb-0">{{ $distribucion['protegidos'] }}</div></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Pasillos</div><div class="h3 mb-0">{{ $distribucion['total_pasillos'] }}</div></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="panel rounded-3 p-3 h-100"><div class="muted small">Capacidad informativa</div><div class="h3 mb-0">{{ $this->sala->capacidad ?? 'No especificada' }}</div></div></div>
    </div>

    <details class="panel rounded-3 mb-3 dist-section dist-validation-panel dist-foldable-panel" aria-labelledby="dist-validacion-titulo" @if ($distribucion['advertencias'] !== []) open @endif>
        <summary class="dist-foldable-heading">
            <span id="dist-validacion-titulo" class="h6 mb-0">Validación de la distribución</span>
            @if ($distribucion['advertencias'] === [])
                <span class="badge text-bg-success">Sin huecos internos detectados</span>
            @else
                <span class="badge text-bg-warning">{{ count($distribucion['advertencias']) }} advertencia(s)</span>
            @endif
        </summary>
        <div class="dist-foldable-content p-3 pt-0">
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
    </details>

    <div class="panel rounded-3 p-3 p-md-4 dist-details-panel" x-ref="detailsPanel" id="dist-details-panel">
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
                @if ($seleccion && $seleccion['id'] > 0)
                    <a href="{{ route('admin.asientos.edit', $seleccion['id']) }}" class="btn btn-outline-light">Ver ficha completa</a>
                    @if (!$seleccion['protegido'])
                        <button type="button" class="btn btn-outline-danger" wire:click="eliminarAsiento" wire:loading.attr="disabled" wire:target="eliminarAsiento" aria-label="Quitar asiento del borrador">Quitar del borrador</button>
                    @endif
                @endif
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="guardarAsiento">
                    <span wire:loading.remove wire:target="guardarAsiento">{{ $seleccion ? 'Aplicar al borrador' : 'Agregar al borrador' }}</span>
                    <span wire:loading wire:target="guardarAsiento">Aplicando…</span>
                </button>
            </div>
            @error('asientoSeleccionadoId')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </form>
    </div>

        </aside>
    </div>

</div>
