@php
    $config = [
        'snapshot' => $snapshot,
        'capacidad' => $capacidad,
        'nombreSala' => $salaNombre,
        // El marcador __ID__ lo sustituye el JS por el id real del asiento.
        'fichaUrl' => route('admin.asientos.edit', '__ID__'),
    ];
    $tipos = [
        'ESTANDAR' => ['Estándar', '1', 'bi-brush'],
        'PREFERENCIAL' => ['Preferencial', '2', 'bi-star-fill'],
        'ACCESIBLE' => ['Accesible', '3', 'bi-person-wheelchair'],
    ];
@endphp
{{-- Raíz de Livewire: un único elemento con el ID que aísla todo el CSS. --}}
<div id="editor-distribucion-asientos">
    {{-- wire:ignore: ningún render de Livewire toca el DOM que controla Alpine. --}}
    <div
        wire:ignore
        class="dist-app"
        x-data="editorDistribucion(@js($config))"
        x-bind:data-tool="tool"
        x-on:keydown.window="onKey($event)"
        x-on:pointerover="tipOver($event)"
        x-on:pointerout="ocultarTip()"
        x-on:focusin="tipFocus($event)"
        x-on:focusout="ocultarTip()"
        x-on:scroll.window.passive="ocultarTip()"
    >
        {{-- ========== Barra de guardado ========== --}}
        <section class="dist-savebar" aria-label="Guardar o cancelar distribución">
            <div class="dist-savebar__info">
                <span class="dist-savebar__dot" x-bind:class="dirty ? 'is-dirty' : 'is-clean'" aria-hidden="true"></span>
                <div>
                    <div class="dist-savebar__title" role="status" aria-live="polite" aria-atomic="true" x-text="dirty ? 'Hay cambios pendientes' : 'Distribución guardada'">Distribución guardada</div>
                    <div class="dist-savebar__sub" x-text="dirty ? 'Los cambios están solo en el borrador del editor. Aún no se han escrito en la base de datos.' : 'No hay cambios pendientes de guardar.'">No hay cambios pendientes de guardar.</div>
                </div>
            </div>
            <div class="dist-savebar__actions">
                <button type="button" class="dist-btn dist-btn--icon" x-on:click="deshacer()" x-bind:disabled="!canUndo" data-tip="Deshacer (Ctrl+Z)" aria-label="Deshacer">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                </button>
                <button type="button" class="dist-btn dist-btn--icon" x-on:click="rehacer()" x-bind:disabled="!canRedo" data-tip="Rehacer (Ctrl+Y)" aria-label="Rehacer">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                </button>
                <button type="button" class="dist-btn" x-on:click="cancelar()" x-bind:disabled="!dirty || saving">Cancelar cambios</button>
                <button type="button" class="dist-btn dist-btn--primary" x-on:click="guardar()" x-bind:disabled="!dirty || saving || hayError" x-bind:aria-busy="saving">
                    <span x-show="!saving">Guardar distribución</span>
                    <span x-show="saving" x-cloak>Guardando…</span>
                </button>
            </div>
            <div class="dist-savebar__alert" x-show="errorGuardado" x-cloak role="alert">
                <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                <span x-text="errorGuardado"></span>
                <button type="button" class="dist-btn dist-btn--sm" x-show="conflicto" x-on:click="recargar()" x-bind:disabled="saving">Recargar distribución</button>
            </div>
        </section>

        <div class="dist-workspace">
            {{-- ========== Barra de herramientas ========== --}}
            <aside class="dist-tools" aria-label="Herramientas de la pizarra">
                <h2 class="dist-panel-title">Herramientas</h2>
                <div class="dist-toolbar" role="toolbar" aria-label="Herramientas de la pizarra">
                    <button type="button" class="dist-tool" x-on:click="setTool('select')" x-bind:class="{ 'is-active': tool === 'select' }" x-bind:aria-pressed="tool === 'select'" data-tip="Seleccionar (V)">
                        <i class="bi bi-cursor-fill" aria-hidden="true"></i><span>Seleccionar</span>
                    </button>
                    <button type="button" class="dist-tool" x-on:click="setTool('create')" x-bind:class="{ 'is-active': tool === 'create' }" x-bind:aria-pressed="tool === 'create'" data-tip="Crear asiento (C)">
                        <i class="bi bi-plus-square-fill" aria-hidden="true"></i><span>Crear asiento</span>
                    </button>
                    @foreach ($tipos as $valor => [$etiqueta, $atajo, $icono])
                        <button type="button" class="dist-tool" x-on:click="setTool('brush-type', '{{ $valor }}')" x-bind:class="{ 'is-active': tool === 'brush-type' && brushTipo === '{{ $valor }}' }" x-bind:aria-pressed="tool === 'brush-type' && brushTipo === '{{ $valor }}'" data-tip="Pincel {{ strtolower($etiqueta) }} ({{ $atajo }})">
                            <i class="bi {{ $icono }}" aria-hidden="true"></i><span>Pincel {{ strtolower($etiqueta) }}</span>
                        </button>
                    @endforeach
                    <button type="button" class="dist-tool" x-on:click="setTool('brush-activo', '1')" x-bind:class="{ 'is-active': tool === 'brush-activo' && brushActivo === '1' }" x-bind:aria-pressed="tool === 'brush-activo' && brushActivo === '1'" data-tip="Pintar activos (A)">
                        <i class="bi bi-circle-fill" aria-hidden="true"></i><span>Pintar activos</span>
                    </button>
                    <button type="button" class="dist-tool" x-on:click="setTool('brush-activo', '0')" x-bind:class="{ 'is-active': tool === 'brush-activo' && brushActivo === '0' }" x-bind:aria-pressed="tool === 'brush-activo' && brushActivo === '0'" data-tip="Pintar inactivos (I)">
                        <i class="bi bi-circle" aria-hidden="true"></i><span>Pintar inactivos</span>
                    </button>
                    <button type="button" class="dist-tool" x-on:click="setTool('erase')" x-bind:class="{ 'is-active': tool === 'erase' }" x-bind:aria-pressed="tool === 'erase'" data-tip="Quitar asiento del borrador (Supr con selección)">
                        <i class="bi bi-eraser-fill" aria-hidden="true"></i><span>Quitar asiento</span>
                    </button>
                    <button type="button" class="dist-tool" x-on:click="setTool(tool === 'pan' ? 'select' : 'pan')" x-bind:class="{ 'is-active': tool === 'pan' }" x-bind:aria-pressed="tool === 'pan'" data-tip="Mover pizarra (H)">
                        <i class="bi bi-arrows-move" aria-hidden="true"></i><span>Mover pizarra</span>
                    </button>
                    <button type="button" class="dist-tool" x-on:click="toggleMulti()" x-bind:class="{ 'is-active': multiMode }" x-bind:aria-pressed="multiMode" data-tip="Selección múltiple táctil: cada toque suma o quita">
                        <i class="bi bi-ui-checks-grid" aria-hidden="true"></i><span>Selección múltiple</span>
                    </button>
                    <button type="button" class="dist-tool" x-on:click="abrirAyuda()" data-tip="Atajos de teclado (?)">
                        <i class="bi bi-question-circle" aria-hidden="true"></i><span>Ayuda</span>
                    </button>
                </div>

                <div class="dist-tool-options" x-show="tool === 'create'" x-cloak>
                    <label for="dist-create-tipo">Tipo al crear</label>
                    <select id="dist-create-tipo" x-model="createTipo" x-on:change="setTool('create')">
                        @foreach ($tipos as $valor => [$etiqueta])
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <label for="dist-create-activo">Disponibilidad al crear</label>
                    <select id="dist-create-activo" x-model="createActivo" x-on:change="setTool('create')">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <div class="dist-tool-extra">
                    <button type="button" class="dist-btn dist-btn--sm" x-on:click="agregarFila()" data-tip="Agrega una fila vacía al plano">+ Fila</button>
                    <button type="button" class="dist-btn dist-btn--sm" x-on:click="agregarColumnas()" data-tip="Muestra más columnas (máximo 30)">+ Columnas</button>
                </div>
            </aside>

            {{-- ========== Pizarra central ========== --}}
            <main class="dist-canvas" aria-label="Pizarra de asientos">
                {{-- Plantilla: solo para salas sin asientos --}}
                <section class="dist-template" x-show="vacia" x-cloak aria-label="Plantilla para sala vacía">
                    <div class="dist-template__head">
                        <h2 class="dist-panel-title mb-1">Sala sin asientos</h2>
                        <p class="dist-muted mb-0">Puedes empezar con una plantilla o crear asientos con la herramienta «Crear asiento». Nada se guarda hasta pulsar «Guardar distribución».</p>
                    </div>
                    <div class="dist-template__body">
                        <div class="dist-field">
                            <label for="dist-tpl-tipo">Tipo por defecto</label>
                            <select id="dist-tpl-tipo" x-model="tplTipo">
                                @foreach ($tipos as $valor => [$etiqueta])
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="dist-check">
                            <input type="checkbox" x-model="tplCentrales">
                            <span>Marcar las filas centrales como Preferencial</span>
                        </label>
                        <p class="dist-template__preview" role="status" aria-live="polite">
                            <strong x-text="plantilla.total + ' asientos'"></strong>
                            en <span x-text="plantilla.filas"></span> fila(s) (A–<span x-text="plantilla.ultimaFila"></span>) ×
                            <span x-text="plantilla.columnas"></span> columna(s)
                            <span x-text="plantilla.basica ? '· plantilla básica (la sala no tiene capacidad informada)' : '· ajustada a la capacidad informada'"></span>.
                            <span x-show="plantilla.recortado" x-cloak>La capacidad supera 780: se recorta a 780.</span>
                        </p>
                        <button type="button" class="dist-btn dist-btn--primary" x-on:click="aplicarPlantilla()">Vista previa y aplicar al borrador</button>
                    </div>
                </section>

                {{-- Barra contextual --}}
                <section class="dist-contextbar" x-show="selCount > 0" x-cloak aria-label="Acciones de la selección">
                    <div class="dist-contextbar__identity">
                        <i class="bi" x-bind:class="selCount === 1 && selSeat && selSeat.protegido ? 'bi-lock-fill' : 'bi-grid-3x3-gap-fill'" aria-hidden="true"></i>
                        <div>
                            <div class="dist-contextbar__title" x-text="selCount === 1 && selSeat ? 'Asiento ' + selSeat.fila + '-' + selSeat.numero : selCount + ' asientos seleccionados'"></div>
                            <div class="dist-muted" x-show="selCount === 1 && selSeat" x-text="selSeat ? etiquetaTipo(selSeat.tipo) + ' · ' + (selSeat.activo ? 'Activo' : 'Inactivo') + (selSeat.protegido ? ' · Protegido por historial' : '') : ''"></div>
                            <div class="dist-muted" x-show="selCount > 1 && selProt > 0" x-cloak x-text="selProt + ' con historial (conservan tipo y posición)'"></div>
                        </div>
                    </div>
                    <div class="dist-contextbar__actions">
                        <span class="dist-contextbar__label">Tipo</span>
                        @foreach ($tipos as $valor => [$etiqueta])
                            <button type="button" class="dist-btn dist-btn--sm" x-on:click="accionTipo('{{ $valor }}')" x-bind:disabled="selCount === 1 && selSeat && selSeat.protegido" x-bind:class="{ 'is-current': selCount === 1 && selSeat && selSeat.tipo === '{{ $valor }}' }">{{ $etiqueta }}</button>
                        @endforeach
                        <span class="dist-contextbar__label">Disponibilidad</span>
                        <button type="button" class="dist-btn dist-btn--sm" x-on:click="accionActivo(1)" x-bind:class="{ 'is-current': selCount === 1 && selSeat && selSeat.activo === 1 }">Activo</button>
                        <button type="button" class="dist-btn dist-btn--sm" x-on:click="accionActivo(0)" x-bind:class="{ 'is-current': selCount === 1 && selSeat && selSeat.activo === 0 }">Inactivo</button>
                        <button type="button" class="dist-btn dist-btn--sm" x-show="selCount === 2" x-cloak x-on:click="intercambiar()" x-bind:disabled="!puedeIntercambiar" data-tip="Intercambia la posición de los dos asientos (no disponible con historial)">Intercambiar posiciones</button>
                        <button type="button" class="dist-btn dist-btn--sm dist-btn--danger" x-on:click="accionQuitar()" data-tip="Quitar del borrador (Supr)">Quitar del borrador</button>
                        <button type="button" class="dist-btn dist-btn--sm" x-on:click="limpiarSel()" data-tip="Limpiar selección (Esc)">Limpiar selección</button>
                    </div>
                </section>

                <section class="dist-board" aria-label="Plano de asientos">
                    <div class="dist-board__head">
                        <h2 class="dist-panel-title mb-0">Plano de asientos</h2>
                        <ul class="dist-legend" aria-label="Leyenda de asientos">
                            <li><span class="dist-ley dist-asiento t-ESTANDAR is-activo" aria-hidden="true"></span>Estándar</li>
                            <li><span class="dist-ley dist-asiento t-PREFERENCIAL is-activo" aria-hidden="true"><i class="bi bi-star-fill dist-ico"></i></span>Preferencial</li>
                            <li><span class="dist-ley dist-asiento t-ACCESIBLE is-activo" aria-hidden="true"><i class="bi bi-person-wheelchair dist-ico"></i></span>Accesible</li>
                            <li><span class="dist-ley dist-asiento t-ESTANDAR is-inactivo" aria-hidden="true"></span>Inactivo</li>
                            <li><span class="dist-ley dist-asiento t-ESTANDAR is-activo is-protegido" aria-hidden="true"><i class="bi bi-lock-fill dist-lock"></i></span>Con historial</li>
                            <li><span class="dist-ley dist-asiento is-vacio" aria-hidden="true"></span>Vacío</li>
                            <li><span class="dist-ley dist-ley--pasillo" aria-hidden="true"></span>Pasillo</li>
                        </ul>
                    </div>

                    <div class="dist-pantalla"><span class="material-symbols-outlined" aria-hidden="true">movie</span>PANTALLA</div>

                    <div class="dist-stage">
                        <div class="dist-zoombar" role="group" aria-label="Controles de zoom">
                            <button type="button" class="dist-btn dist-btn--icon" x-on:click="cambiarZoom(-10)" x-bind:disabled="zoom <= minZoom" data-tip="Alejar" aria-label="Alejar plano"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                            <output class="dist-zoombar__value" aria-live="polite" aria-label="Nivel de zoom" x-text="zoom + '%'">100%</output>
                            <button type="button" class="dist-btn dist-btn--icon" x-on:click="cambiarZoom(10)" x-bind:disabled="zoom >= maxZoom" data-tip="Acercar" aria-label="Acercar plano"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                            <button type="button" class="dist-btn dist-btn--sm" x-on:click="ajustarSala()" data-tip="Ajustar el plano al ancho visible">Ajustar sala</button>
                            <button type="button" class="dist-btn dist-btn--sm" x-on:click="restablecerZoom()">Restablecer 100%</button>
                        </div>

                        <div class="dist-grid-wrap" x-ref="boardViewport" tabindex="0"
                             aria-label="Plano desplazable de asientos. Usa las flechas para moverte entre celdas, Enter para activar, P para un pasillo. Dos dedos para ampliar o reducir."
                             x-bind:class="{ 'is-panning': esPan }"
                             x-on:pointerdown="onPointerDown($event)"
                             x-on:pointermove="onPointerMove($event)"
                             x-on:pointerup="onPointerUp($event)"
                             x-on:pointercancel="onPointerUp($event)"
                             x-on:lostpointercapture="onPointerUp($event)"
                             x-on:click.capture="onClick($event)"
                             x-on:keydown="onKeyDownPlano($event)"
                             x-on:focusin="onFocusIn($event)"
                             x-on:wheel="onWheel($event)">
                            {{-- El plano lo dibuja renderPlano() en el JS. --}}
                            <div class="dist-zoom" x-ref="plano" x-bind:style="{ zoom: zoom + '%' }"></div>
                        </div>
                    </div>

                    <p class="dist-interaction-status" role="status" aria-live="polite" aria-atomic="true" x-text="statusMsg"></p>
                    <p class="dist-muted dist-board__help">Usa los separadores entre números para pasillos longitudinales y los separadores entre filas para transversales. Los asientos con candado tienen historial: solo cambia su disponibilidad. Todo queda en el borrador hasta guardar.</p>
                </section>

                {{-- Barra de estado --}}
                <section class="dist-statusbar" aria-label="Estado de la distribución">
                    <div class="dist-statusbar__counts">
                        <span><strong x-text="stats.total"></strong> asientos</span>
                        <span><strong x-text="stats.activos"></strong> activos</span>
                        <span><strong x-text="stats.inactivos"></strong> inactivos</span>
                        <span><strong x-text="stats.protegidos"></strong> con historial</span>
                        <span><strong x-text="stats.pasillos"></strong> pasillos</span>
                    </div>
                    <div class="dist-capacity" x-show="capacidad" x-cloak>
                        <div class="dist-capacity__text" x-bind:class="{ 'is-over': sobreCapacidad }">
                            <span x-text="stats.total + ' asientos de ' + capacidad + ' (capacidad informada)'"></span>
                            <span x-show="sobreCapacidad" x-cloak>· supera la capacidad (solo informativo)</span>
                        </div>
                        <div class="dist-capacity__bar" role="progressbar" aria-label="Asientos respecto a la capacidad informada" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="capacidadPct">
                            <div class="dist-capacity__fill" x-bind:class="{ 'is-over': sobreCapacidad }" x-bind:style="{ width: capacidadPct + '%' }"></div>
                        </div>
                    </div>
                    <div class="dist-capacity" x-show="!capacidad">
                        <div class="dist-capacity__text"><span x-text="stats.total + ' asientos · capacidad no especificada'"></span></div>
                    </div>
                </section>
            </main>

            {{-- ========== Inspector ========== --}}
            <aside class="dist-inspector" aria-label="Resumen y propiedades">
                <button type="button" class="dist-inspector__toggle" x-on:click="inspectorAbierto = !inspectorAbierto" x-bind:aria-expanded="inspectorAbierto" aria-controls="dist-inspector-body">
                    <span class="dist-panel-title mb-0">Inspector</span>
                    <i class="bi" x-bind:class="inspectorAbierto ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                </button>
                <div id="dist-inspector-body" class="dist-inspector__body" x-bind:class="{ 'is-plegado': !inspectorAbierto }">
                    <section class="dist-card" aria-label="Propiedades del asiento">
                        <h2 class="dist-panel-title" x-text="selSeat ? 'Asiento ' + selSeat.fila + '-' + selSeat.numero : (selCount > 1 ? selCount + ' asientos' : 'Propiedades')"></h2>

                        <p class="dist-muted" x-show="selCount === 0">Selecciona un asiento en el plano para ver y editar sus propiedades.</p>
                        <p class="dist-muted" x-show="selCount > 1" x-cloak>Con varios asientos seleccionados usa las acciones de la barra contextual sobre la pizarra.</p>

                        <template x-if="selSeat">
                            <form x-on:submit.prevent="aplicarForm()" class="dist-form">
                                <div class="dist-alert dist-alert--warn" x-show="selSeat.protegido" role="status">
                                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                    <span>Tiene historial de funciones. No se puede cambiar su tipo ni ubicación, ni eliminarlo; solo su disponibilidad.</span>
                                </div>
                                <div class="dist-form__row">
                                    <div class="dist-field">
                                        <label for="dist-fila">Fila</label>
                                        <input id="dist-fila" type="text" maxlength="5" x-model="form.fila" x-bind:disabled="selSeat.protegido" autocomplete="off">
                                    </div>
                                    <div class="dist-field">
                                        <label for="dist-numero">Número</label>
                                        <input id="dist-numero" type="number" min="1" max="65535" x-model.number="form.numero" x-bind:disabled="selSeat.protegido">
                                    </div>
                                </div>
                                <div class="dist-field">
                                    <label for="dist-tipo">Tipo de asiento</label>
                                    <select id="dist-tipo" x-model="form.tipo" x-bind:disabled="selSeat.protegido">
                                        @foreach ($tipos as $valor => [$etiqueta])
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="dist-field">
                                    <label for="dist-activo">Disponibilidad</label>
                                    <select id="dist-activo" x-model="form.activo">
                                        <option value="1">Activo</option>
                                        <option value="0">Inactivo</option>
                                    </select>
                                </div>
                                <div class="dist-form__actions">
                                    <a class="dist-btn dist-btn--sm" x-show="fichaUrl" x-bind:href="fichaUrl">Ver ficha completa</a>
                                    <button type="button" class="dist-btn dist-btn--sm dist-btn--danger" x-show="!selSeat.protegido" x-on:click="accionQuitar()">Quitar del borrador</button>
                                    <button type="submit" class="dist-btn dist-btn--primary dist-btn--sm">Aplicar al borrador</button>
                                </div>
                            </form>
                        </template>
                    </section>

                    <section class="dist-card" aria-label="Resumen">
                        <h2 class="dist-panel-title">Resumen</h2>
                        <dl class="dist-summary">
                            <div><dt>Asientos</dt><dd x-text="stats.total"></dd></div>
                            <div><dt>Activos</dt><dd x-text="stats.activos"></dd></div>
                            <div><dt>Inactivos</dt><dd x-text="stats.inactivos"></dd></div>
                            <div><dt>Con historial</dt><dd x-text="stats.protegidos"></dd></div>
                            <div><dt>Pasillos</dt><dd x-text="stats.pasillos"></dd></div>
                            <div><dt>Capacidad informativa</dt><dd x-text="capacidad ?? 'No especificada'"></dd></div>
                        </dl>
                    </section>

                    <section class="dist-card" aria-label="Validación de la distribución">
                        <h2 class="dist-panel-title">Validación</h2>
                        <p class="dist-muted mb-0" x-show="advertencias.length === 0">Sin advertencias. La revisión es informativa y no bloquea la edición, salvo los pasillos transversales inválidos.</p>
                        <ul class="dist-warnings" x-show="advertencias.length > 0" x-cloak>
                            <template x-for="(a, i) in advertencias" x-bind:key="a.tipo + i">
                                <li x-bind:class="a.nivel === 'error' ? 'is-error' : 'is-aviso'">
                                    <i class="bi" x-bind:class="a.nivel === 'error' ? 'bi-x-octagon-fill' : 'bi-exclamation-triangle-fill'" aria-hidden="true"></i>
                                    <span x-text="a.mensaje"></span>
                                </li>
                            </template>
                        </ul>
                        <p class="dist-muted" x-show="advertencias.length > 0" x-cloak>Los huecos pueden ser intencionales; no se renumeran asientos.</p>
                    </section>
                </div>
            </aside>
        </div>

        {{-- ========== Tooltip propio ========== --}}
        <div id="dist-tooltip" class="dist-tooltip" role="tooltip" x-show="tip.visible" x-cloak
             x-bind:class="{ 'is-abajo': tip.abajo }" x-bind:style="{ left: tip.x + 'px', top: tip.y + 'px' }" x-text="tip.texto"></div>

        {{-- ========== Toasts ========== --}}
        <div class="dist-toasts" aria-live="polite">
            <template x-for="t in toasts" x-bind:key="t.id">
                <div class="dist-toast" x-bind:class="'is-' + t.tipo" x-bind:role="t.tipo === 'error' ? 'alert' : 'status'">
                    <i class="bi" x-bind:class="{ 'bi-check-circle-fill': t.tipo === 'success', 'bi-info-circle-fill': t.tipo === 'info', 'bi-exclamation-triangle-fill': t.tipo === 'warning', 'bi-x-octagon-fill': t.tipo === 'error' }" aria-hidden="true"></i>
                    <span x-text="t.mensaje"></span>
                    <button type="button" class="dist-toast__close" x-on:click="cerrarToast(t.id)" aria-label="Cerrar mensaje">×</button>
                </div>
            </template>
        </div>

        {{-- ========== Diálogo propio (confirmación y ayuda) ========== --}}
        <div class="dist-modal-backdrop" x-show="modal.tipo" x-cloak x-on:keydown="onKeyModal($event)">
            <div class="dist-modal" x-ref="modalCaja" role="dialog" aria-modal="true" aria-labelledby="dist-modal-titulo">
                <h2 id="dist-modal-titulo" class="dist-modal__title" x-text="modal.titulo"></h2>

                <div x-show="modal.tipo === 'confirm'">
                    <p class="dist-modal__text" x-text="modal.mensaje"></p>
                    <ul class="dist-modal__list" x-show="modal.detalles.length" x-cloak>
                        <template x-for="(d, i) in modal.detalles" x-bind:key="i"><li x-text="d"></li></template>
                    </ul>
                    <div class="dist-modal__actions">
                        <button type="button" class="dist-btn" x-on:click="cerrarModal(false)" x-text="modal.cancelar"></button>
                        <button type="button" class="dist-btn" x-bind:class="modal.peligro ? 'dist-btn--danger-solid' : 'dist-btn--primary'" data-modal-autofocus x-on:click="cerrarModal(true)" x-text="modal.confirmar"></button>
                    </div>
                </div>

                <div x-show="modal.tipo === 'help'" x-cloak>
                    <dl class="dist-shortcuts">
                        <div><dt><kbd>V</kbd></dt><dd>Seleccionar</dd></div>
                        <div><dt><kbd>C</kbd></dt><dd>Crear asiento</dd></div>
                        <div><dt><kbd>1</kbd> <kbd>2</kbd> <kbd>3</kbd></dt><dd>Pincel Estándar / Preferencial / Accesible</dd></div>
                        <div><dt><kbd>A</kbd> / <kbd>I</kbd></dt><dd>Pincel activo / inactivo</dd></div>
                        <div><dt><kbd>H</kbd></dt><dd>Mover pizarra</dd></div>
                        <div><dt><kbd>Supr</kbd></dt><dd>Quitar la selección del borrador</dd></div>
                        <div><dt><kbd>Esc</kbd></dt><dd>Limpiar selección o volver a Seleccionar</dd></div>
                        <div><dt><kbd>Ctrl</kbd>+<kbd>Z</kbd></dt><dd>Deshacer</dd></div>
                        <div><dt><kbd>Ctrl</kbd>+<kbd>Y</kbd> / <kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>Z</kbd></dt><dd>Rehacer</dd></div>
                        <div><dt><kbd>Ctrl</kbd>/<kbd>⌘</kbd> + clic</dt><dd>Sumar o quitar de la selección</dd></div>
                        <div><dt><kbd>Shift</kbd> + clic</dt><dd>Seleccionar un rango en la misma fila</dd></div>
                        <div><dt>Clic en la letra de fila</dt><dd>Seleccionar la fila entera</dd></div>
                        <div><dt><kbd>Ctrl</kbd> + rueda</dt><dd>Zoom del plano</dd></div>
                        <div><dt>Flechas, <kbd>Enter</kbd>/<kbd>Espacio</kbd></dt><dd>Moverse por el plano y activar la celda</dd></div>
                        <div><dt><kbd>P</kbd> / <kbd>Shift</kbd>+<kbd>P</kbd></dt><dd>Pasillo tras la celda / pasillo transversal bajo la fila</dd></div>
                        <div><dt><kbd>?</kbd></dt><dd>Abrir esta ayuda</dd></div>
                    </dl>
                    <p class="dist-muted">Con ratón o lápiz, los pinceles y «Crear asiento» pintan arrastrando. En pantallas táctiles, un toque por asiento.</p>
                    <div class="dist-modal__actions">
                        <button type="button" class="dist-btn dist-btn--primary" data-modal-autofocus x-on:click="cerrarModal(false)">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
