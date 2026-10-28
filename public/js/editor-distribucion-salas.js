/* CinePrime · Editor de distribución de salas (lógica Alpine.js)
 *
 * Todo el borrador vive aquí. Livewire solo se usa en dos llamadas:
 *   $wire.guardarDistribucion(payload)  -> {ok, mensaje, conflicto?, snapshot?}
 *   $wire.recargarDistribucion()        -> snapshot fresco
 * Ninguna otra operación de edición hace peticiones al servidor.
 *
 * El estado pesado (asientos, pasillos, historial) se guarda en variables de
 * cierre, NO en propiedades reactivas, para no pagar el coste del proxy de
 * Alpine con ~800 asientos. Solo los valores derivados que la vista necesita
 * (contadores, selección, banderas) son reactivos.
 */
(function () {
    'use strict';

    let registrado = false;

    const TIPOS = {
        ESTANDAR: { label: 'Estándar', icono: '' },
        PREFERENCIAL: { label: 'Preferencial', icono: 'bi-star-fill' },
        ACCESIBLE: { label: 'Accesible', icono: 'bi-person-wheelchair' },
    };
    const MAX_COLS = 30;
    const MAX_FILAS_PLANTILLA = 26;
    const MAX_PLANTILLA = 780;
    const MAX_ASIENTOS = 2000;
    const MAX_HISTORIAL = 100;
    const MAX_TOASTS = 4;
    const FILA_RE = /^[A-Z0-9]{1,5}$/;

    const cmp = (a, b) => (a < b ? -1 : a > b ? 1 : 0); // mismo criterio que sort(..., SORT_STRING)
    const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
    const keyOf = (fila, numero) => fila + '|' + numero;
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const letraFila = (i) => String.fromCharCode(65 + i);

    /* ------------------------------------------------------------------ */
    /* Funciones puras (también exportadas para pruebas)                    */
    /* ------------------------------------------------------------------ */

    function calcularPlantilla(capacidad, opts) {
        opts = opts || {};
        const tipoBase = TIPOS[opts.tipo] ? opts.tipo : 'ESTANDAR';
        const cap = Number.isFinite(Number(capacidad)) && Number(capacidad) > 0 ? Math.floor(Number(capacidad)) : 0;
        let total, columnas, filas, recortado = false, basica = false;

        if (cap > 0) {
            total = Math.min(cap, MAX_PLANTILLA);
            recortado = cap > MAX_PLANTILLA;
            columnas = clamp(Math.round(Math.sqrt(total * 1.6)), 6, MAX_COLS);
            filas = Math.ceil(total / columnas);
            if (filas > MAX_FILAS_PLANTILLA) {
                columnas = Math.min(MAX_COLS, Math.ceil(total / MAX_FILAS_PLANTILLA));
                filas = Math.ceil(total / columnas);
            }
        } else {
            basica = true;
            filas = 8;
            columnas = 12;
            total = 96;
        }

        const centrales = new Set();
        if (opts.centrales && filas >= 3) {
            const ini = Math.floor(filas / 3);
            const fin = Math.max(ini + 1, Math.ceil((filas * 2) / 3));
            for (let r = ini; r < fin; r++) centrales.add(r);
        }

        const posPasillo = Math.floor(columnas / 2);
        const asientos = [];
        const pasillos = [];
        let preferenciales = 0;
        for (let r = 0; r < filas; r++) {
            const fila = letraFila(r);
            const cantidad = r < filas - 1 ? columnas : total - columnas * (filas - 1);
            const tipo = centrales.has(r) ? 'PREFERENCIAL' : tipoBase;
            for (let n = 1; n <= cantidad; n++) {
                asientos.push({ fila, numero: n, tipo, activo: 1 });
                if (tipo === 'PREFERENCIAL' && tipoBase !== 'PREFERENCIAL') preferenciales++;
            }
            if (posPasillo >= 1 && cantidad > posPasillo) pasillos.push({ fila, despues: posPasillo });
        }

        return { filas, columnas, total, recortado, basica, asientos, pasillos, preferenciales, posPasillo, ultimaFila: letraFila(filas - 1), capacidad: cap || null };
    }

    function calcularAdvertencias(seats, pasL, pasT) {
        const lista = [];
        const porFila = new Map();
        let fueraRango = 0;
        seats.forEach((s) => {
            if (!porFila.has(s.fila)) porFila.set(s.fila, []);
            porFila.get(s.fila).push(s.numero);
            if (s.numero > MAX_COLS) fueraRango++;
        });

        const filasPasillo = new Set();
        pasL.forEach((k) => filasPasillo.add(k.split('|')[0]));
        [...filasPasillo].sort(cmp).forEach((f) => {
            if (!porFila.has(f)) lista.push({ tipo: 'fila_sin_asientos', nivel: 'aviso', mensaje: `La fila ${f} tiene pasillos registrados, pero no tiene asientos.` });
        });

        let huecos = 0;
        let filasConHuecos = 0;
        [...porFila.keys()].sort(cmp).forEach((f) => {
            const nums = [...new Set(porFila.get(f))].sort((a, b) => a - b);
            if (nums.length < 2) return;
            const presentes = new Set(nums);
            const faltan = [];
            for (let n = nums[0] + 1; n < nums[nums.length - 1]; n++) if (!presentes.has(n)) faltan.push(n);
            if (faltan.length) {
                huecos += faltan.length;
                filasConHuecos++;
                const texto = faltan.length > 20 ? faltan.slice(0, 20).join(', ') + '…' : faltan.join(', ');
                lista.push({ tipo: 'huecos', nivel: 'aviso', mensaje: `La fila ${f} tiene posiciones vacías dentro de su rango: ${texto}.` });
            }
        });

        const filasConAsientos = [...porFila.keys()].sort(cmp);
        [...pasT].sort().forEach((k) => {
            const [f, g] = k.split('|');
            const i = filasConAsientos.indexOf(f);
            if (i < 0 || filasConAsientos[i + 1] !== g) {
                lista.push({ tipo: 'transversal', nivel: 'error', mensaje: `El pasillo transversal entre las filas ${f} y ${g} ya no separa filas consecutivas con asientos. Quítalo antes de guardar.` });
            }
        });

        if (fueraRango > 0) {
            lista.push({ tipo: 'fuera_rango', nivel: 'aviso', mensaje: `Hay ${fueraRango} asiento(s) con número superior a ${MAX_COLS}. Se conservan en los datos; el plano muestra hasta la columna ${MAX_COLS}.` });
        }

        return { lista, huecos, filasConHuecos, fueraRango };
    }

    /* ------------------------------------------------------------------ */
    /* Registro del componente                                              */
    /* ------------------------------------------------------------------ */

    function registrar() {
        if (registrado || !window.Alpine || typeof window.Alpine.data !== 'function') return;
        registrado = true;
        window.Alpine.data('editorDistribucion', crearComponente);
    }
    document.addEventListener('alpine:init', registrar);
    registrar();

    window.CinePrimeDistribucion = { calcularPlantilla, calcularAdvertencias, crearComponente };

    function crearComponente(config) {
        config = config || {};

        // Estado no reactivo
        let seats = [];
        let pasL = new Set(); // "fila|numero" (pasillo tras ese número)
        let pasT = new Set(); // "fila|filaSiguiente"
        let index = new Map();
        let sel = new Set();
        let anchorId = null;
        let undoStack = [];
        let redoStack = [];
        let originalNorm = '';
        let originalState = '';
        let nextTemp = -1;
        const extraRows = new Set();
        let colsExtra = 0;
        let focusKey = null;
        let ultimasFilas = [];
        let ultimasCols = 12;
        let renderPendiente = false;
        let stroke = null;
        let ignoreClick = false;
        const pointers = {};
        let pan = null;
        let pinch = null;
        let pinchMoved = false;
        let modalResolve = null;
        let modalFoco = null;
        let toastSeq = 0;
        const toastTimers = {};
        let tipEl = null;
        let overNotificado = false;

        const reindex = () => {
            index = new Map();
            seats.forEach((a) => index.set(keyOf(a.fila, a.numero), a));
        };
        const copiaAsiento = (a) => ({
            id: Number(a.id),
            fila: String(a.fila),
            numero: Number(a.numero),
            tipo: String(a.tipo),
            activo: Number(a.activo) ? 1 : 0,
            protegido: !!a.protegido,
        });
        const serializar = () => JSON.stringify({
            s: seats.map((a) => [a.id, a.fila, a.numero, a.tipo, a.activo, a.protegido ? 1 : 0]),
            l: [...pasL],
            t: [...pasT],
        });
        const restaurar = (json) => {
            const o = JSON.parse(json);
            seats = o.s.map((r) => ({ id: r[0], fila: r[1], numero: r[2], tipo: r[3], activo: r[4], protegido: !!r[5] }));
            pasL = new Set(o.l);
            pasT = new Set(o.t);
            reindex();
        };
        const normalizado = () => {
            const a = seats.map((s) => [s.id, s.fila, s.numero, s.tipo, s.activo]).sort((x, y) => x[0] - y[0] || cmp(x[1], y[1]) || x[2] - y[2]);
            return JSON.stringify([a, [...pasL].sort(), [...pasT].sort()]);
        };
        const filasConAsientos = () => [...new Set(seats.map((s) => s.fila))].sort(cmp);
        const filasVisibles = () => {
            const set = new Set();
            seats.forEach((s) => set.add(s.fila));
            pasL.forEach((k) => set.add(k.split('|')[0]));
            pasT.forEach((k) => k.split('|').forEach((f) => set.add(f)));
            extraRows.forEach((f) => set.add(f));
            if (set.size === 0) return ['A', 'B', 'C', 'D', 'E', 'F'];
            return [...set].sort(cmp);
        };
        const columnasVisibles = () => {
            let max = 0;
            seats.forEach((s) => { if (s.numero > max) max = s.numero; });
            return clamp(Math.max(12, max, colsExtra), 1, MAX_COLS);
        };
        const textoTipo = (t) => (TIPOS[t] ? TIPOS[t].label : t);

        return {
            // ---- estado reactivo ----
            nombreSala: config.nombreSala || '',
            capacidad: config.capacidad ?? null,
            maxAsientos: MAX_ASIENTOS,
            tool: 'select',
            brushTipo: 'ESTANDAR',
            brushActivo: '1',
            createTipo: 'ESTANDAR',
            createActivo: '1',
            multiMode: false,
            zoom: 100,
            minZoom: 50,
            maxZoom: 150,
            stats: { total: 0, activos: 0, inactivos: 0, protegidos: 0, pasillos: 0, fueraRango: 0 },
            advertencias: [],
            hayError: false,
            dirty: false,
            canUndo: false,
            canRedo: false,
            saving: false,
            conflicto: false,
            errorGuardado: '',
            selCount: 0,
            selSeat: null,
            selProt: 0,
            puedeIntercambiar: false,
            form: { id: null, fila: '', numero: 1, tipo: 'ESTANDAR', activo: '1' },
            statusMsg: 'Herramienta Seleccionar activa. Haz clic en un asiento para ver o editar sus detalles.',
            toasts: [],
            modal: { tipo: null, titulo: '', mensaje: '', detalles: [], confirmar: 'Aceptar', cancelar: 'Cancelar', peligro: false },
            tip: { visible: false, texto: '', x: 0, y: 0, abajo: false },
            tplTipo: 'ESTANDAR',
            tplCentrales: false,
            inspectorAbierto: true,

            // ---- getters ----
            get vacia() { return this.stats.total === 0; },
            get plantilla() { return calcularPlantilla(this.capacidad, { tipo: this.tplTipo, centrales: this.tplCentrales }); },
            get capacidadPct() { return this.capacidad ? Math.min(100, Math.round((this.stats.total / this.capacidad) * 100)) : 0; },
            get sobreCapacidad() { return !!this.capacidad && this.stats.total > this.capacidad; },
            get fichaUrl() {
                return this.selSeat && this.selSeat.id > 0 && config.fichaUrl ? config.fichaUrl.replace('__ID__', this.selSeat.id) : null;
            },
            get esPan() { return this.tool === 'pan'; },
            get herramientaTexto() {
                return ({ select: 'Selección', create: 'Crear asiento', 'brush-type': 'Pincel de tipo', 'brush-activo': 'Pincel de disponibilidad', erase: 'Quitar asiento', pan: 'Mover pizarra' })[this.tool];
            },
            etiquetaTipo(t) { return textoTipo(t); },

            // ---- ciclo de vida ----
            init() {
                this.cargar(config.snapshot || { asientos: [], pasillos: { longitudinales: [], transversales: [] } });
                overNotificado = this.sobreCapacidad;
                window.addEventListener('beforeunload', (e) => {
                    if (this.dirty) { e.preventDefault(); e.returnValue = ''; }
                });
                this.$nextTick(() => this.contraerSidebar());
                if (window.innerWidth < 1280) this.inspectorAbierto = false;
            },

            // Contrae el sidebar solo al abrir la página y solo en escritorio ancho,
            // igual que el script del layout. Después respeta lo que haga el usuario.
            contraerSidebar() {
                if (window.innerWidth < 1200) return;
                const shell = document.getElementById('adminShell');
                const boton = document.getElementById('adminDesktopToggle');
                if (!shell || shell.classList.contains('sidebar-collapsed')) return;
                shell.classList.add('sidebar-collapsed');
                if (boton) {
                    boton.setAttribute('aria-label', 'Expandir menú');
                    const icono = boton.querySelector('.material-symbols-outlined');
                    if (icono) icono.textContent = 'left_panel_open';
                }
            },

            cargar(snap) {
                seats = (snap.asientos || []).map(copiaAsiento);
                pasL = new Set(((snap.pasillos || {}).longitudinales || []).map((p) => keyOf(p.fila, p.despues_de_numero)));
                pasT = new Set(((snap.pasillos || {}).transversales || []).map((p) => keyOf(p.fila, p.fila_siguiente)));
                undoStack = [];
                redoStack = [];
                sel = new Set();
                anchorId = null;
                reindex();
                originalNorm = normalizado();
                originalState = serializar();
                this.form.id = null;
                this.sync();
            },

            // ---- derivados ----
            sync() {
                reindex();
                // Quita de la selección asientos que ya no existen
                const ids = new Set(seats.map((a) => a.id));
                sel.forEach((id) => { if (!ids.has(id)) sel.delete(id); });

                let activos = 0, protegidos = 0;
                seats.forEach((a) => { if (a.activo) activos++; if (a.protegido) protegidos++; });
                const adv = calcularAdvertencias(seats, pasL, pasT);
                this.stats = {
                    total: seats.length,
                    activos,
                    inactivos: seats.length - activos,
                    protegidos,
                    pasillos: pasL.size + pasT.size,
                    fueraRango: adv.fueraRango,
                };
                this.advertencias = adv.lista;
                this.hayError = adv.lista.some((a) => a.nivel === 'error');
                this.dirty = normalizado() !== originalNorm;
                this.canUndo = undoStack.length > 0;
                this.canRedo = redoStack.length > 0;

                const sobre = this.sobreCapacidad;
                if (sobre && !overNotificado) {
                    this.toast('warning', `El total de asientos (${seats.length}) supera la capacidad informada (${this.capacidad}). Es solo informativo: puedes guardar igualmente.`);
                }
                overNotificado = sobre;

                this.syncSel();
                this.programarRender();
            },

            syncSel() {
                this.selCount = sel.size;
                const elegidos = seats.filter((a) => sel.has(a.id));
                this.selProt = elegidos.filter((a) => a.protegido).length;
                this.puedeIntercambiar = elegidos.length === 2 && this.selProt === 0;
                if (elegidos.length === 1) {
                    const a = elegidos[0];
                    this.selSeat = { ...a };
                    if (this.form.id !== a.id) this.llenarForm(a);
                } else {
                    this.selSeat = null;
                    this.form.id = null;
                }
            },

            llenarForm(a) {
                this.form = { id: a.id, fila: a.fila, numero: a.numero, tipo: a.tipo, activo: String(a.activo) };
            },

            // ---- historial ----
            commit(fn) {
                const antes = serializar();
                const r = fn();
                if (r === false) return false;
                undoStack.push(antes);
                if (undoStack.length > MAX_HISTORIAL) undoStack.shift();
                redoStack = [];
                this.errorGuardado = '';
                this.sync();
                return true;
            },
            deshacer() {
                if (!undoStack.length) return;
                redoStack.push(serializar());
                restaurar(undoStack.pop());
                this.form.id = null;
                this.statusMsg = 'Cambio deshecho.';
                this.sync();
            },
            rehacer() {
                if (!redoStack.length) return;
                undoStack.push(serializar());
                restaurar(redoStack.pop());
                this.form.id = null;
                this.statusMsg = 'Cambio rehecho.';
                this.sync();
            },

            // ---- herramientas ----
            setTool(tool, valor) {
                this.tool = tool;
                if (tool === 'brush-type' && valor) this.brushTipo = valor;
                if (tool === 'brush-activo' && valor !== undefined) this.brushActivo = String(valor);
                pan = null;
                const msgs = {
                    select: 'Herramienta Seleccionar activa. Haz clic en un asiento para ver o editar sus detalles.',
                    create: `Crear asiento activo (${textoTipo(this.createTipo)}, ${this.createActivo === '1' ? 'activo' : 'inactivo'}). Haz clic en una celda vacía.`,
                    'brush-type': `Pincel de tipo activo: ${textoTipo(this.brushTipo)}. Toca los asientos que quieras pintar.`,
                    'brush-activo': `Pincel de disponibilidad activo: ${this.brushActivo === '1' ? 'Activo' : 'Inactivo'}. Toca los asientos que quieras pintar.`,
                    erase: 'Quitar asiento activo. Haz clic en un asiento para quitarlo del borrador.',
                    pan: 'Mover pizarra activo. Arrastra para desplazarte; elige otra herramienta para editar.',
                };
                this.statusMsg = msgs[tool] || msgs.select;
                this.programarRender();
            },
            toggleMulti() {
                this.multiMode = !this.multiMode;
                if (this.multiMode && this.tool !== 'select') this.setTool('select');
                this.statusMsg = this.multiMode
                    ? 'Selección múltiple activa: cada toque suma o quita un asiento de la selección.'
                    : 'Selección múltiple desactivada.';
            },

            // ---- acciones sobre celdas ----
            activarCelda(key, ev, via) {
                ev = ev || {};
                const [fila, numStr] = key.split('|');
                const numero = Number(numStr);
                const seat = index.get(key);
                focusKey = key;
                switch (this.tool) {
                    case 'select':
                        if (!seat) { this.statusMsg = 'Celda vacía. Usa la herramienta Crear asiento (C) para agregar uno.'; return; }
                        if (this.multiMode || ev.ctrlKey || ev.metaKey) {
                            if (sel.has(seat.id)) sel.delete(seat.id); else sel.add(seat.id);
                            anchorId = seat.id;
                        } else if (ev.shiftKey && anchorId !== null && seats.find((a) => a.id === anchorId && a.fila === fila)) {
                            const ancla = seats.find((a) => a.id === anchorId);
                            const lo = Math.min(ancla.numero, numero), hi = Math.max(ancla.numero, numero);
                            sel = new Set(seats.filter((a) => a.fila === fila && a.numero >= lo && a.numero <= hi).map((a) => a.id));
                        } else {
                            sel = new Set([seat.id]);
                            anchorId = seat.id;
                            this.llenarForm(seat);
                        }
                        this.syncSel();
                        this.programarRender();
                        return;
                    case 'create':
                        if (seat) { this.statusMsg = `La posición ${fila}-${numero} ya tiene un asiento.`; return; }
                        this.commit(() => this.crearAsiento(fila, numero));
                        this.statusMsg = `Asiento ${fila}-${numero} creado en el borrador.`;
                        return;
                    case 'brush-type':
                        if (!seat) { this.statusMsg = 'El pincel solo pinta asientos existentes. Usa Crear asiento en una celda vacía.'; return; }
                        if (seat.protegido) { this.toast('warning', 'Asiento protegido por historial: su tipo no se puede cambiar. Puedes usar el pincel de disponibilidad.'); return; }
                        if (this.commit(() => { if (seat.tipo === this.brushTipo) return false; seat.tipo = this.brushTipo; return true; })) {
                            this.statusMsg = `Pincel aplicado: ${textoTipo(this.brushTipo)}. Puedes seguir pintando.`;
                        }
                        return;
                    case 'brush-activo':
                        if (!seat) { this.statusMsg = 'El pincel solo pinta asientos existentes. Usa Crear asiento en una celda vacía.'; return; }
                        if (this.commit(() => { const v = Number(this.brushActivo); if (seat.activo === v) return false; seat.activo = v; return true; })) {
                            this.statusMsg = `Disponibilidad aplicada: ${this.brushActivo === '1' ? 'activo' : 'inactivo'}. Puedes seguir pintando.`;
                        }
                        return;
                    case 'erase':
                        if (!seat) return;
                        if (seat.protegido) { this.toast('error', `El asiento ${fila}-${numero} tiene historial de funciones y no se puede quitar.`); return; }
                        this.commit(() => this.quitarAsientos([seat.id]));
                        this.statusMsg = `Asiento ${fila}-${numero} quitado del borrador.`;
                        return;
                    default:
                        return;
                }
            },

            crearAsiento(fila, numero) {
                if (!FILA_RE.test(fila) || numero < 1 || numero > MAX_COLS) return false;
                if (seats.length >= MAX_ASIENTOS) { this.toast('error', `No se pueden superar ${MAX_ASIENTOS} asientos por sala.`); return false; }
                if (index.has(keyOf(fila, numero))) return false;
                const nuevo = { id: nextTemp--, fila, numero, tipo: this.createTipo, activo: Number(this.createActivo), protegido: false };
                seats.push(nuevo);
                index.set(keyOf(fila, numero), nuevo);
                return true;
            },
            quitarAsientos(ids) {
                const set = new Set(ids);
                const antes = seats.length;
                seats = seats.filter((a) => !set.has(a.id));
                reindex();
                return seats.length !== antes;
            },

            // Pintado/creación arrastrando (solo ratón o lápiz). Una pasada = un paso del historial.
            aplicarTrazo(key) {
                stroke.last = key;
                const [fila, numStr] = key.split('|');
                const numero = Number(numStr);
                const seat = index.get(key);
                let cambio = false;
                if (this.tool === 'brush-type' && seat) {
                    if (seat.protegido) {
                        if (!stroke.avisado) { this.toast('warning', 'Hay asientos protegidos por historial en el trazo: conservan su tipo.'); stroke.avisado = true; }
                    } else if (seat.tipo !== this.brushTipo) { seat.tipo = this.brushTipo; cambio = true; }
                } else if (this.tool === 'brush-activo' && seat) {
                    const v = Number(this.brushActivo);
                    if (seat.activo !== v) { seat.activo = v; cambio = true; }
                } else if (this.tool === 'create' && !seat) {
                    cambio = this.crearAsiento(fila, numero);
                }
                if (cambio) { stroke.changed = true; this.sync(); }
            },

            // ---- pasillos ----
            alternarPasilloL(fila, n) {
                const k = keyOf(fila, n);
                this.commit(() => { if (pasL.has(k)) pasL.delete(k); else pasL.add(k); return true; });
                this.statusMsg = pasL.has(k) ? `Pasillo agregado después de ${fila}-${n}.` : `Pasillo quitado después de ${fila}-${n}.`;
            },
            alternarPasilloT(fila, siguiente) {
                const k = keyOf(fila, siguiente);
                if (!pasT.has(k)) {
                    const filas = filasConAsientos();
                    const i = filas.indexOf(fila);
                    if (i < 0 || filas[i + 1] !== siguiente) {
                        this.toast('warning', 'El pasillo transversal debe separar dos filas con asientos y consecutivas.');
                        return;
                    }
                }
                this.commit(() => { if (pasT.has(k)) pasT.delete(k); else pasT.add(k); return true; });
                this.statusMsg = pasT.has(k) ? `Pasillo transversal agregado entre ${fila} y ${siguiente}.` : `Pasillo transversal quitado entre ${fila} y ${siguiente}.`;
            },

            agregarFila() {
                const filas = filasVisibles();
                const ultima = filas[filas.length - 1];
                let i = /^[A-Z]$/.test(ultima) ? ultima.charCodeAt(0) - 64 : 0;
                if (i >= 26) { this.toast('warning', 'Se alcanzó la fila Z.'); return; }
                extraRows.add(letraFila(i));
                this.statusMsg = `Fila ${letraFila(i)} agregada al plano (vacía).`;
                this.programarRender();
            },
            agregarColumnas() {
                const cols = columnasVisibles();
                if (cols >= MAX_COLS) { this.toast('info', `El plano muestra como máximo ${MAX_COLS} columnas.`); return; }
                colsExtra = Math.min(MAX_COLS, cols + 4);
                this.statusMsg = `El plano muestra ahora ${colsExtra} columnas.`;
                this.programarRender();
            },

            // ---- selección ----
            seleccionarFila(fila) {
                const ids = seats.filter((a) => a.fila === fila).map((a) => a.id);
                if (!ids.length) { this.statusMsg = `La fila ${fila} no tiene asientos.`; return; }
                sel = new Set(ids);
                anchorId = ids[0];
                this.syncSel();
                this.programarRender();
                this.statusMsg = `Fila ${fila} seleccionada: ${ids.length} asiento(s).`;
            },
            limpiarSel() {
                sel = new Set();
                anchorId = null;
                this.syncSel();
                this.programarRender();
            },
            seleccionados() { return seats.filter((a) => sel.has(a.id)); },

            // ---- acciones de la barra contextual ----
            async accionTipo(tipo) {
                const elegidos = this.seleccionados();
                const bloqueados = elegidos.filter((a) => a.protegido);
                const cambiables = elegidos.filter((a) => !a.protegido && a.tipo !== tipo);
                if (elegidos.length === 1 && bloqueados.length === 1) { this.toast('warning', 'Este asiento tiene historial de funciones: su tipo no se puede cambiar.'); return; }
                if (!cambiables.length) { this.toast('info', 'Ningún asiento seleccionado necesita ese cambio de tipo.'); return; }
                if (bloqueados.length) {
                    const ok = await this.confirmar({
                        titulo: 'Cambiar tipo de la selección',
                        mensaje: `Se aplicará el tipo ${textoTipo(tipo)} al borrador.`,
                        detalles: [`${cambiables.length} asiento(s) cambiarán de tipo.`, `${bloqueados.length} asiento(s) con historial conservarán su tipo.`],
                        confirmar: 'Aplicar al borrador',
                    });
                    if (!ok) return;
                }
                const ids = new Set(cambiables.map((a) => a.id));
                this.commit(() => { seats.forEach((a) => { if (ids.has(a.id)) a.tipo = tipo; }); return true; });
                this.toast('success', `${ids.size} asiento(s) cambiaron a ${textoTipo(tipo)} en el borrador.`);
            },
            accionActivo(valor) {
                const v = Number(valor);
                const cambiables = this.seleccionados().filter((a) => a.activo !== v);
                if (!cambiables.length) { this.toast('info', 'Ningún asiento seleccionado necesita ese cambio de disponibilidad.'); return; }
                const ids = new Set(cambiables.map((a) => a.id));
                this.commit(() => { seats.forEach((a) => { if (ids.has(a.id)) a.activo = v; }); return true; });
                this.toast('success', `${ids.size} asiento(s) ahora están ${v ? 'activos' : 'inactivos'} en el borrador.`);
            },
            async accionQuitar() {
                const elegidos = this.seleccionados();
                if (!elegidos.length) return;
                const protegidos = elegidos.filter((a) => a.protegido);
                const quitables = elegidos.filter((a) => !a.protegido);
                if (!quitables.length) { this.toast('error', 'Los asientos seleccionados tienen historial de funciones y no se pueden quitar.'); return; }
                if (elegidos.length > 1 || protegidos.length) {
                    const ok = await this.confirmar({
                        titulo: 'Quitar asientos del borrador',
                        mensaje: 'Los cambios solo afectan al borrador hasta que guardes la distribución.',
                        detalles: [`${quitables.length} asiento(s) se quitarán.`, ...(protegidos.length ? [`${protegidos.length} asiento(s) con historial se conservarán.`] : [])],
                        confirmar: 'Quitar del borrador',
                        peligro: true,
                    });
                    if (!ok) return;
                }
                const ids = quitables.map((a) => a.id);
                this.commit(() => this.quitarAsientos(ids));
                this.toast('success', `${ids.length} asiento(s) quitados del borrador.`);
            },
            intercambiar() {
                const e = this.seleccionados();
                if (e.length !== 2) { this.toast('warning', 'Selecciona exactamente dos asientos para intercambiar sus posiciones.'); return; }
                if (e[0].protegido || e[1].protegido) { this.toast('error', 'No se pueden intercambiar asientos vinculados al historial de funciones.'); return; }
                this.commit(() => {
                    const f = e[0].fila, n = e[0].numero;
                    e[0].fila = e[1].fila; e[0].numero = e[1].numero;
                    e[1].fila = f; e[1].numero = n;
                    return true;
                });
                this.toast('success', 'Posiciones intercambiadas en el borrador.');
            },
            aplicarForm() {
                if (!this.selSeat) return;
                const seat = seats.find((a) => a.id === this.selSeat.id);
                if (!seat) return;
                const fila = String(this.form.fila || '').trim().toUpperCase();
                const numero = Number(this.form.numero);
                const tipo = this.form.tipo;
                const activo = Number(this.form.activo) ? 1 : 0;
                if (!FILA_RE.test(fila)) { this.toast('error', 'La fila debe tener de 1 a 5 letras o números.'); return; }
                if (!Number.isInteger(numero) || numero < 1 || numero > 65535) { this.toast('error', 'El número debe ser un entero entre 1 y 65535.'); return; }
                if (!TIPOS[tipo]) { this.toast('error', 'El tipo de asiento no es válido.'); return; }
                if (seat.protegido && (fila !== seat.fila || numero !== seat.numero || tipo !== seat.tipo)) {
                    this.toast('error', 'Este asiento tiene historial: solo puedes cambiar su disponibilidad.');
                    this.llenarForm(seat);
                    return;
                }
                const otro = index.get(keyOf(fila, numero));
                if (otro && otro !== seat) { this.toast('error', 'Ya existe un asiento en esa fila y número.'); return; }
                const hecho = this.commit(() => {
                    if (seat.fila === fila && seat.numero === numero && seat.tipo === tipo && seat.activo === activo) return false;
                    seat.fila = fila; seat.numero = numero; seat.tipo = tipo; seat.activo = activo;
                    return true;
                });
                if (hecho) { this.llenarForm(seat); this.toast('success', 'Cambios aplicados al borrador.'); }
            },

            // ---- plantilla para salas vacías ----
            async aplicarPlantilla() {
                if (!this.vacia) return;
                const p = this.plantilla;
                const detalles = [
                    `${p.total} asientos en ${p.filas} fila(s) (A–${p.ultimaFila}) × ${p.columnas} columna(s).`,
                    `Pasillo central después del asiento ${p.posPasillo}.`,
                ];
                if (p.preferenciales) detalles.push(`${p.preferenciales} asiento(s) preferenciales en las filas centrales.`);
                if (p.recortado) detalles.push(`La capacidad informada (${p.capacidad}) excede ${MAX_PLANTILLA}; la plantilla se recortó a ${MAX_PLANTILLA}.`);
                const ok = await this.confirmar({
                    titulo: 'Aplicar plantilla al borrador',
                    mensaje: 'Se llenará el borrador de esta sala vacía. No se guarda nada hasta pulsar «Guardar distribución».',
                    detalles,
                    confirmar: 'Aplicar al borrador',
                });
                if (!ok) return;
                const hecho = this.commit(() => {
                    if (seats.length !== 0) return false; // nunca sobrescribe una distribución existente
                    p.asientos.forEach((a) => seats.push({ id: nextTemp--, fila: a.fila, numero: a.numero, tipo: a.tipo, activo: a.activo, protegido: false }));
                    p.pasillos.forEach((x) => pasL.add(keyOf(x.fila, x.despues)));
                    reindex();
                    return true;
                });
                if (hecho) this.toast('success', `Plantilla aplicada al borrador: ${p.total} asientos. Revisa y guarda cuando estés conforme.`);
                else this.toast('error', 'La sala ya no está vacía; la plantilla no se aplicó.');
            },

            // ---- guardar / cancelar / recargar ----
            payload() {
                return {
                    asientos: seats.map((s) => ({ id: s.id > 0 ? s.id : null, fila: s.fila, numero: s.numero, tipo: s.tipo, activo: s.activo === 1 })),
                    pasillos: {
                        longitudinales: [...pasL].map((k) => { const [f, n] = k.split('|'); return { fila: f, despues_de_numero: Number(n) }; }),
                        transversales: [...pasT].map((k) => { const [f, g] = k.split('|'); return { fila: f, fila_siguiente: g }; }),
                    },
                };
            },
            validarLocal() {
                if (seats.length > MAX_ASIENTOS) return `No se pueden superar ${MAX_ASIENTOS} asientos por sala.`;
                const visto = new Set();
                for (const s of seats) {
                    if (!FILA_RE.test(s.fila) || !Number.isInteger(s.numero) || s.numero < 1 || s.numero > 65535) return 'Hay un asiento con datos inválidos. Revisa fila y número.';
                    const k = keyOf(s.fila, s.numero);
                    if (visto.has(k)) return `La ubicación ${s.fila}-${s.numero} está duplicada.`;
                    visto.add(k);
                }
                const t = this.advertencias.find((a) => a.nivel === 'error');
                return t ? t.mensaje : null;
            },
            async guardar() {
                if (this.saving || !this.dirty) return;
                const err = this.validarLocal();
                if (err) { this.errorGuardado = err; this.toast('error', err); return; }
                this.saving = true;
                this.conflicto = false;
                this.errorGuardado = '';
                try {
                    const res = await this.$wire.guardarDistribucion(this.payload());
                    if (res && res.ok) {
                        this.cargar(res.snapshot);
                        overNotificado = this.sobreCapacidad;
                        this.toast('success', res.mensaje || 'Distribución guardada correctamente.');
                    } else {
                        const msg = (res && res.mensaje) || 'No se pudo guardar la distribución.';
                        this.errorGuardado = msg;
                        this.conflicto = !!(res && res.conflicto);
                        this.toast('error', msg);
                    }
                } catch (e) {
                    console.error('[editor-distribucion] Error al guardar', e);
                    this.errorGuardado = 'No se pudo comunicar con el servidor. Tus cambios siguen en el borrador; inténtalo de nuevo.';
                    this.toast('error', this.errorGuardado);
                } finally {
                    this.saving = false;
                }
            },
            async cancelar() {
                if (!this.dirty) return;
                const ok = await this.confirmar({
                    titulo: 'Cancelar cambios',
                    mensaje: '¿Descartar todos los cambios pendientes y volver a la distribución guardada?',
                    confirmar: 'Descartar cambios',
                    cancelar: 'Seguir editando',
                    peligro: true,
                });
                if (!ok) return;
                restaurar(originalState);
                undoStack = [];
                redoStack = [];
                sel = new Set();
                this.form.id = null;
                this.errorGuardado = '';
                this.conflicto = false;
                this.sync();
                this.toast('info', 'Cambios pendientes descartados; se restauró la distribución guardada.');
            },
            async recargar() {
                if (this.dirty) {
                    const ok = await this.confirmar({
                        titulo: 'Recargar distribución',
                        mensaje: 'Se descartarán tus cambios locales y se cargará la distribución actual de la base de datos.',
                        confirmar: 'Recargar',
                        peligro: true,
                    });
                    if (!ok) return;
                }
                this.saving = true;
                try {
                    const snap = await this.$wire.recargarDistribucion();
                    this.cargar(snap);
                    overNotificado = this.sobreCapacidad;
                    this.errorGuardado = '';
                    this.conflicto = false;
                    this.toast('info', 'Distribución recargada desde la base de datos.');
                } catch (e) {
                    console.error('[editor-distribucion] Error al recargar', e);
                    this.toast('error', 'No se pudo recargar la distribución. Revisa tu conexión.');
                } finally {
                    this.saving = false;
                }
            },

            // ---- zoom y desplazamiento ----
            zoomEn(siguiente, cx, cy) {
                const vp = this.$refs.boardViewport;
                siguiente = clamp(Math.round(siguiente), this.minZoom, this.maxZoom);
                if (!vp || siguiente === this.zoom) return;
                const viejo = this.zoom / 100;
                const fx = (vp.scrollLeft + cx) / viejo;
                const fy = (vp.scrollTop + cy) / viejo;
                this.zoom = siguiente;
                const nuevo = siguiente / 100;
                this.$nextTick(() => {
                    vp.scrollLeft = Math.max(0, fx * nuevo - cx);
                    vp.scrollTop = Math.max(0, fy * nuevo - cy);
                });
            },
            cambiarZoom(delta) {
                const vp = this.$refs.boardViewport;
                this.zoomEn(this.zoom + delta, vp ? vp.clientWidth / 2 : 0, vp ? vp.clientHeight / 2 : 0);
            },
            restablecerZoom() { this.zoom = 100; },
            ajustarSala() {
                const vp = this.$refs.boardViewport;
                const tabla = this.$refs.plano && this.$refs.plano.querySelector('table');
                if (!vp || !tabla) return;
                const escala = this.zoom / 100;
                const ancho = tabla.getBoundingClientRect().width / escala;
                const disponible = Math.max(1, vp.clientWidth - 24);
                this.zoom = clamp(Math.floor((disponible / ancho) * 100), this.minZoom, 100);
            },
            onWheel(e) {
                if (!(e.ctrlKey || e.metaKey)) return;
                e.preventDefault();
                const vp = this.$refs.boardViewport;
                const r = vp.getBoundingClientRect();
                this.zoomEn(this.zoom + (e.deltaY < 0 ? 10 : -10), e.clientX - r.left, e.clientY - r.top);
            },

            // ---- eventos del plano (delegados) ----
            onPointerDown(e) {
                const vp = this.$refs.boardViewport;
                if (!vp) return;
                this.ocultarTip();
                pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
                const ps = Object.values(pointers);
                if (ps.length === 2) {
                    const dx = ps[1].x - ps[0].x, dy = ps[1].y - ps[0].y;
                    const r = vp.getBoundingClientRect();
                    pinch = {
                        dist: Math.max(1, Math.hypot(dx, dy)),
                        zoom: this.zoom,
                        cx: (ps[0].x + ps[1].x) / 2 - r.left,
                        cy: (ps[0].y + ps[1].y) / 2 - r.top,
                    };
                    pinchMoved = false;
                    pan = null;
                    return;
                }
                if (ps.length > 2) return;
                if (this.tool === 'pan') {
                    if (e.button !== 0) return;
                    pan = { id: e.pointerId, x: e.clientX, y: e.clientY, l: vp.scrollLeft, t: vp.scrollTop, moved: false };
                    if (vp.setPointerCapture) vp.setPointerCapture(e.pointerId);
                    return;
                }
                if (e.pointerType === 'touch' || e.button !== 0) return;
                if (!['brush-type', 'brush-activo', 'create'].includes(this.tool)) return;
                const celda = e.target.closest && e.target.closest('[data-key]');
                if (!celda) return;
                stroke = { before: serializar(), changed: false, avisado: false, last: null };
                ignoreClick = true;
                this.aplicarTrazo(celda.dataset.key);
                if (vp.setPointerCapture) vp.setPointerCapture(e.pointerId);
            },
            onPointerMove(e) {
                const vp = this.$refs.boardViewport;
                if (!vp) return;
                if (pointers[e.pointerId]) pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
                const ps = Object.values(pointers);
                if (pinch && ps.length >= 2) {
                    const dx = ps[1].x - ps[0].x, dy = ps[1].y - ps[0].y;
                    const dist = Math.max(1, Math.hypot(dx, dy));
                    const siguiente = clamp(Math.round((pinch.zoom * dist) / pinch.dist), this.minZoom, this.maxZoom);
                    if (Math.abs(siguiente - pinch.zoom) >= 1) pinchMoved = true;
                    this.zoomEn(siguiente, pinch.cx, pinch.cy);
                    e.preventDefault();
                    return;
                }
                if (pan && pan.id === e.pointerId) {
                    const dx = e.clientX - pan.x, dy = e.clientY - pan.y;
                    if (Math.abs(dx) > 3 || Math.abs(dy) > 3) pan.moved = true;
                    vp.scrollLeft = pan.l - dx;
                    vp.scrollTop = pan.t - dy;
                    if (pan.moved) e.preventDefault();
                    return;
                }
                if (stroke && e.pointerType !== 'touch') {
                    const el = document.elementFromPoint(e.clientX, e.clientY);
                    const celda = el && el.closest ? el.closest('[data-key]') : null;
                    if (celda && vp.contains(celda) && celda.dataset.key !== stroke.last) this.aplicarTrazo(celda.dataset.key);
                }
            },
            onPointerUp(e) {
                const vp = this.$refs.boardViewport;
                delete pointers[e.pointerId];
                if (pan && pan.id === e.pointerId) pan = null;
                if (Object.keys(pointers).length < 2 && pinch) {
                    pinch = null;
                    setTimeout(() => { pinchMoved = false; }, 0);
                }
                if (stroke) {
                    const s = stroke;
                    stroke = null;
                    if (s.changed) {
                        undoStack.push(s.before);
                        if (undoStack.length > MAX_HISTORIAL) undoStack.shift();
                        redoStack = [];
                        this.errorGuardado = '';
                        this.sync();
                        this.statusMsg = 'Trazo aplicado al borrador.';
                    }
                    setTimeout(() => { ignoreClick = false; }, 0);
                }
                if (vp && vp.hasPointerCapture && vp.hasPointerCapture(e.pointerId)) vp.releasePointerCapture(e.pointerId);
            },
            onClick(e) {
                if (this.tool === 'pan' || pinch || pinchMoved) { e.preventDefault(); e.stopPropagation(); return; }
                if (ignoreClick) return;
                const t = e.target;
                if (!t.closest) return;
                const pl = t.closest('[data-pas-l]');
                if (pl) { const [f, n] = pl.dataset.pasL.split('|'); this.alternarPasilloL(f, Number(n)); return; }
                const pt = t.closest('[data-pas-t]');
                if (pt) { const [f, g] = pt.dataset.pasT.split('|'); this.alternarPasilloT(f, g); return; }
                const fl = t.closest('[data-fila-label]');
                if (fl) { this.seleccionarFila(fl.dataset.filaLabel); return; }
                const celda = t.closest('[data-key]');
                if (celda) this.activarCelda(celda.dataset.key, e, 'click');
            },
            onFocusIn(e) {
                const celda = e.target.closest && e.target.closest('[data-key]');
                if (celda) focusKey = celda.dataset.key;
            },
            onKeyDownPlano(e) {
                const celda = e.target.closest && e.target.closest('[data-key]');
                if (!celda) return;
                const [fila, numStr] = celda.dataset.key.split('|');
                const numero = Number(numStr);
                const i = ultimasFilas.indexOf(fila);
                let nf = fila, nn = numero;
                switch (e.key) {
                    case 'ArrowRight': nn = Math.min(ultimasCols, numero + 1); break;
                    case 'ArrowLeft': nn = Math.max(1, numero - 1); break;
                    case 'ArrowDown': nf = ultimasFilas[Math.min(ultimasFilas.length - 1, i + 1)]; break;
                    case 'ArrowUp': nf = ultimasFilas[Math.max(0, i - 1)]; break;
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        this.activarCelda(celda.dataset.key, e, 'key');
                        return;
                    case 'p':
                    case 'P':
                        e.preventDefault();
                        if (e.shiftKey) {
                            const sig = ultimasFilas[i + 1];
                            if (sig) this.alternarPasilloT(fila, sig);
                        } else if (numero < ultimasCols) {
                            this.alternarPasilloL(fila, numero);
                        }
                        return;
                    default: return;
                }
                e.preventDefault();
                const destino = this.$refs.plano.querySelector(`[data-key="${nf}|${nn}"]`);
                if (destino) { focusKey = nf + '|' + nn; destino.focus(); }
            },

            // ---- atajos globales ----
            onKey(e) {
                if (this.modal.tipo) return;
                const t = e.target;
                if (t && (/^(INPUT|SELECT|TEXTAREA)$/.test(t.tagName) || t.isContentEditable)) return;
                const k = e.key;
                if (e.ctrlKey || e.metaKey) {
                    const l = k.toLowerCase();
                    if (l === 'z') { e.preventDefault(); if (e.shiftKey) this.rehacer(); else this.deshacer(); }
                    else if (l === 'y') { e.preventDefault(); this.rehacer(); }
                    return;
                }
                if (e.altKey) return;
                switch (k) {
                    case 'v': case 'V': this.setTool('select'); break;
                    case 'c': case 'C': this.setTool('create'); break;
                    case '1': this.setTool('brush-type', 'ESTANDAR'); break;
                    case '2': this.setTool('brush-type', 'PREFERENCIAL'); break;
                    case '3': this.setTool('brush-type', 'ACCESIBLE'); break;
                    case 'a': case 'A': this.setTool('brush-activo', '1'); break;
                    case 'i': case 'I': this.setTool('brush-activo', '0'); break;
                    case 'h': case 'H': this.setTool(this.tool === 'pan' ? 'select' : 'pan'); break;
                    case 'Delete': if (sel.size) { e.preventDefault(); this.accionQuitar(); } break;
                    case 'Escape':
                        if (sel.size) this.limpiarSel();
                        else if (this.tool !== 'select') this.setTool('select');
                        break;
                    case '?': e.preventDefault(); this.abrirAyuda(); break;
                    default: break;
                }
            },

            // ---- tooltips propios ----
            tipOver(e) {
                if (e.pointerType === 'touch') return;
                const el = e.target.closest && e.target.closest('[data-tip]');
                if (!el) { this.ocultarTip(); return; }
                if (el === tipEl) return;
                this.mostrarTip(el);
            },
            tipFocus(e) {
                const el = e.target.closest && e.target.closest('[data-tip]');
                if (el) this.mostrarTip(el);
            },
            mostrarTip(el) {
                this.ocultarTip();
                const texto = el.getAttribute('data-tip');
                if (!texto) return;
                tipEl = el;
                el.setAttribute('aria-describedby', 'dist-tooltip');
                const r = el.getBoundingClientRect();
                const x = clamp(r.left + r.width / 2, 90, window.innerWidth - 90);
                const arriba = r.top > 56;
                this.tip = { visible: true, texto, x: Math.round(x), y: Math.round(arriba ? r.top - 8 : r.bottom + 8), abajo: !arriba };
            },
            ocultarTip() {
                if (tipEl && tipEl.isConnected) tipEl.removeAttribute('aria-describedby');
                tipEl = null;
                if (this.tip.visible) this.tip = { visible: false, texto: '', x: 0, y: 0, abajo: false };
            },

            // ---- toasts ----
            toast(tipo, mensaje) {
                const id = ++toastSeq;
                this.toasts.push({ id, tipo, mensaje });
                while (this.toasts.length > MAX_TOASTS) {
                    const viejo = this.toasts.shift();
                    clearTimeout(toastTimers[viejo.id]);
                    delete toastTimers[viejo.id];
                }
                toastTimers[id] = setTimeout(() => this.cerrarToast(id), tipo === 'error' ? 9000 : 5500);
            },
            cerrarToast(id) {
                clearTimeout(toastTimers[id]);
                delete toastTimers[id];
                this.toasts = this.toasts.filter((t) => t.id !== id);
            },

            // ---- diálogos accesibles ----
            confirmar(opts) {
                return new Promise((resolve) => {
                    modalResolve = resolve;
                    modalFoco = document.activeElement;
                    this.ocultarTip();
                    this.modal = {
                        tipo: 'confirm',
                        titulo: opts.titulo,
                        mensaje: opts.mensaje || '',
                        detalles: opts.detalles || [],
                        confirmar: opts.confirmar || 'Aceptar',
                        cancelar: opts.cancelar || 'Cancelar',
                        peligro: !!opts.peligro,
                    };
                    this.$nextTick(() => this.enfocarModal());
                });
            },
            abrirAyuda() {
                modalFoco = document.activeElement;
                this.ocultarTip();
                this.modal = { tipo: 'help', titulo: 'Atajos de teclado', mensaje: '', detalles: [], confirmar: 'Cerrar', cancelar: '', peligro: false };
                this.$nextTick(() => this.enfocarModal());
            },
            cerrarModal(resultado) {
                const r = modalResolve;
                modalResolve = null;
                this.modal = { ...this.modal, tipo: null };
                const foco = modalFoco;
                modalFoco = null;
                if (foco && foco.isConnected && foco.focus) foco.focus({ preventScroll: true });
                if (r) r(!!resultado);
            },
            enfocarModal() {
                const caja = this.$refs.modalCaja;
                if (!caja) return;
                const el = caja.querySelector('[data-modal-autofocus]') || caja.querySelector('button');
                if (el) el.focus();
            },
            onKeyModal(e) {
                if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); this.cerrarModal(false); return; }
                if (e.key !== 'Tab') return;
                const caja = this.$refs.modalCaja;
                const foco = [...caja.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')].filter((x) => !x.disabled && x.offsetParent !== null);
                if (!foco.length) return;
                const primero = foco[0], ultimo = foco[foco.length - 1];
                if (e.shiftKey && document.activeElement === primero) { e.preventDefault(); ultimo.focus(); }
                else if (!e.shiftKey && document.activeElement === ultimo) { e.preventDefault(); primero.focus(); }
            },

            // ---- render del plano ----
            programarRender() {
                if (renderPendiente) return;
                renderPendiente = true;
                const f = window.requestAnimationFrame || ((cb) => setTimeout(cb, 16));
                f(() => this.renderPlano());
            },
            renderPlano() {
                renderPendiente = false;
                const host = this.$refs.plano;
                if (!host) return;
                const filas = filasVisibles();
                const cols = columnasVisibles();
                ultimasFilas = filas;
                ultimasCols = cols;
                if (!focusKey || !filas.includes(focusKey.split('|')[0]) || Number(focusKey.split('|')[1]) > cols) focusKey = keyOf(filas[0], 1);

                const activo = document.activeElement;
                const claveFoco = activo && host.contains(activo) && activo.dataset ? activo.dataset.key || null : null;
                const crear = this.tool === 'create';

                let h = '<table class="dist-grid" aria-label="Plano de asientos"><thead><tr><th scope="col" class="dist-fila-label">Fila</th>';
                for (let n = 1; n <= cols; n++) {
                    h += `<th scope="col" class="dist-col-label">${n}</th>`;
                    if (n < cols) h += '<th scope="col" class="dist-pasillo-header" aria-hidden="true"></th>';
                }
                h += '</tr></thead><tbody>';

                filas.forEach((f, i) => {
                    const ef = esc(f);
                    h += `<tr><th scope="row" class="dist-fila-label"><button type="button" class="dist-fila-btn" tabindex="-1" data-fila-label="${ef}" data-tip="Seleccionar toda la fila ${ef}" aria-label="Seleccionar toda la fila ${ef}">${ef}</button></th>`;
                    for (let n = 1; n <= cols; n++) {
                        const k = keyOf(f, n);
                        const s = index.get(k);
                        const cls = ['dist-asiento'];
                        let etiqueta, tip, contenido, pressed = 'false';
                        if (s) {
                            const elegido = sel.has(s.id);
                            cls.push('t-' + s.tipo, s.activo ? 'is-activo' : 'is-inactivo');
                            if (s.protegido) cls.push('is-protegido');
                            if (elegido) { cls.push('is-sel'); pressed = 'true'; }
                            const estado = s.activo ? 'activo' : 'inactivo';
                            etiqueta = `Asiento ${f}-${n}, ${textoTipo(s.tipo)}, ${estado}${s.protegido ? ', protegido por historial' : ''}`;
                            tip = `${f}-${n} · ${textoTipo(s.tipo)} · ${s.activo ? 'Activo' : 'Inactivo'}${s.protegido ? ' · Con historial: solo cambia la disponibilidad' : ''}`;
                            const ico = TIPOS[s.tipo] ? TIPOS[s.tipo].icono : '';
                            contenido = (s.protegido ? '<i class="bi bi-lock-fill dist-lock" aria-hidden="true"></i>' : '')
                                + (ico ? `<i class="bi ${ico} dist-ico" aria-hidden="true"></i>` : '')
                                + `<span class="dist-num">${n}</span>`;
                        } else {
                            cls.push('is-vacio');
                            etiqueta = crear ? `Crear asiento en ${f}-${n}` : `Celda vacía ${f}-${n}`;
                            tip = crear ? `Crear asiento ${f}-${n}` : `Celda vacía ${f}-${n}`;
                            contenido = '<i class="bi bi-plus-lg" aria-hidden="true"></i>';
                        }
                        h += `<td><button type="button" class="${cls.join(' ')}" data-key="${esc(k)}" data-tip="${esc(tip)}" tabindex="${k === focusKey ? 0 : -1}" aria-pressed="${pressed}" aria-label="${esc(etiqueta)}">${contenido}</button></td>`;
                        if (n < cols) {
                            const act = pasL.has(k);
                            const lab = `${act ? 'Quitar' : 'Agregar'} pasillo entre ${f}-${n} y ${f}-${n + 1}`;
                            h += `<td class="dist-pasillo-cell${act ? ' is-activo' : ''}"><button type="button" class="dist-pasillo${act ? ' is-activo' : ''}" tabindex="-1" data-pas-l="${esc(k)}" data-tip="${lab}" aria-pressed="${act}" aria-label="${lab}"><span aria-hidden="true">${act ? '↔' : '·'}</span></button></td>`;
                        }
                    }
                    h += '</tr>';
                    if (i < filas.length - 1) {
                        const g = filas[i + 1];
                        const k = keyOf(f, g);
                        const act = pasT.has(k);
                        const lab = `${act ? 'Quitar' : 'Agregar'} pasillo transversal entre las filas ${f} y ${g}`;
                        h += `<tr class="dist-pasillo-transversal-row"><th aria-hidden="true"></th><td colspan="${cols * 2 - 1}"><button type="button" class="dist-pasillo dist-pasillo-transversal${act ? ' is-activo' : ''}" tabindex="-1" data-pas-t="${esc(k)}" data-tip="${lab}" aria-pressed="${act}" aria-label="${lab}">${act ? '↕ Pasillo transversal' : '· Pasillo transversal'}</button></td></tr>`;
                    }
                });
                h += '</tbody></table>';

                host.innerHTML = h;
                if (claveFoco) {
                    const el = host.querySelector(`[data-key="${claveFoco}"]`);
                    if (el) el.focus({ preventScroll: true });
                }
            },
        };
    }
})();
