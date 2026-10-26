# CINEPREMIUM — Registro de migraciones de formularios administrativos

Este registro complementa `Directriz_patron_compartido_formularios_CINEPREMIUM.md`. Documenta los componentes usados, archivos modificados, comportamiento preservado y pruebas reales de cada migración.


## 2026-10-25 — Formulario: Idiomas

**Estado:** implementado; pendiente de validación funcional en un entorno Laravel con dependencias instaladas.

**Archivos modificados:**
- `resources/views/admin/idiomas/form.blade.php` — migrado al encabezado visual del patrón, resumen general de errores, contenedor compartido, sección responsiva, campos y acciones comunes.
- `resources/views/components/form/field.blade.php` — añadida la prop opcional `showError` (predeterminada `true`) para permitir una única salida del error por campo.
- `docs/Directriz_patron_compartido_formularios_CINEPREMIUM.md` — versión 1.1; documenta la nueva prop y concreta la regla para evitar duplicación de errores.
- `docs/Registro_migraciones_formularios_CINEPREMIUM.md` — registro de esta migración.

**Componentes reutilizados:**
- `x-form.errors` — resumen de errores antes de la tarjeta.
- `x-form.layout` — tarjeta «Información del idioma».
- `x-form.field` + `x-form.input` — `codigo` y `nombre`; `showError=false` evita duplicar los mensajes que muestran los controles.
- `x-form.select` — estado `activo`, con opciones de valor `1` (Activo) y `0` (Inactivo), etiqueta asociada al botón visible `activo_toggle`.
- `x-form.actions` — acciones Cancelar y Guardar.
- `.form-cm__section` y cuadrícula Bootstrap — agrupación y distribución adaptable.

**Comportamiento preservado:**
- Acción condicional `admin.idiomas.store` / `admin.idiomas.update`.
- `@csrf` y `@method('PUT')` solo en edición.
- Campos `codigo`, `nombre` y `activo`, incluidos límites visibles de 15 y 100 caracteres.
- Valores anteriores mediante `old()` en los controles compartidos y selección de estado.
- Rutas de regreso `admin.idiomas.index`; textos de crear/editar.
- Validaciones de servidor, unicidad, formato BCP 47, conversión de `activo`, mensajes de sesión y persistencia permanecen en el controlador sin cambios.

**Excepciones y observaciones:**
- Se mantuvo el selector personalizado para Estado, en lugar de un `<select>` nativo, según la directriz.
- La prop `showError` es una ampliación compatible: los usos existentes conservan el comportamiento por defecto.
- No se modificaron controlador, modelo, rutas, CSS global ni listener JavaScript.

**Pruebas ejecutadas:**
- Inspección de vista, controlador, modelo, rutas, componente compartido y listener de dropdown.
- Revisión estática de los archivos modificados y comprobación de que el entorno de trabajo no contiene `vendor/`.

**Pruebas pendientes:**
- Renderizado Blade/Laravel y `php artisan view:cache`.
- Registro válido, validación de campos y unicidad, edición de un idioma existente y persistencia.
- Confirmar visualmente selector personalizado, errores y diseño en ancho móvil.
- Ejecutar la suite automatizada pertinente en el entorno completo del proyecto.


## 2026-10-25 — Formulario: Países

**Estado:** implementado; pendiente de validación funcional y visual por el usuario en el entorno Laravel.

**Archivos modificados:**
- `resources/views/admin/paises/form.blade.php` — migrado desde panel y controles Bootstrap individuales al patrón compartido.
- `docs/Directriz_patron_compartido_formularios_CINEPREMIUM.md` — versión 1.2; registra la aplicación del patrón a Países y Categorías de producto.
- `docs/Registro_migraciones_formularios_CINEPREMIUM.md` — registro de la migración.

**Componentes reutilizados:**
- `x-form.errors` — resumen general de validación.
- `x-form.layout` — tarjeta «Información del país».
- `x-form.field` + `x-form.input` — campos `codigo` y `nombre`, con ayudas y errores junto a cada control.
- `x-form.actions` — Cancelar y Registrar/Guardar cambios.
- `.form-cm__section` y cuadrícula Bootstrap — distribución responsiva.

**Comportamiento preservado:**
- Acción condicional `admin.paises.store` / `admin.paises.update`, con el código del país como clave de edición.
- Ruta `admin.paises.index` para volver y cancelar.
- `@csrf` y `@method('PUT')` solo al editar.
- Campos `codigo` y `nombre`; código de exactamente dos letras mayúsculas (`maxlength`, `minlength`, `pattern`) y nombre con máximo 100 caracteres.
- Valores del modelo y recuperación de `old()` por los componentes compartidos.
- Validaciones de servidor, unicidad, normalización a mayúsculas/trim, persistencia y relaciones permanecen en el controlador/modelo sin cambios.

**Excepciones y observaciones:**
- No se añadió selector de estado: el modelo País no contiene atributo `activo`.
- Se preservó la transformación visual a mayúsculas del código mediante el atributo `style` que admite el componente `x-form.input`. La validación del servidor sigue siendo la autoridad.
- No se modificaron controlador, modelo, rutas, CSS global ni JavaScript.

**Pruebas ejecutadas:**
- Inspección de vista, controlador, modelo y rutas pertinentes.
- Revisión estática de que la vista conserva campos, nombres, rutas, método HTTP y restricciones del formulario original.
- Confirmación de que el entorno de trabajo no incluye `vendor/`; no se pudo ejecutar Laravel.

**Pruebas pendientes:**
- Renderizado Blade y `php artisan view:cache`.
- Registro y edición de país, errores de formato y unicidad, y persistencia.
- Comprobación visual en navegador y móvil.

## 2026-10-25 — Formulario: Categorías de producto

**Estado:** implementado; pendiente de validación funcional y visual por el usuario en el entorno Laravel.

**Archivos modificados:**
- `resources/views/admin/categorias-producto/form.blade.php` — migrado al patrón compartido manteniendo los avisos específicos del estado.
- `docs/Directriz_patron_compartido_formularios_CINEPREMIUM.md` — versión 1.2.
- `docs/Registro_migraciones_formularios_CINEPREMIUM.md` — registro de la migración.

**Componentes reutilizados:**
- `x-form.errors` — resumen general de validación.
- `x-form.layout` — tarjeta «Información de la categoría».
- `x-form.field` + `x-form.input` — campo obligatorio `nombre`.
- `x-form.field` + `x-form.textarea` — campo opcional `descripcion`.
- `x-form.actions` — Cancelar y Registrar/Guardar cambios.
- `.form-cm__section` y cuadrícula Bootstrap — distribución responsiva.

**Comportamiento preservado:**
- Acción condicional `admin.categorias-producto.store` / `admin.categorias-producto.update`, pasando el modelo `categoriaProducto` al actualizar.
- Ruta `admin.categorias-producto.index`, CSRF y método `PUT` en edición.
- Campo `nombre` obligatorio, máximo 80 caracteres y validación de unicidad en servidor.
- Campo `descripcion` opcional, máximo 255 caracteres y cuatro filas visibles.
- Recuperación de valores anteriores y datos del modelo mediante los componentes compartidos.
- El estado no se edita desde este formulario: se mantienen los avisos de creación activa por defecto y de edición gestionada desde el listado. No se modifica el controlador ni el flujo Livewire del listado.

**Excepciones y observaciones:**
- No se añadió selector de estado ni se alteró su operación, porque el controlador documenta que el estado se administra desde el listado y no en este formulario.
- Se conserva `autofocus` en el nombre.
- No se modificaron controlador, modelo, rutas, CSS global ni JavaScript.

**Pruebas ejecutadas:**
- Inspección de vista, controlador, modelo y rutas pertinentes.
- Revisión estática de campos, nombres, acciones, método HTTP, límites de caracteres y avisos condicionales.
- Confirmación de que el entorno de trabajo no incluye `vendor/`; no se pudo ejecutar Laravel.

**Pruebas pendientes:**
- Renderizado Blade y `php artisan view:cache`.
- Crear categoría válida, probar nombre duplicado y descripción de longitud límite.
- Editar categoría existente y confirmar que el estado no cambia desde el formulario.
- Comprobación visual en navegador y móvil.


## 2026-10-25 — Validación funcional del usuario: Países y Categorías de producto

**Estado global:** aprobado funcionalmente por el usuario para los casos enumerados. No se reportaron fallos en las comprobaciones realizadas. Esta aprobación se limita a los escenarios indicados y no equivale a una certificación completa de accesibilidad, diseño responsivo o pruebas automatizadas.

### Países — aprobado

**Comprobaciones realizadas por el usuario:**
- [x] Registrar un país con código válido (por ejemplo, `CL`), si no existía previamente.
- [x] Probar un código inválido y un nombre duplicado.
- [x] Editar un país existente y comprobar que los cambios se guardan.

**Resultado:** el usuario marcó los tres casos como realizados y no comunicó problemas. Se considera validado el flujo manual de registro, rechazo de datos inválidos/duplicados y edición/persistencia en su entorno.

### Categorías de producto — aprobado

**Comprobaciones realizadas por el usuario:**
- [x] Registrar una categoría con nombre y descripción.
- [x] Probar un nombre duplicado y una descripción de más de 255 caracteres.
- [x] Editar una categoría y verificar que el estado no cambia desde el formulario.

**Resultado:** el usuario marcó los tres casos como realizados y no comunicó problemas. Se considera validado el flujo manual de registro, validación de unicidad y longitud, edición y preservación del estado fuera del formulario.

### Problemas y comprobaciones pendientes

- **Problemas reportados:** ninguno en los seis casos comunicados.
- **No consta todavía como probado:** diseño en pantallas estrechas, acciones de cancelar/regresar y navegación, revisión de accesibilidad, renderizado mediante `php artisan view:cache` y pruebas automatizadas Laravel. Mantener estos puntos como pendientes hasta que se ejecuten; en la preparación previa no se pudo ejecutar Laravel porque el entorno de trabajo no tenía `vendor/`.
- **Alcance de la aprobación:** validación manual realizada por el usuario en su entorno; no se infieren resultados de pruebas que no fueron mencionadas.


## 2026-10-25 — Formulario integrado: Créditos de películas

**Estado:** validado funcionalmente por el usuario; todas las pruebas manuales que reportó fueron aprobadas.

**Archivos modificados:**
- `resources/views/admin/peliculas/form.blade.php` — aplicado el patrón compartido al formulario de edición de crédito y a campos de la asignación desde modal y creación rápida de personas.
- `docs/Directriz_patron_compartido_formularios_CINEPREMIUM.md` — versión 1.4; nueva observación O-004 para formularios integrados y vistas históricas que redirigen.
- `docs/Registro_migraciones_formularios_CINEPREMIUM.md` — registro de esta etapa y de sus pendientes.

**Componentes reutilizados:**
- `x-form.field` + `x-form.input` — personaje y orden tanto en edición como en asignación; nombre completo, alias y URL de foto en el registro rápido.
- `x-form.select` — rol en edición, asignación de persona existente y creación rápida; etiqueta dirigida al botón visible `credito_rol_toggle` en el formulario de edición.
- `x-form.actions` — acciones del formulario de edición y del formulario de asignación en modal.
- Clases `.form-cm`, `.form-cm__section`, `.form-cm__group`, `.form-cm__title`, `.form-cm__subtitle` y `.form-cm__text` — estructura, ayudas y consistencia visual.

**Comportamiento preservado:**
- La pestaña `creditos` del editor de películas sigue siendo la interfaz de trabajo.
- Se mantienen rutas de creación/actualización/eliminación, métodos HTTP, `@csrf`, `@method('PUT')` y la ruta de regreso al editor.
- Se mantienen roles permitidos (`DIRECTOR`, `ACTOR`, `GUIONISTA`, `PRODUCTOR`, `COMPOSITOR`), `personaje` con máximo 150, `orden` entero entre 0 y 65535, búsqueda de personas, selección por ID y validaciones del servidor.
- Se conservan IDs y nombres consumidos por JavaScript, la búsqueda asíncrona, pestañas Bootstrap del modal, creación rápida, respuesta JSON y sincronización del rol entre los formularios.
- Se conserva el estado vacío, tabla, paginación y acciones de edición/eliminación de créditos.

**Excepciones y observaciones:**
- No se migró `resources/views/admin/creditos/form.blade.php`: el controlador redirige los flujos `index`, `create` y `edit` a la pestaña integrada del editor, por lo que esa vista no parece ser el flujo activo normal. No se borró ni alteró.
- No se cambió controlador, modelo, rutas, CSS global ni lógica de negocio.
- La asignación usa un campo de búsqueda con un input oculto para el ID; no se reemplazó por un selector genérico porque perdería la búsqueda asíncrona y el comportamiento de selección.

**Pruebas ejecutadas:**
- Inspección estática de `resources/views/admin/peliculas/form.blade.php`, la vista histórica de créditos, `PeliculaCreditoAdminController`, rutas y componentes `x-form.*`.
- Revisión estática de que las rutas, nombres de campos, IDs clave del JavaScript y límites HTML se mantienen.
- El usuario confirmó que todas las pruebas manuales realizadas en su entorno funcionaron y fueron aprobadas.

**Pruebas pendientes:**
- Renderizar la vista con Laravel y ejecutar `php artisan view:cache` en un entorno con dependencias instaladas.
- Ejecutar la suite automatizada pertinente en el entorno completo del proyecto; no se afirma que se haya ejecutado en esta sesión.

**Decisiones nuevas para la directriz:**
- Aplicar O-004: seguir el flujo real de rutas antes de decidir qué vista migrar; en formularios integrados conservar expresamente IDs, atributos Bootstrap y contratos de JavaScript/JSON.
## 2026-10-25 — Formulario: Países de películas

**Estado:** implementado; pendiente de validación funcional y visual por el usuario.

**Archivo modificado:**
- `resources/views/admin/paises-peliculas/form.blade.php` — migrado del formulario Bootstrap aislado a la estructura compartida de formularios administrativos.

**Componentes reutilizados:**
- `x-form.errors` — resumen general de validación.
- `x-form.layout` — tarjeta «País de producción».
- `x-form.field` — etiqueta y ayuda del campo `codigo_pais`; `showError=false` evita duplicar el error que representa el selector.
- `x-form.select` — selector personalizado con nombre y código del país, valores anteriores y estado deshabilitado cuando el catálogo está vacío.
- `x-form.actions` — acciones Cancelar y Asignar país.
- `.form-cm__section` y cuadrícula Bootstrap — estructura responsiva.

**Comportamiento preservado:**
- Acción `admin.peliculas.paises.store`, método `POST` y token CSRF.
- Navegación Volver/Cancelar mediante `admin.peliculas.paises.index` con la película correspondiente.
- Campo `codigo_pais` obligatorio y validación de existencia del país en servidor.
- Prevención de asociaciones duplicadas película-país.
- Mensaje de estado vacío cuando no hay países disponibles; selector y botón de envío deshabilitados en ese caso.
- No se modificaron controlador, modelo, rutas, consultas ni reglas de negocio.

**Excepciones y observaciones:**
- Se reemplazó el `<select>` nativo por `x-form.select` porque el catálogo se entrega como opciones simples y el listener compartido sincroniza el valor oculto con el botón visible.
- La etiqueta apunta a `codigo_pais_toggle`, el botón visible del selector, siguiendo O-001.
- No se cambió el flujo de búsqueda ni creación rápida del país que existe en la pestaña Producción del editor; esta migración afecta solo al formulario independiente de asignación.

**Pruebas ejecutadas:**
- Inspección de la vista, `PeliculaPaisAdminController`, las rutas pertinentes y los componentes `x-form`.
- Revisión estática de ruta de envío, CSRF, nombre del campo, valor anterior, errores, estado vacío y condición de deshabilitado.

**Pruebas pendientes:**
- Renderizado Blade/Laravel y `php artisan view:cache`.
- Asignar un país válido a una película y verificar persistencia.
- Intentar asignar un país ya asociado y confirmar mensaje de validación.
- Comprobar que una selección inválida no se persiste.
- Probar el catálogo vacío, selector personalizado, cancelar/regresar y diseño responsive.
- Ejecutar pruebas automatizadas pertinentes en el entorno con dependencias instaladas.

**Decisiones para la directriz:** aplicar O-005: en selectores de relaciones, preservar valores anteriores, apuntar la etiqueta al botón visible, evitar errores duplicados y ofrecer un estado vacío accesible que deshabilite el envío.


## 2026-10-25 — Formulario: Asientos

**Estado:** validado funcional y visualmente por el usuario; pruebas manuales reportadas como aprobadas.

**Archivos modificados:**
- `resources/views/admin/asientos/form.blade.php` — migrado a la tarjeta, campos, selector y acciones compartidos.
- `docs/Directriz_patron_compartido_formularios_CINEPREMIUM.md` — versión 1.7; registra esta etapa y su validación manual posterior.
- `docs/Registro_migraciones_formularios_CINEPREMIUM.md` — registro de la migración y sus pendientes.

**Componentes reutilizados:**
- `x-form.errors` — resumen de validación.
- `x-form.layout` — tarjeta «Información del asiento».
- `x-form.field` + `x-form.input` — fila y número.
- `x-form.field` + `x-form.select` — sala, tipo y disponibilidad.
- `x-form.actions` — Cancelar y Registrar/Guardar cambios.
- `.form-cm__section` y cuadrícula Bootstrap — agrupación adaptable.

**Comportamiento preservado:** rutas `admin.asientos.store/update/index`, `POST` y `PUT` en edición, CSRF, campos `id_sala`, `fila`, `numero`, `tipo` y `activo`, valores anteriores, tipo por defecto `ESTANDAR`, disponibilidad predeterminada activa, límites de fila y número, y bloqueo del envío cuando no hay salas.

**Excepciones y observaciones:** la selección de sala usa el selector compartido con el nombre de la sala; el catálogo vacío muestra aviso accesible y deshabilita selección y envío. No se modificaron controlador, modelo, rutas, CSS global ni JavaScript.

**Pruebas ejecutadas:** inspección estática de la vista original y migrada; revisión de nombres de campo, rutas, métodos, CSRF, restricciones y estados vacíos.

**Pruebas manuales aprobadas por el usuario:**
- Alta válida y aparición en listado; rechazo de combinación sala/fila/número duplicada; edición y persistencia de disponibilidad; estado sin salas; navegación; errores sin duplicación; diseño responsive.

**Pruebas pendientes:** renderizado Blade, `php artisan view:cache` y pruebas automatizadas Laravel. No se ejecutaron porque el directorio `vendor/` no está presente en este entorno.

## 2026-10-25 — Formulario: Compradores

**Estado:** validado funcional y visualmente por el usuario; pruebas manuales reportadas como aprobadas.

**Archivos modificados:**
- `resources/views/admin/compradores/form.blade.php` — migrado a los componentes de formulario compartidos.
- `docs/Directriz_patron_compartido_formularios_CINEPREMIUM.md` — versión 1.7.
- `docs/Registro_migraciones_formularios_CINEPREMIUM.md` — registro de la migración.

**Componentes reutilizados:**
- `x-form.errors` — resumen de validación.
- `x-form.layout` — tarjeta «Información del comprador».
- `x-form.field` + `x-form.input` — nombre, correo y teléfono.
- `x-form.field` + `x-form.select` — tipo de comprador (`INVITADO`/`REGISTRADO`).
- `x-form.actions` — Cancelar y Registrar/Guardar cambios.
- `.form-cm__section` y cuadrícula Bootstrap — distribución adaptable.

**Comportamiento preservado:** rutas `admin.compradores.store/update/index`, `POST` y `PUT` en edición, CSRF, campos `nombre`, `correo`, `telefono` y `tipo_comprador`, máximos 100/150/30, nombre obligatorio con autofocus, correo obligatorio de tipo email, teléfono opcional, etiquetas Invitado/Registrado y valor predeterminado `INVITADO`. Se mantiene explícita la nota de que el correo no está obligado a ser único.

**Excepciones y observaciones:** no se agregó una regla de unicidad de correo ni se cambió lógica de negocio. No se modificaron controlador, modelo, rutas, CSS global ni JavaScript.

**Pruebas ejecutadas:** inspección estática de la vista original y migrada; revisión de campos, restricciones, valores predeterminados, rutas y método HTTP.

**Pruebas manuales aprobadas por el usuario:**
- Alta con correo válido y teléfono opcional; validación de correo y longitudes; edición y persistencia del tipo; navegación; errores sin duplicación; diseño responsive.

**Pruebas pendientes:** renderizado Blade, `php artisan view:cache` y pruebas automatizadas Laravel. No se ejecutaron porque el directorio `vendor/` no está presente en este entorno.

## 2026-10-25 — Formulario: Funciones

**Estado:** validado funcional y visualmente por el usuario; pruebas manuales reportadas como aprobadas.

**Archivos modificados:**
- `resources/views/admin/funciones/form.blade.php` — migrado al patrón compartido, con selectores para películas/salas y campos de programación.
- `docs/Directriz_patron_compartido_formularios_CINEPREMIUM.md` — versión 1.7.
- `docs/Registro_migraciones_formularios_CINEPREMIUM.md` — registro de la migración.

**Componentes reutilizados:**
- `x-form.errors` — resumen general de errores.
- `x-form.layout` — tarjeta «Programación de la función».
- `x-form.field` + `x-form.select` — película, sala, formato y estado.
- `x-form.field` + `x-form.input` — inicio, fecha/hora local y precio base.
- `x-form.actions` — Cancelar y Registrar/Guardar cambios.
- `.form-cm__section` y cuadrícula Bootstrap — secciones responsivas.

**Comportamiento preservado:** rutas `admin.funciones.store/update/index`, `POST`/`PUT`, CSRF, campos `id_pelicula`, `id_sala`, `inicio`, `formato`, `precio_base` y `estado`; formato de fecha `Y-m-d\\TH:i`; valores anteriores y predeterminados (`2D`, `PROGRAMADA`); etiquetas de películas con duración y salas con estado/margen de limpieza; mensaje de sesión `error`; avisos para catálogos vacíos y envío deshabilitado si faltan películas o salas. La lógica de duración, limpieza, solapamientos y reglas de negocio del servidor no se modificó.

**Excepciones y observaciones:** se conserva el tipo `datetime-local` mediante `x-form.input`; los selectores de relación muestran los datos contextuales existentes. No se modificaron controlador, modelo, rutas, CSS global ni JavaScript.

**Pruebas ejecutadas:** inspección estática de la vista original y migrada; revisión de campos, rutas, métodos, CSRF, formato de fecha, valores por defecto, mensajes y condiciones de deshabilitado.

**Pruebas manuales aprobadas por el usuario:**
- Alta válida; validación de fechas, precio y selecciones; rechazo de solapamientos según las reglas existentes; estados sin películas/salas; edición y persistencia; navegación; errores sin duplicación; diseño responsive.

**Pruebas pendientes:** renderizado Blade, `php artisan view:cache` y pruebas automatizadas Laravel. No se ejecutaron porque el directorio `vendor/` no está presente en este entorno.


## 2026-10-25 — Formularios: Personas, Multimedia y Productos

**Estado:** implementación preparada; pendiente de validación funcional y visual por el usuario. No se han ejecutado pruebas automatizadas de Laravel ni renderizado Blade en este entorno.

**Archivos modificados:**
- `resources/views/components/form/image-upload.blade.php` — nuevo componente compartido para subida de imagen, vista previa local, imagen persistida y descarte de la selección temporal.
- `resources/views/admin/personas/form.blade.php` — migrado al patrón compartido y conectado al componente de imagen; conserva alternativa de URL HTTPS.
- `resources/views/admin/multimedia/form.blade.php` — unificado el contenedor/errores con el patrón, retirada la vista redundante de imagen actual y añadido control para descartar el archivo seleccionado sin perder la vista previa de la fuente persistida.
- `resources/views/admin/productos/form.blade.php` — migrado al patrón compartido y conectado al componente de imagen.
- `app/Http/Controllers/admin/ProductoAdminController.php` — el reemplazo ahora guarda la nueva imagen, actualiza el producto y elimina la anterior solo después de actualizar correctamente; si falla la actualización, intenta limpiar el archivo nuevo.
- `docs/Directriz_patron_compartido_formularios_CINEPREMIUM.md` — versión 1.8; documenta O-006 sobre el ciclo de vida de imágenes.
- `docs/Registro_migraciones_formularios_CINEPREMIUM.md` — registro de esta etapa.

**Comportamiento de archivos acordado:**
- En creación sin imagen, no se muestra una imagen ficticia como si estuviera guardada.
- Al seleccionar un archivo, se muestra una vista previa local y el nombre del archivo con el estado «aún no está guardado».
- «Descartar archivo seleccionado» limpia únicamente el input temporal y restaura la imagen persistida si existe.
- En edición, si no se selecciona reemplazo, la imagen actual se conserva.
- Personas admite archivo o URL HTTPS, nunca ambas fuentes; el controlador mantiene esa validación.
- Multimedia conserva su lógica de fuentes por tipo, la vista previa de imágenes/videos y sus reglas de negocio; el botón nuevo descarta la selección temporal.
- Productos acepta JPG/PNG/WebP hasta 5 MB. La imagen anterior se elimina solo después de actualizar correctamente el registro.
- Tras un error del servidor no se intenta reponer el archivo en el input, porque los navegadores impiden restaurar de forma segura un archivo local; se mantiene visible la imagen persistida y el usuario puede volver a seleccionar el archivo.

**Comportamiento preservado:** rutas, nombres de campos, CSRF, método PUT en edición, límites de campos, tipos admitidos, validaciones de servidor, valores antiguos, reglas promocionales y reglas de fuente de Multimedia. No se modificaron rutas ni modelos.

**Pruebas estáticas realizadas:** inspección de los controladores de Personas, Multimedia y Productos; comprobación de `php -l` para `ProductoAdminController.php`; revisión de nombres de campos/rutas y del flujo de reemplazo de imagen. Los archivos ZIP generados se someterán a comprobación de integridad.

**Pruebas manuales pendientes:**
- Personas: crear sin imagen, archivo local, URL HTTPS, intentar enviar archivo y URL juntos, editar conservando imagen, reemplazar archivo, descartar selección y probar URL inválida.
- Multimedia: probar POSTER/IMAGEN y TRAILER/VIDEO, archivo local, URL de imagen, YouTube, reemplazo, descartar selección, cambiar tipo y no guardar hasta enviar.
- Productos: crear sin imagen/con imagen, editar sin reemplazo, seleccionar y descartar reemplazo, guardar reemplazo, archivo inválido y error de validación conservando la imagen anterior.
- En los tres: cancelar, navegación, errores, vista previa responsive y confirmación de que seleccionar o descartar un archivo no borra el archivo persistido.
- Renderizado Blade, `php artisan view:cache` y pruebas automatizadas Laravel siguen pendientes porque no está presente `vendor/` en este entorno.


## 2026-10-25 — Corrección: imágenes de Productos y estilos de Multimedia

**Estado:** corrección implementada; pendiente de validación funcional y visual por el usuario.

**Archivos modificados:**
- `resources/views/admin/productos/form.blade.php` — la imagen guardada se construye con el mismo patrón que Personas: para rutas locales bajo `productos/`, utiliza `asset('storage/' . $producto->imagen)`; los valores que no son rutas locales se conservan como URLs externas. Se evita depender de `Storage::disk('public')->url()`, que puede generar un host de `APP_URL` como `localhost`.
- `resources/views/admin/multimedia/form.blade.php` — etiquetas, controles, ayudas, secciones, checks y vista previa aplican las clases compartidas `form-cm__*`; el campo Tipo usa `x-form.select` y conserva el evento `change` que consume el script de vista previa. Se corrigió el marcado duplicado del campo Orden detectado en la vista.
- `public/css/formulario.css` — se añadieron estilos compartidos para el botón nativo de selección de archivo, el contenedor de vista previa de imagen/archivo, el marco responsive de vídeo y los checks del formulario.
- `docs/Directriz_patron_compartido_formularios_CINEPREMIUM.md` — versión 1.9; añade O-007 sobre URLs públicas de archivos locales y la terminación visual de Multimedia.
- `docs/Registro_migraciones_formularios_CINEPREMIUM.md` — registro de esta corrección.

**Comportamiento preservado:** rutas, acciones HTTP, CSRF, campos, validaciones, selección de archivo, URL pública, reglas de tipo de Multimedia, vista previa de YouTube, edición y reglas de negocio. No se modificaron controladores, modelos ni rutas para esta corrección.

**Comprobaciones realizadas:** inspección estática de la vista de Personas como referencia; revisión de la expresión de URL de Productos; revisión del listener global que propaga `change` desde `x-form.select`; revisión de que los IDs `tipo` y `tipo_toggle` permanecen consistentes; inspección del marcado del campo Orden y de las nuevas clases CSS.

**Validación pendiente por el usuario:**
- Productos: editar un producto con imagen existente y confirmar que la URL cargada usa el host real; comprobar crear/editar y que la imagen se conserva si no se elige reemplazo.
- Multimedia: comprobar visualmente los controles compartidos, selector Tipo, errores, vista previa de imágenes y vídeos, cambio de tipo y comportamiento responsive.
- Confirmar que el cambio de tipo continúa actualizando los campos y la vista previa.

**Limitación técnica:** no se pudo ejecutar renderizado Blade, `php artisan view:cache` ni pruebas automatizadas Laravel porque el entorno no dispone de `vendor/`.
