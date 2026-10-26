# Guía de unificación visual de tablas administrativas --- CINEPREMIUM

**Estado:** primera etapa de unificación visual completada, con
validación visual pendiente para algunos casos límite.\
**Objetivo:** incorporar nuevas tablas administrativas manteniendo una
apariencia coherente sin alterar la lógica de negocio, las rutas ni el
mecanismo de paginación de cada módulo.

## 1. ¿Podemos considerar terminada la unificación visual?

Sí: el patrón visual compartido ya está establecido y aplicado a las
páginas administrativas de **Películas, Compras, Funciones, Compradores,
Productos y Pagos**.

La unificación incluye:

-   Encabezados de página coherentes mediante
    `x-admin.encabezado-pagina`.
-   Contenedores, filtros, tablas y pies de tabla con estilos
    compartidos.
-   Acciones con iconos y estados de interacción coherentes, incluidos
    tooltips y etiquetas accesibles.
-   Un patrón visual común para la paginación.
-   Ajustes de distribución para que el resumen de resultados quede a la
    izquierda y los controles de paginación a la derecha, con adaptación
    a pantallas pequeñas.
-   Componentes Blade reutilizables para el contenedor, los filtros, el
    estado vacío y la zona de paginación.

**Qué significa "terminada" en esta etapa:** las páginas existentes
comparten una base visual y hay una guía reutilizable para futuras
tablas.

**Qué no significa:** que todas las combinaciones de datos, anchos de
pantalla y estados de interacción hayan sido probadas exhaustivamente en
el navegador. Esa comprobación final debe hacerse con datos reales en el
entorno Laravel.

## 2. Principio fundamental

> Reutilizar la presentación, no forzar a todos los módulos a usar la
> misma lógica.

Una tabla puede compartir estilos y componentes con las demás, pero debe
conservar su comportamiento específico: consultas, filtros, permisos,
rutas, acciones, paginación y reglas de negocio.

Antes de editar, identifica qué parte es estructura visual y qué parte
es lógica funcional. Evita cambiar ambas a la vez.

## 3. Referencia y archivos compartidos

### Página de referencia

Usa **Películas** como referencia visual para mantener la identidad del
panel.

### CSS global

Archivo principal de estilos compartidos:

`public/css/estilos-globales-proyecto.css`

Antes de crear una regla nueva, busca si ya existe una clase común
adecuada. Evita añadir selectores duplicados o reglas que solo corrijan
una página si el problema afecta a todas las tablas.

Clases compartidas relevantes:

-   `.admin-table-card`: contenedor general de la tabla.
-   `.admin-table-filters`: zona de filtros.
-   `.admin-table-count`: contador o resumen de registros, cuando
    corresponda.
-   `.admin-table`: tabla administrativa.
-   `.admin-table-footer`: pie de tabla y zona de paginación.
-   `.admin-table-empty`: presentación de estado vacío cuando
    corresponda.
-   `.admin-action-icon`: estilo común de acciones con iconos.
-   `.admin-action-icon--danger`: variante de acción destructiva.

La clase exacta debe verificarse en el archivo antes de reutilizarla. No
agregues una clase a una vista suponiendo que ya tiene estilos
asociados.

### Componentes Blade compartidos

Ubicación:

`resources/views/components/admin/tabla/`

Componentes existentes:

  -----------------------------------------------------------------------
  Componente                          Uso
  ----------------------------------- -----------------------------------
  `contenedor.blade.php`              Envolver el contenido de una tabla
                                      o tarjeta administrativa.

  `filtros.blade.php`                 Dar una envoltura común a la zona
                                      de filtros.

  `vacio.blade.php`                   Mostrar un estado vacío dentro de
                                      una fila de tabla; no asumir que
                                      sirve como bloque independiente.

  `paginacion.blade.php`              Envolver el pie o la zona de
                                      paginación sin reemplazar el
                                      mecanismo de navegación.
  -----------------------------------------------------------------------

Los componentes compartidos son envolturas de presentación. Revisa sus
atributos y su contenido actual antes de ampliar su responsabilidad.

## 4. Procedimiento para unificar una tabla nueva

### Paso 1. Inspeccionar antes de editar

Localiza:

-   La vista Blade de la página.
-   El componente Livewire, si existe.
-   El controlador o la clase que prepara los datos, si corresponde.
-   El modelo y las consultas relacionadas.
-   Los filtros, ordenamiento, acciones y paginación actuales.
-   Las clases CSS específicas de esa página.

Anota cómo funciona la tabla antes de tocarla. Comprueba especialmente
qué tipo de paginador usa: Laravel Blade o Livewire.

### Paso 2. Unificar el encabezado

Reutiliza `x-admin.encabezado-pagina` cuando corresponda. Mantén el
título, la descripción, los botones y sus destinos propios del módulo.

No cambies permisos, rutas ni acciones solo para que la vista se parezca
a otra.

### Paso 3. Unificar el contenedor y los filtros

Usa las envolturas compartidas y las clases existentes cuando encajen
con la estructura de la página.

Los filtros deben conservar:

-   Sus campos y opciones.
-   Sus nombres y valores.
-   Su validación y comportamiento.
-   La forma en que actualizan los resultados.
-   El reinicio de la paginación cuando sea necesario al cambiar los
    criterios.

No conviertas un filtro tradicional en reactivo, ni al contrario, como
parte de un cambio puramente visual.

### Paso 4. Unificar la tabla

Mantén la estructura semántica de la tabla (`table`, `thead`, `tbody`,
encabezados y celdas). Aplica las clases compartidas existentes en lugar
de crear una segunda familia de estilos.

Verifica que cada columna tenga un propósito claro y que las acciones no
alteren innecesariamente el ancho de la tabla. No cambies el orden de
las columnas ni elimines información sin una decisión explícita.

### Paso 5. Unificar las acciones

Usa el patrón compartido de iconos y tooltips cuando sea adecuado:

-   Editar: icono de lápiz.
-   Eliminar: icono de papelera con estilo de acción destructiva.
-   Ver detalle: icono de ojo, cuando la acción sea consultar detalles.
-   Otras acciones: conservar su función y usar un patrón visual
    coherente.

Requisitos:

-   Mantener el destino, método y parámetros de cada acción.
-   Conservar confirmaciones y controles de autorización existentes.
-   Añadir o preservar `aria-label` contextual, por ejemplo, "Editar
    comprador".
-   No depender exclusivamente del color o del icono para comunicar la
    acción.
-   Verificar los tooltips con ratón y con los mecanismos de interacción
    disponibles en dispositivos táctiles.

### Paso 6. Unificar el pie y la paginación

Usa `x-admin.tabla.paginacion` como envoltura visual cuando corresponda
y `.admin-table-footer` para el pie compartido.

La distribución esperada en pantallas amplias es:

-   **Izquierda:** resumen de resultados, si esa vista lo muestra.
-   **Derecha:** controles de paginación.
-   **Pantallas estrechas:** los elementos pueden reorganizarse o
    centrarse sin solaparse.

**No reemplaces el paginador por otro solo para cambiar su apariencia.**

-   Si la vista usa paginación estándar de Laravel, conserva `links()` y
    su vista de paginación correspondiente.
-   Si usa Livewire, conserva sus acciones reactivas (`previousPage`,
    `gotoPage`, `nextPage`, u otras que ya tenga).
-   La vista personalizada
    `resources/views/vendor/livewire/pagination-bootstrap.blade.php`
    pertenece al flujo de Livewire que ya la utiliza; no debe aplicarse
    automáticamente a todos los paginadores Laravel.

Después de cualquier cambio, comprueba que funcionen los botones
anterior, siguiente y número de página, así como los estados activo y
deshabilitado.

### Paso 7. Revisar el estado vacío y los estados especiales

Verifica qué ocurre cuando:

-   No hay registros.
-   Los filtros no encuentran resultados.
-   Hay solo una página.
-   Se llega a la primera o a la última página.
-   Los datos incluyen nombres, correos u otros textos largos.
-   Un registro tiene más o menos acciones que los demás.

Usa el componente de estado vacío únicamente en la estructura para la
que está diseñado.

### Paso 8. Añadir CSS solo si hace falta

Antes de escribir CSS:

1.  Busca una clase global existente.
2.  Comprueba si la clase ya está aplicada a la vista.
3.  Añade una regla compartida si la necesidad se repite en varias
    tablas.
4.  Usa una regla específica solo cuando la diferencia sea intencional y
    propia de ese módulo.
5.  Evita `!important` y selectores demasiado generales salvo que exista
    una razón técnica clara.

No modifiques colores, posiciones, tamaños ni espaciados ajenos al
problema que estás resolviendo.

## 4.1. Estándar tipográfico aprobado para tablas

Tras probar el ajuste en las páginas **Administración de pagos** y **Administración de funciones**, se aprobó el siguiente estándar tipográfico para las tablas administrativas:

| Elemento | Tamaño aprobado |
|---|---:|
| Texto de las celdas | **14 px** |
| Encabezados de columnas | **13 px** |
| Iconos de acciones | Mantener el tamaño actual |
| Altura y espaciado de las filas | Mantener los valores actuales salvo que una prueba detecte un problema concreto |

### Cómo aplicarlo

- Centraliza los tamaños en `public/css/estilos-globales-proyecto.css`, usando las clases compartidas de tabla.
- Aplica el tamaño de 14 px al contenido de las celdas y 13 px a los encabezados.
- Evita añadir reglas diferentes en cada vista si no existe una necesidad específica.
- No reduzcas automáticamente el tamaño de filtros, paginación, botones o textos auxiliares: este estándar se aprobó para encabezados y celdas.
- No cambies la altura de las filas, el espaciado, los iconos ni la distribución de columnas como parte de este ajuste tipográfico.

Los tamaños fueron aprobados visualmente por el usuario a partir de capturas de las páginas de Pagos y Funciones. La validación de otros tamaños de pantalla y de textos excepcionalmente largos sigue formando parte de las comprobaciones de calidad.

## 5. Lista de comprobación antes de dar una tabla por terminada

### Presentación

-   [ ] Encabezado coherente con el resto del panel.
-   [ ] Contenedor, filtros y tabla utilizan el patrón compartido
    apropiado.
-   [ ] Tipografía, colores, bordes, fondos y espaciados son
    consistentes.
-   [ ] Acciones representadas con iconos coherentes y tooltips útiles.
-   [ ] El pie no presenta superposiciones.
-   [ ] El resumen de resultados queda a la izquierda y la paginación a
    la derecha en escritorio.
-   [ ] En móvil, los elementos no se salen del contenedor ni se pisan.
-   [ ] Los textos largos no ocultan información importante ni
    inutilizan las acciones.
-   [ ] El estado vacío es visualmente coherente.

### Funcionalidad

-   [ ] Los filtros siguen devolviendo los resultados correctos.
-   [ ] El ordenamiento funciona y no provoca recargas o renderizados
    inesperados.
-   [ ] La paginación navega correctamente.
-   [ ] Cambiar filtros u ordenamiento no deja al usuario en una página
    inválida.
-   [ ] Crear, ver, editar y eliminar siguen funcionando según
    corresponda.
-   [ ] Las rutas, permisos, validaciones y confirmaciones se conservan.
-   [ ] No aparecen errores de JavaScript, Livewire, Blade o Laravel.

### Validación en navegador

-   [ ] Probar con registros suficientes para mostrar varias páginas.
-   [ ] Probar con cero resultados y con una sola página.
-   [ ] Probar en un ancho de escritorio y en un ancho móvil.
-   [ ] Probar las acciones principales.
-   [ ] Revisar los cambios visuales con una captura antes de seguir con
    otra tabla.

## 6. Errores que se deben evitar

-   Duplicar en el CSS global reglas que ya existen.
-   Copiar toda la vista de Películas y sustituir nombres, sin entender
    la lógica de la tabla nueva.
-   Reemplazar la paginación de Laravel por la de Livewire, o viceversa,
    por razones estéticas.
-   Cambiar rutas, consultas, permisos, validaciones o eventos durante
    una tarea visual.
-   Eliminar textos accesibles porque el icono parece evidente.
-   Dar por validado el comportamiento solo porque el ZIP se creó
    correctamente.
-   Cambiar estilos de varias páginas a la vez sin probarlas
    individualmente.
-   Corregir una particularidad de una página mediante una regla global
    que perjudique a las demás.

## 7. Método de trabajo recomendado

Para mantener los cambios controlados:

1.  Elegir una tabla y tomarla como objetivo.
2.  Inspeccionar su implementación actual.
3.  Compararla con el patrón de Películas.
4.  Modificar únicamente lo necesario para adoptar los componentes y
    estilos compartidos.
5.  Revisar el diff y confirmar que la lógica no haya cambiado.
6.  Preparar el ZIP o cambio para probar.
7.  Probar en el entorno Laravel y revisar una captura.
8.  Corregir los detalles detectados antes de pasar a la siguiente
    tabla.

Si una tabla ya está unificada, no hay que rehacerla: se comprueba
contra esta guía y solo se corrigen las diferencias reales.

## 8. Estado actual y trabajo pendiente

### Completado en la primera etapa

- Estándar tipográfico aprobado: celdas de 14 px y encabezados de 13 px, comprobado visualmente en Pagos y Funciones.

-   Patrón visual de referencia basado en Películas.
-   Encabezados coherentes en Compras, Funciones, Compradores, Productos
    y Pagos.
-   Estilos compartidos para filtros, tablas y pies.
-   Componentes Blade comunes para envolturas de tabla, filtros, estados
    vacíos y paginación.
-   Acciones con iconos y tooltips.
-   Primera unificación visual de paginación.
-   Ajuste de alineación del resumen de resultados y los botones,
    confirmado por el usuario como funcional.

### Recomendado como control de calidad final

-   Revisar individualmente las tablas con datos reales en el navegador.
-   Confirmar el comportamiento en anchos pequeños, con textos largos y
    con estados vacíos.
-   Comprobar filtros, ordenamiento, acciones y paginación después de
    los cambios.
-   Verificar que no haya regresiones en los módulos que comparten CSS.

Estos puntos son comprobaciones finales; no implican que exista
necesariamente un defecto. No se debe afirmar que han sido validados
hasta probarlos en el entorno Laravel.

## 9. Regla de oro

**Una nueva tabla de CINEPREMIUM debe reutilizar el patrón visual y los
componentes compartidos, pero conservar su propia lógica funcional.** Si
se necesita una excepción visual, debe ser intencional, limitada y
documentada.
