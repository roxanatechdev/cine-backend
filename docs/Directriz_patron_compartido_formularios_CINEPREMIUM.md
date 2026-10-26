# CINEPREMIUM — Directriz del patrón compartido de formularios administrativos

**Versión:** 1.9  
**Fecha:** 25 de octubre de 2026  
**Estado:** patrón inicial aprobado visualmente por el usuario en el formulario piloto de Géneros.  
**Referencia visual:** `resources/views/admin/peliculas/form.blade.php` y `public/css/formulario.css`.  
**Caso piloto migrado:** `resources/views/admin/generos/form.blade.php`.

---

## 1. Propósito

Esta directriz define el patrón que se debe aplicar al revisar, migrar o crear formularios administrativos en CINEPREMIUM. Su objetivo es unificar el aspecto y las convenciones de los formularios sin alterar la lógica de negocio, las reglas de validación, las rutas, la autorización, las operaciones CRUD ni el comportamiento JavaScript que ya funcione.

Esta directriz es un documento vivo: **cada nuevo hallazgo, excepción, componente reutilizable o corrección debe documentarse aquí**. No crear una segunda familia de estilos o componentes si puede ampliarse de forma compatible el patrón existente.

## 2. Fuentes de verdad y precedencia

1. **Referencia visual y de interacción:** formulario de películas (`resources/views/admin/peliculas/form.blade.php`).
2. **Sistema de estilos:** `public/css/formulario.css`, con tokens `--cm-*` y clases `form-cm__*`.
3. **Selector personalizado:** `resources/views/components/form/select.blade.php`.
4. **Patrón de implementación del piloto:** `resources/views/admin/generos/form.blade.php`.
5. **Carga global de estilos y scripts:** comprobar `resources/views/admin/layout.blade.php` antes de añadir nuevas importaciones o listeners.

Si el código actual contradice esta directriz, inspeccionar primero el comportamiento real y las dependencias. No asumir que el documento autoriza a eliminar lógica existente. Registrar cualquier discrepancia antes de modificarla.

## 3. Principios obligatorios

- Reutilizar los componentes `x-form.*` existentes siempre que sean compatibles.
- Usar `x-form.select` para desplegables cuando las opciones y el comportamiento encajen. No reemplazarlo por un `<select>` nativo solo por conveniencia.
- No duplicar CSS ni JavaScript si la regla o el listener ya existe globalmente.
- Mantener las rutas, los nombres de campos, los métodos HTTP, CSRF, validación de servidor, valores `old()`, mensajes de sesión, permisos y reglas de negocio.
- Preservar particularidades justificadas: cargas de archivos, variantes, pestañas, campos condicionales, fechas, relaciones y operaciones financieras.
- Mantener etiquetas accesibles, mensajes de error, ayudas y estados de foco/invalidación.
- No migrar lógica de controlador a Blade ni introducir Livewire como parte de una tarea exclusivamente visual.
- No declarar una migración terminada hasta ejecutar las comprobaciones disponibles y registrar claramente las pruebas pendientes.

## 4. Estructura visual estándar

### 4.1 Encabezado de página

Usar la estructura de encabezado que ya emplea el panel administrativo:

- Título claro para crear o editar, condicionado por el modo.
- Descripción breve y útil.
- Acción de regreso al índice del módulo.
- Clases responsivas existentes de Bootstrap y clases tipográficas del proyecto.

No incorporar un encabezado duplicado dentro de la tarjeta si ya existe fuera de ella. Para formularios pequeños, mantener un encabezado compacto; para formularios largos, se pueden conservar pestañas o secciones específicas.

### 4.2 Contenedor

Usar `x-form.layout` para el contenedor común. Admite `title`, `subtitle`, atributos HTML y contenido mediante slot.

```blade
<x-form.layout
    title="Información del registro"
    subtitle="Completa los datos solicitados.">
    {{-- formulario y secciones --}}
</x-form.layout>
```

El componente añade `.form-cm`. Si no se necesita encabezado interno, omitir `title` y `subtitle`.

### 4.3 Secciones y distribución

- Envolver los grupos de campos en `<section class="form-cm__section">`.
- Usar la cuadrícula Bootstrap (`.row`, `.col-12`, `.col-md-*`, `.col-lg-*`) para adaptación responsiva.
- Mantener un orden de lectura lógico: datos principales, atributos relacionados y estado; ajustar los grupos según la entidad real.
- No forzar pestañas en formularios sencillos.

### 4.4 Acciones

Usar `x-form.actions` para la zona de acciones al pie. Pasar las clases de alineación como atributos; el componente añade `.form-cm__actions`.

```blade
<x-form.actions class="justify-content-end">
    <a href="{{ route('admin.modulo.index') }}" class="btn btn-outline-light">Cancelar</a>
    <button type="submit" class="btn btn-primary">Guardar cambios</button>
</x-form.actions>
```

Mantener la ruta de cancelación y el texto del botón adecuados para crear o editar. No cambiar el endpoint ni el método HTTP para uniformar la apariencia.

## 5. Catálogo de componentes compartidos

Las APIs siguientes corresponden a la implementación presente en el piloto. Antes de ampliar una API, buscar sus usos en el proyecto y probar los módulos que dependen de ella.

### 5.1 `x-form.layout`

**Archivo:** `resources/views/components/form/layout.blade.php`  
**Responsabilidad:** contenedor `.form-cm` y encabezado interno opcional.

**Props:**
- `title` (opcional): título de la tarjeta.
- `subtitle` (opcional): descripción breve.
- Atributos HTML adicionales se aplican al contenedor mediante `$attributes`.
- El contenido del formulario va en `$slot`.

**Uso:** una instancia por tarjeta/formulario principal, salvo que la pantalla realmente tenga varios formularios independientes.

### 5.2 `x-form.field`

**Archivo:** `resources/views/components/form/field.blade.php`  
**Responsabilidad:** grupo `.form-cm__group`, etiqueta, indicador de obligatoriedad, slot del control, texto de ayuda opcional y error del campo.

**Props:**
- `name` (requerido): nombre de campo para buscar errores.
- `label` (requerido): texto de etiqueta.
- `id` (opcional; por defecto `name`): ID asociado al control.
- `required` (opcional, por defecto `false`): muestra asterisco e indicación accesible.
- `help` (opcional): texto de ayuda.
- `showError` (opcional, por defecto `true`): permite ocultar el error renderizado por el campo cuando el control interno ya muestra su propio mensaje.
- Atributos adicionales se aplican al contenedor.

**Uso recomendado:** para controles cuyo elemento interactivo tenga el mismo `id` que el campo. En componentes cuyo elemento interactivo visible use otro ID (por ejemplo `x-form.select`, que utiliza `name_toggle`), no envolver a ciegas: asociar la etiqueta al control visible o adaptar el componente de forma compatible.

### 5.3 `x-form.input`

**Archivo:** `resources/views/components/form/input.blade.php`  
**Responsabilidad:** input de texto/valor con clase visual, `old()`, atributos habituales y estado de error.

**Props:** `name` (requerido), `id` (opcional), `type` (por defecto `text`), `value`, `placeholder`, `required`, `maxlength`, `autocomplete`. Acepta atributos HTML adicionales.

**Comportamiento:** usa `old($name, $value)`, añade `.form-cm__control`, `.is-invalid` si hay error y construye `aria-describedby` para el error y/o el ID indicado en atributos.

**Uso:** utilizar `value` para el valor del modelo en edición; no escribir manualmente el valor con interpolación en el atributo.

### 5.4 `x-form.textarea`

**Archivo:** `resources/views/components/form/textarea.blade.php`  
**Responsabilidad:** textarea con la misma presentación y recuperación de valores anteriores que `x-form.input`.

**Props:** `name` (requerido), `id`, `value`, `placeholder`, `required`, `maxlength`, `rows` (por defecto `3`). Acepta atributos HTML adicionales.

**Comportamiento:** usa `old($name, $value)`, clase `.form-cm__control`, estado `.is-invalid` y asociación accesible al error/ayuda.

### 5.5 `x-form.select` — componente preferente para desplegables compatibles

**Archivo:** `resources/views/components/form/select.blade.php`  
**Responsabilidad:** selector personalizado con Bootstrap Dropdown, input oculto que envía el valor y botón visible con la etiqueta seleccionada.

**Props:**
- `name` (requerido): nombre del campo.
- `options` (array asociativo valor => etiqueta; por defecto vacío).
- `selected` (opcional): valor inicial.
- `placeholder` (por defecto `Selecciona una opción`).
- `id` (opcional; por defecto `name`).
- `disabled` (por defecto `false`).
- `help` (opcional).
- Atributos adicionales se aplican al input oculto.

**Comportamiento importante:**
- Resuelve el valor mediante `old($name, $selected)`.
- El valor de formulario se envía a través del input oculto `id="$id"`.
- El botón visible tiene ID `{$id}_toggle`.
- El menú usa `data-dropdown-option` y `data-value`.
- El listener compartido debe actualizar el input oculto, la etiqueta visible y el estado activo de la opción; debe emitir los eventos que necesiten los consumidores.
- No añadir otro listener local sin verificar primero `resources/views/admin/layout.blade.php`.

**Accesibilidad:** la etiqueta debe apuntar al botón visible `{$id}_toggle`, no al input oculto. El componente actual usa `aria-label` con el nombre técnico del campo; cuando se actualice el componente, preferir una etiqueta accesible derivada de un atributo configurable, sin romper la API existente. Ver también el registro de observaciones.

**Ejemplo:**

```blade
<label for="activo_toggle" class="form-cm__label">Estado</label>
<x-form.select
    name="activo"
    :options="['1' => 'Activo', '0' => 'Inactivo']"
    :selected="(string) (int) $modelo->activo"
    placeholder="Seleccionar estado"
    help="Explica qué implica cambiar el estado." />
```

Si se define `id` explícito, la etiqueta debe apuntar a `id_toggle`. Para opciones cuyos valores son booleanos/enteros, normalizar los valores como cadenas cuando corresponda para que coincidan con el valor enviado.

### 5.6 `x-form.errors`

**Archivo:** `resources/views/components/form/errors.blade.php`  
**Responsabilidad:** resumen superior de todos los errores cuando `$errors->any()`.

**Uso:** colocar cerca del comienzo del formulario, normalmente antes de `x-form.layout` o justo antes del `<form>`, según el diseño de la página. No sustituye los mensajes de error junto a cada campo.

**Atributos:** acepta atributos adicionales y los combina con `form-cm__errors alert alert-danger`.

### 5.7 `x-form.actions`

**Archivo:** `resources/views/components/form/actions.blade.php`  
**Responsabilidad:** contenedor de acciones que añade `.form-cm__actions` y conserva el slot.

**Uso:** agrupar cancelar/volver y enviar; no contiene reglas de rutas ni lógica de guardado.

## 6. Estilos compartidos

**Archivo principal:** `public/css/formulario.css`.

### 6.1 Tokens de referencia

El diseño toma como base los tokens ya presentes en el CSS. Entre ellos:

- `--cm-bg-surface`: superficie de tarjeta.
- `--cm-bg-input`: fondo de controles.
- `--cm-surface-high`: superficie elevada/hover.
- `--cm-border-soft` y `--cm-border-hover`: bordes/sombras sutiles.
- `--cm-focus-shadow`: anillo de foco rojo suave.
- `--cm-primary`: rojo principal de marca.
- `--cm-text-main` y `--cm-text-muted`: jerarquía de texto.

Antes de crear tokens nuevos, buscar si ya existe uno equivalente. No copiar valores hexadecimales en cada formulario.

### 6.2 Clases de referencia

- `.form-cm`: tarjeta del formulario.
- `.form-cm__section`: sección interior.
- `.form-cm__title` / `.form-cm__subtitle`: título y subtítulo.
- `.form-cm__group`: grupo de campo.
- `.form-cm__label`: etiqueta.
- `.form-cm__icon`: icono junto a la etiqueta cuando aplique.
- `.form-cm__control`: input/textarea y base visual del selector.
- `.form-cm__dropdown`, `.form-cm__select-toggle`, `.form-cm__select-menu`: selector personalizado.
- `.form-cm__text`: ayuda.
- `.form-cm__feedback`: error de campo.
- `.form-cm__errors`: resumen general.
- `.form-cm__actions`: acciones del formulario.

No crear selectores CSS específicos para un único formulario si se puede resolver con las clases compartidas. Si un campo necesita una excepción real, documentarla y limitar su alcance.

## 7. Reglas para migrar cada formulario

1. Inspeccionar la vista, controlador, rutas, validaciones, modelo y JavaScript asociado antes de editar.
2. Anotar los campos reales, sus tipos, relaciones, condiciones y reglas de creación/edición.
3. Identificar componentes compartidos existentes y verificar su API antes de usarlos.
4. Migrar primero estructura y clases visuales; conservar nombres de campos, IDs necesarios, rutas, `@csrf`, `@method`, atributos `wire:*`/`x-*` y scripts existentes.
5. Usar `x-form.input`, `x-form.textarea`, `x-form.field`, `x-form.select`, `x-form.errors`, `x-form.layout` y `x-form.actions` donde sean apropiados.
6. Para archivos, fechas, números, relaciones complejas, pestañas o campos condicionales, ampliar el patrón solo después de estudiar el comportamiento específico.
7. Evitar que ayuda/error se dupliquen entre `x-form.field` y el control. Elegir una única fuente para cada mensaje.
8. Comprobar la etiqueta accesible y `aria-describedby` para cada control. El caso del selector personalizado requiere especial atención por la diferencia entre el ID oculto y el botón visible.
9. No modificar lógica de negocio en una tarea de estilo salvo que se acuerde por separado.
10. Actualizar esta directriz y el registro de cambios del módulo en la misma entrega.

## 8. Piloto de referencia: Géneros

**Vista:** `resources/views/admin/generos/form.blade.php`.

El piloto utiliza:
- `x-form.errors` para el resumen de errores.
- `x-form.layout` para la tarjeta principal.
- `x-form.field` + `x-form.input` para el nombre.
- `x-form.field` + `x-form.textarea` para la descripción.
- `x-form.select` para el estado Activo/Inactivo.
- `x-form.actions` para Cancelar y Registrar/Guardar cambios.
- `.form-cm__section` y la cuadrícula Bootstrap para estructura responsiva.

Se preservan las rutas `admin.generos.store`, `admin.generos.update`, `admin.generos.index`, CSRF y `PUT` en edición. El estado conserva los valores `1` y `0`; la desactivación no debe eliminar el registro ni sus relaciones.

El usuario confirmó que el resultado visual del piloto se ve bien. La validación visual positiva no sustituye las pruebas de creación, edición, errores y persistencia en el entorno Laravel.

## 9. Lista de verificación obligatoria por formulario

### Revisión previa
- [ ] Revisé la vista, rutas, controlador, modelo y validaciones pertinentes.
- [ ] Identifiqué dependencias JavaScript y componentes compartidos.
- [ ] Registré campos, reglas y comportamientos que deben preservarse.

### Implementación
- [ ] Usé `x-form.*` donde correspondía.
- [ ] Reutilicé `x-form.select` en desplegables compatibles.
- [ ] No dupliqué CSS ni listeners existentes.
- [ ] Conservé rutas, método HTTP, CSRF y nombres de campo.
- [ ] Conservé valores anteriores, valores de edición y errores.
- [ ] Revisé etiquetas, IDs, foco, `aria-describedby` y estados disabled/invalid.
- [ ] Documenté componentes usados, archivos modificados, excepciones y motivo.

### Verificación
- [ ] Revisé sintaxis Blade/PHP con las herramientas disponibles.
- [ ] Probé registro válido.
- [ ] Probé errores de validación.
- [ ] Probé edición con datos existentes.
- [ ] Probé selectores y valores enviados.
- [ ] Probé cancelar/regresar y rutas.
- [ ] Revisé pantalla estrecha/responsive.
- [ ] Ejecuté pruebas automatizadas disponibles o declaré por qué no fue posible.
- [ ] Registré los resultados reales; no afirmar pruebas no ejecutadas.

## 10. Plantilla para documentar cada migración

Añadir una sección por módulo en el registro de cambios del proyecto, usando este formato:

```markdown
### [AAAA-MM-DD] — Formulario: [Módulo]

**Estado:** pendiente / implementado / validado por usuario.

**Archivos modificados:**
- `ruta/al/archivo.blade.php` — qué cambió y por qué.
- `ruta/al/archivo.css` — reglas añadidas/modificadas, si corresponde.
- `ruta/al/componente.blade.php` — API o comportamiento afectado, si corresponde.

**Componentes reutilizados:**
- `x-form.layout` — función en este formulario.
- `x-form.field` — campos donde se aplicó.
- `x-form.input` — campos donde se aplicó.
- `x-form.textarea` — campos donde se aplicó.
- `x-form.select` — campos donde se aplicó y opciones configuradas.
- `x-form.errors` — ubicación.
- `x-form.actions` — acciones incluidas.

**Comportamiento preservado:** rutas, métodos HTTP, validaciones, relaciones, carga de archivos, scripts y condiciones relevantes.

**Excepciones y observaciones:** qué no se pudo unificar y por qué; defectos encontrados; deuda técnica.

**Pruebas ejecutadas:** comandos y resultados concretos.

**Pruebas pendientes:** lista explícita.

**Decisiones nuevas para la directriz:** cambios que deban incorporarse a este documento.
```

No enumerar componentes que no se usaron como si se hubieran aplicado: registrar únicamente los componentes realmente utilizados y explicar cualquier excepción.

## 11. Registro de observaciones y evolución del patrón

Mantener esta sección actualizada en cada migración. Una observación nueva no implica cambiar inmediatamente un componente compartido: primero buscar todos sus usos y evaluar compatibilidad.

### O-001 — Etiqueta del selector personalizado

**Hallazgo:** `x-form.select` usa un input oculto con ID `name` y un botón visible con ID `name_toggle`. Una etiqueta `for="name"` no apunta al control interactivo visible.

**Regla provisional:** asociar la etiqueta a `name_toggle` (o `id_toggle` si se proporciona `id`) y comprobar el nombre accesible del botón.

**Mejora futura compatible:** considerar una prop `label`/`ariaLabel` para `x-form.select` y construir el `aria-label` con texto legible; al implementarla, buscar todos los usos del selector y probarlos.

### O-002 — Ayuda y error duplicados

**Hallazgo:** `x-form.field` puede imprimir ayuda/error y `x-form.input`, `x-form.textarea` y `x-form.select` también pueden mostrar errores o ayuda. Si se anidan sin criterio, pueden aparecer mensajes duplicados.

**Regla:** por campo, escoger una sola fuente para la ayuda y una sola para el error. Para `x-form.input` y `x-form.textarea`, usar `:show-error="false"` en `x-form.field`, ya que el control gestiona el error y el ID usado por `aria-describedby`. Para `x-form.select`, mantener el grupo manual con etiqueta dirigida a `id_toggle`, porque el selector gestiona internamente su ayuda y error.

**Decisión en versión 1.1:** se añadió la prop compatible `showError` a `x-form.field`, con valor predeterminado `true`, para evitar duplicaciones sin alterar los usos existentes.

### O-004 — Formularios integrados y vistas independientes con redirección

**Hallazgo:** algunas rutas CRUD conservan vistas independientes históricas, pero sus métodos `index`, `create` o `edit` redirigen a una pestaña del editor principal. Migrar solo la vista histórica no necesariamente modifica la interfaz que utiliza el usuario.

**Regla:** antes de aplicar el patrón compartido, seguir las rutas hasta la vista efectiva. Si la interfaz activa está integrada en otra página, migrar el formulario real y documentar por qué se deja intacta la vista histórica. En componentes con modales, búsquedas asíncronas o formularios anidados, conservar IDs, nombres de campo, selectores JavaScript, endpoints, atributos Bootstrap y respuestas JSON.

**Aplicación inicial:** Créditos se edita/asigna desde la pestaña `creditos` de `resources/views/admin/peliculas/form.blade.php`. La vista `resources/views/admin/creditos/form.blade.php` no se modifica en esta etapa porque los métodos de entrada inspeccionados redirigen al editor integrado.

### O-003 — Estilos repetidos de ayuda/error en `formulario.css`

**Hallazgo:** las reglas de `.form-cm__text` y `.form-cm__feedback` aparecen más de una vez, con diferencias de tamaño/color.

**Regla:** no añadir más duplicados. Al hacer una limpieza CSS específica, consolidar las reglas en una única definición sin alterar el aspecto aprobado; revisar pantallas de películas y Géneros.

### O-005 — Selector de relación con estado vacío

**Hallazgo:** los formularios que asignan relaciones deben presentar opciones legibles, recuperar el valor anterior y explicar cuando no existen registros disponibles.

**Regla:** usar `x-form.select` cuando las opciones y el listener global sean compatibles. Asociar la etiqueta al botón visible (`{nombre}_toggle`), evitar mensajes de error duplicados con `x-form.field` mediante `showError=false` cuando el selector ya representa el error, y deshabilitar el selector y el envío cuando no existan opciones. Mostrar también un mensaje explicativo accesible. No duplicar CSS ni JavaScript si el patrón compartido ya resuelve el comportamiento.



### O-007 — URLs públicas de archivos locales y estilos completos de Multimedia

**Hallazgo:** `Storage::disk('public')->url()` puede construir una URL usando `APP_URL` (por ejemplo, `localhost`) en entornos donde la aplicación se sirve desde otro host. El formulario de Personas ya construye la URL pública local mediante `asset('storage/' . $ruta)` para rutas relativas de almacenamiento.

**Regla:** cuando se renderice una imagen guardada como ruta relativa dentro del disco público, seguir el patrón del formulario de Personas: detectar el prefijo de carpeta esperado y construir la URL con `asset('storage/' . $ruta)`. Si el valor guardado es una URL externa, conservarla sin modificarla. No transformar rutas locales en el campo destinado a URLs externas.

**Multimedia:** aplicar tokens y clases `form-cm__*` a etiquetas, controles, ayudas, errores, secciones y vista previa. Se puede usar `x-form.select` para el tipo si se conserva el evento `change` que necesita la lógica de vista previa; la etiqueta debe apuntar al botón visible `tipo_toggle`. Centralizar en `formulario.css` los estilos de la vista previa y del botón de selección de archivo. No cambiar la lógica de validación de la fuente, el tipo del recurso ni los contratos de rutas.

### O-006 — Ciclo de vida de imágenes en formularios

**Aplicación:** Personas, Multimedia y Productos (2026-10-25).

- Diferenciar visualmente la imagen persistida del archivo nuevo seleccionado en el navegador. Una vista previa local no implica que el archivo esté guardado.
- En edición, mostrar la imagen persistida y aclarar que se conserva cuando no se selecciona un reemplazo.
- Mostrar vista previa local con URL de objeto temporal y permitir descartar la selección para volver a la imagen guardada; liberar URLs de objeto al cambiar o abandonar la página.
- No intentar restaurar un archivo seleccionado tras una respuesta de validación: los navegadores no permiten rellenar de forma segura inputs de tipo file. Mostrar la imagen previamente guardada y permitir volver a seleccionar el archivo.
- Para Personas, la fuente puede ser archivo local o URL HTTPS directa, nunca ambas a la vez. Para Multimedia, la vista previa depende del tipo (imagen frente a video de YouTube) y el servidor conserva la validación de fuente. Para Productos, solo se acepta archivo local.
- Al reemplazar una imagen persistida, conservar el archivo anterior hasta que la actualización del registro se haya guardado correctamente. No borrar la imagen actual solo porque el usuario seleccione otra en el navegador.
- No introducir eliminación de imagen mediante el botón «Descartar archivo seleccionado»: ese control solo cancela la selección nueva; no borra la imagen persistida.

## 12. Política de actualización

Cada vez que una observación se convierta en una decisión:

1. Buscar los usos actuales de los componentes y clases afectados.
2. Explicar el problema y la solución propuesta antes de un cambio global con riesgo.
3. Actualizar la versión/fecha de esta directriz.
4. Añadir la decisión al registro de cambios.
5. Documentar cada archivo y componente modificado.
6. Probar el piloto de Géneros y los demás consumidores directos del componente afectado.
7. Registrar resultados y pendientes sin afirmar validación no ejecutada.

**Regla central:** unificar presentación, no borrar diferencias funcionales. Reutilizar primero, extender de forma compatible cuando sea necesario y documentar cada decisión.

---

### Validación manual — Asientos, Compradores y Funciones (2026-10-25)

El usuario confirmó como aprobados los casos funcionales y visuales de los tres formularios: alta, validaciones, duplicados/solapamientos, edición y persistencia, catálogos vacíos, navegación, mensajes de error y diseño responsive. Esta aprobación corresponde a pruebas manuales reportadas por el usuario; no equivale a renderizado Blade ni a ejecución de pruebas automatizadas.

### 1.9 — Corrección de URLs de imagen y estilos de Multimedia (2026-10-25)

Se corrige la URL de la imagen guardada de Productos para usar el mismo patrón que Personas (`asset('storage/...')` para rutas locales). Se completa el estilo compartido de Multimedia y se integra su selector de tipo con `x-form.select`, preservando los eventos de vista previa.

### 1.8 — Personas, Multimedia y Productos (2026-10-25)

Se documenta el ciclo de vida de imágenes y archivos: imagen actual, vista previa local, descarte de selección, URL de imagen en Personas, vista previa según tipo de Multimedia y reemplazo seguro en Productos. Se añade `x-form.image-upload` como componente compartido.

## 13. Historial de versiones

| Versión | Fecha | Cambios |
|---|---|---|
| 1.0 | 2026-10-25 | Primera directriz formal basada en Películas, `formulario.css` y el piloto de Géneros; catálogo de componentes, reglas de migración, checklist y registro de observaciones. |
| 1.1 | 2026-10-25 | Documenta `x-form.field showError` para evitar errores duplicados al combinarlo con controles que ya renderizan sus mensajes; registra la migración de Idiomas en el registro del proyecto. |
| 1.2 | 2026-10-25 | Registra la aplicación del patrón compartido a Países y Categorías de producto; refuerza la preservación de restricciones HTML, estado administrado fuera del formulario y ayudas accesibles. |
| 1.3 | 2026-10-25 | Registra la validación funcional manual comunicada por el usuario para Países y Categorías de producto; separa los casos aprobados de las comprobaciones visuales y automatizadas que aún no constan como ejecutadas. |
| 1.4 | 2026-10-25 | Inicia la migración de Créditos dentro del editor de películas; documenta la prioridad de la interfaz activa sobre una vista independiente que redirige al editor, y la preservación de buscador, modal, creación rápida y sincronización de roles. |
| 1.5 | 2026-10-25 | Registra la migración de Países de películas al patrón compartido, incluido el selector de relación, el estado vacío accesible y la aprobación manual comunicada para Créditos. |
| 1.6 | 2026-10-25 | Migra los formularios de Asientos, Compradores y Funciones al patrón compartido, conservando campos, rutas, restricciones, selección de relaciones y estados vacíos. |
| 1.7 | 2026-10-25 | Registra la aprobación funcional y visual comunicada por el usuario para Asientos, Compradores y Funciones; deja explícitamente pendientes el renderizado Blade y las pruebas automatizadas Laravel, que no se ejecutaron en este entorno. |
| 1.8 | 2026-10-25 | Incorpora el ciclo de vida de imágenes y el componente `x-form.image-upload` para Personas, Multimedia y Productos. |
| 1.9 | 2026-10-25 | Corrige la URL pública de imágenes de Productos para evitar depender de `APP_URL`/localhost y completa la aplicación del sistema visual compartido en Multimedia. |
