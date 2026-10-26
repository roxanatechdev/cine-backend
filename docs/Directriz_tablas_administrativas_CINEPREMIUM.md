# Directriz de desarrollo: tablas administrativas de CINEPREMIUM

**Propósito:** establecer un proceso repetible para revisar, implementar y verificar las tablas administrativas, manteniendo coherencia visual, funcional y técnica entre módulos.

## 1. Principios

1. **Inspeccionar antes de modificar.** Revisar la implementación real del módulo y sus dependencias antes de proponer cambios.
2. **Preservar la lógica de negocio.** Los cambios de tabla no deben alterar accidentalmente rutas CRUD, validaciones, permisos, relaciones ni reglas de disponibilidad.
3. **Reutilizar antes de crear.** Usar componentes compartidos existentes siempre que encajen con la necesidad. Crear un componente nuevo solo cuando tenga una responsabilidad reutilizable y clara.
4. **No añadir elementos por decoración.** Filtros, KPI, gráficos y controles deben resolver una necesidad real de administración.
5. **Verificar y declarar límites.** Diferenciar entre revisión de código, comprobación de sintaxis y pruebas funcionales ejecutadas realmente.
6. **Cambios incrementales.** Preferir cambios pequeños, rastreables y fáciles de verificar; aplicar en lote solo cuando los módulos sean suficientemente similares.

## 2. Flujo estándar por módulo

### Fase A — Diagnóstico del módulo

Revisar, según corresponda:

- Modelo, relaciones, casts y scopes.
- Controlador o componente Livewire que entrega los datos.
- Vista de listado y componentes Blade utilizados.
- Rutas, middleware, autorización y políticas.
- Migraciones y columnas disponibles.
- Formularios de creación y edición, validaciones y acciones CRUD.
- Reglas de negocio que la tabla no debe romper.
- Componentes, estilos y patrones ya usados por otros módulos.

Entregar un diagnóstico breve con:
- Qué existe y funciona.
- Qué falta o presenta problemas.
- Riesgos y dependencias.
- Recomendación de alcance antes de escribir código.

No asumir que una función existe solo porque se ve en la interfaz; comprobar su implementación.

### Fase B — Definir búsqueda y filtros

Elegir filtros según las tareas reales del administrador. Posibles criterios:

- Búsqueda textual por nombre, título o identificador relevante.
- Estado, categoría, tipo o relación, cuando tenga sentido.
- Fechas o rangos para entidades temporales.
- Otros criterios propios de la entidad, solo si aportan valor.

Reglas:
- Permitir combinar filtros cuando sea útil.
- Validar o limitar los valores aceptados en el servidor.
- Definir el comportamiento al limpiar filtros.
- Reiniciar la página actual al cambiar filtros para evitar quedar en una página sin resultados.
- Conservar los filtros al navegar entre páginas.
- Mostrar un estado vacío útil y una forma clara de restablecer la búsqueda.
- Evitar filtros redundantes, KPI decorativos o controles que no resuelvan una necesidad concreta.

### Fase C — Livewire, consulta y paginación

- Mantener la consulta eficiente y usar las relaciones necesarias; evitar consultas N+1.
- Aplicar búsqueda y filtros en la consulta del servidor, no solo ocultar filas en el navegador.
- Usar paginación cuando el volumen o la experiencia lo requieran.
- Mantener los criterios de búsqueda y filtros durante la paginación.
- Reiniciar la página al cambiar los criterios que afectan los resultados.
- Limitar y validar opciones como cantidad de registros por página.
- Evitar duplicar lógica de consulta entre vistas y componentes.
- Seguir el patrón de Livewire ya instalado en el proyecto (actualmente Livewire 4.4, sujeto a confirmación en el entorno de trabajo).

### Fase D — Ordenamiento por columnas

- Permitir ordenar solo columnas relevantes.
- Excluir columnas de acciones y controles.
- Usar una lista explícita de columnas permitidas en el servidor; nunca insertar directamente nombres de columnas arbitrarios provenientes de la petición en una consulta.
- Para relaciones, ordenar de forma segura mediante joins o subconsultas adecuados a la estructura real.
- Definir orden inicial estable y desempates cuando corresponda.
- Mostrar un indicador visual de columna y dirección.
- Al cambiar de columna, volver a la primera página y conservar filtros activos.
- Comprobar que las relaciones no dupliquen registros y que la consulta siga siendo paginable.

### Fase E — Componentes y estilos compartidos

Reutilizar, cuando corresponda, los componentes presentes en el proyecto:

- `x-form.select`: selector personalizado para formularios y filtros, si es compatible con el caso de uso.
- `x-admin.tabla.paginacion`: navegación de páginas.
- `x-admin.tabla.pie`: pie compartido con resumen de registros y navegación.
- `x-admin.tabla.contenedor` y otros componentes de tabla existentes.
- Clases y estilos existentes del proyecto; evitar crear variantes CSS duplicadas sin necesidad.

Uso esperado del pie de tabla:

```blade
<x-admin.tabla.pie
    :paginator="$this->registros"
    label="registros"
    class="admin-table-footer"
/>
```

El nombre de la propiedad y la etiqueta deben adaptarse a cada módulo. Verificar la API real del componente antes de usarlo.

No modificar componentes compartidos sin revisar qué otros módulos los utilizan. Si se cambia su API o comportamiento, probar también los módulos afectados.

### Fase F — Verificación

Lista mínima de comprobación:

- [ ] La tabla carga con datos y con cero resultados.
- [ ] La búsqueda encuentra coincidencias y maneja búsquedas sin resultados.
- [ ] Los filtros funcionan individualmente y combinados.
- [ ] Limpiar filtros restaura la vista esperada.
- [ ] La paginación muestra el rango y total correctos.
- [ ] Los filtros se conservan al cambiar de página.
- [ ] El ordenamiento funciona ascendente y descendente.
- [ ] Cambiar el orden no pierde los filtros.
- [ ] El orden inicial es coherente y estable.
- [ ] Las acciones CRUD siguen funcionando.
- [ ] Las validaciones y reglas de negocio siguen vigentes.
- [ ] No aparecen errores en consola ni errores de Livewire.
- [ ] Las relaciones y consultas no producen duplicados ni problemas evidentes de rendimiento.
- [ ] Se revisa el comportamiento responsive si la tabla lo necesita.

Verificación técnica:
- Ejecutar comprobaciones de sintaxis apropiadas.
- Ejecutar pruebas automatizadas disponibles.
- Si no es posible ejecutar pruebas, indicar explícitamente cuáles no se pudieron ejecutar y por qué.
- No afirmar que una prueba funcional pasó si no se ejecutó.

### Fase G — Cierre del módulo

Al terminar, registrar:
- Archivos modificados o creados.
- Filtros implementados y motivo.
- Columnas ordenables.
- Componentes reutilizados o modificados.
- Validaciones y pruebas ejecutadas.
- Limitaciones pendientes.
- Si el módulo está listo para avanzar.

## 3. Decidir entre trabajo individual y por lotes

### Trabajar módulo por módulo cuando:
- La lógica de negocio es diferente.
- La consulta tiene relaciones complejas.
- Hay riesgos de permisos, estados o reglas de disponibilidad.
- La estructura de la tabla aún no está clara.

### Trabajar en lote cuando:
- Varios módulos ya fueron diagnosticados.
- Comparten el mismo patrón técnico.
- Se trata de una mejora transversal de un componente compartido.
- Se puede verificar el impacto en todos los módulos afectados.

**Estrategia recomendada:** diagnosticar cada módulo por separado; acordar un patrón común; implementar en lotes pequeños de tablas similares; verificar cada módulo y hacer una regresión de los componentes compartidos.

## 4. Definición de “tabla lista”

Una tabla no se considera terminada solo porque visualmente muestre datos. Debe cumplir lo que aplique de lo siguiente:

1. Consulta correcta y eficiente.
2. Búsqueda y filtros útiles, combinables y restablecibles.
3. Paginación coherente, con resumen correcto.
4. Ordenamiento seguro, predecible y visualmente indicado.
5. Acciones y permisos preservados.
6. Estados vacíos y errores tratados adecuadamente.
7. Pruebas ejecutadas o limitaciones de verificación declaradas.
8. Documentación del cierre actualizada.

No todos los módulos requieren todos los filtros ni ordenamiento para cada columna. La decisión debe basarse en las tareas administrativas reales.

## 5. Contexto actual de CINEPREMIUM

Patrones establecidos durante el trabajo reciente:

- Las tablas de **Películas** y **Funciones** cuentan con búsqueda/filtros, paginación y ordenamiento por columnas en las versiones de trabajo más recientes.
- Se introdujo el componente compartido `x-admin.tabla.pie` para combinar el resumen de registros con la navegación.
- Se reutiliza `x-form.select` para selectores personalizados cuando resulta compatible.
- El módulo de Funciones requiere especial cuidado con fechas, sala, estado, duración de películas, solapamientos y tiempo de limpieza.
- La implementación real debe revisarse en el proyecto actual antes de asumir que los archivos o patrones de una versión previa siguen vigentes.

Esta sección es una referencia de trabajo y debe actualizarse si el proyecto cambia.

## 6. Plantilla breve para iniciar un nuevo módulo

> Vamos a preparar la tabla administrativa de **[MÓDULO]** siguiendo la directriz de tablas de CINEPREMIUM.
>
> 1. Inspecciona modelo, controlador/componente Livewire, rutas, vista, relaciones, validaciones y componentes existentes.
> 2. Resume el estado actual y señala riesgos antes de modificar archivos.
> 3. Propón solo búsqueda, filtros y ordenamiento útiles para la entidad.
> 4. Reutiliza los componentes compartidos existentes, incluido `x-admin.tabla.pie` y `x-form.select` cuando sean compatibles.
> 5. Implementa cambios incrementales y conserva las reglas de negocio.
> 6. Prueba filtros, paginación, ordenamiento, acciones CRUD y estados vacíos; declara qué pruebas se ejecutaron.
> 7. Resume archivos cambiados y determina si la tabla está lista para cerrar.

---
**Regla principal:** revisar primero, diseñar según la entidad, reutilizar componentes, implementar con seguridad y verificar antes de avanzar.

## 7. Patrón aprobado para catálogos administrativos con Livewire

**Estado: aprobado por validación funcional del usuario el 24-10-2026.** El patrón se probó primero en Géneros y después se replicó en Países e Idiomas. Estos módulos sirven como referencias para los siguientes catálogos; no significa que todos deban copiar exactamente sus filtros.

### 7.1. Estructura de archivos

Para cada catálogo, separar la página contenedora de la tabla reactiva:

- `app/Livewire/Admin/{Catalogo}/Tabla{Catalogo}.php`: estado de filtros, consulta, ordenamiento y paginación.
- `resources/views/livewire/admin/{catalogo}/tabla-{catalogo}.blade.php`: tabla, controles y estados visuales.
- `resources/views/admin/{catalogo}/index.blade.php`: encabezado administrativo, mensajes de sesión y montaje del componente Livewire.
- `app/Http/Controllers/admin/{Catalogo}AdminController.php`: conservar `create`, `store`, `edit` y `update`; `index()` entrega la vista contenedora cuando el listado pasa a Livewire.

Adaptar nombres y mayúsculas a las convenciones reales del repositorio. Antes de crear estos archivos, revisar el catálogo actual y no sobrescribir archivos existentes a ciegas.

### 7.2. Responsabilidades del componente Livewire

El componente debe:

1. Usar `WithPagination` y reiniciar la página al cambiar búsqueda, filtros, tamaño de página u ordenamiento.
2. Mantener los criterios durante la paginación; usar `#[Url]` solo para propiedades cuya persistencia en la URL sea apropiada para ese catálogo.
3. Limitar `perPage` a opciones explícitas aprobadas (en el patrón actual: 10, 25 y 50) y normalizar valores recibidos.
4. Definir una lista blanca de columnas ordenables. Nunca usar un nombre de columna arbitrario recibido del cliente directamente en `orderBy`.
5. Establecer un orden inicial estable y un desempate por clave primaria cuando corresponda.
6. Aplicar búsqueda y filtros en la consulta de base de datos; no filtrar solamente las filas ya renderizadas.
7. Cargar relaciones o conteos necesarios de forma eficiente (`with`, `withCount` o consultas apropiadas) y evitar N+1.
8. Ofrecer una acción para limpiar filtros y un estado vacío que diferencie entre catálogo sin registros y búsqueda sin coincidencias cuando resulte útil.

### 7.3. Adaptar búsqueda, filtros y relaciones

No copiar los criterios de un catálogo a otro sin comprobar el modelo y la consulta existentes.

- **Géneros (piloto):** búsqueda por identificador, nombre y descripción; filtro por estado; conteo de películas con `withCount('peliculas')`.
- **Países:** búsqueda adaptada a código ISO y nombre; conteo de películas usando la relación real `peliculas()`; no inventar un filtro de activo si el modelo no tiene ese atributo.
- **Idiomas:** búsqueda por código y nombre; filtro de activo/inactivo porque el modelo sí contempla estado; conteo usando la relación real `peliculasOriginales()`.

Estos ejemplos documentan las decisiones aprobadas para esos tres módulos. En cada catálogo futuro se deben verificar los nombres reales de columnas, clave primaria, casts, relaciones, cardinalidad y reglas de negocio antes de reutilizar una consulta.

### 7.4. Presentación compartida

Reutilizar, cuando corresponda y tras verificar su API actual:

- `x-admin.encabezado-pagina` para el encabezado.
- `x-admin.tabla.contenedor` para el contenedor de la tabla.
- `x-form.select` para selectores compatibles. Si se usa el selector personalizado en Livewire, conservar el listener de eventos necesario para propagar la selección al componente.
- `x-admin.tabla.pie` para resumen y paginación.
- Clases CSS globales existentes y el patrón visual de acciones accesibles.

No crear una segunda familia de componentes ni duplicar CSS por catálogo. Las columnas, etiquetas, filtros, badges y acciones siguen siendo específicos de cada entidad.

### 7.5. Integración sin regresiones

- La vista `index` debe montar el componente y mantener mensajes de sesión relevantes.
- Conservar rutas, middleware, permisos, enlaces de edición, formularios y validaciones CRUD.
- No mover la lógica de creación o edición a Livewire como parte de la migración de la tabla.
- No añadir acciones de eliminación o cambio de estado si el catálogo no las tenía o no están autorizadas.
- Revisar que las rutas nombradas que usan las acciones existan realmente.

### 7.6. Lista de verificación para cada catálogo

- [ ] Diagnóstico del modelo, migraciones, relaciones, controlador, vistas y rutas.
- [ ] Búsqueda y filtros específicos definidos antes de programar.
- [ ] Columnas ordenables incluidas en una lista blanca y con indicadores accesibles.
- [ ] Opciones de paginación limitadas y reinicio de página al cambiar criterios.
- [ ] Conteos y relaciones correctos, sin duplicados ni N+1 evidente.
- [ ] Pie compartido y selector personalizado funcionan correctamente.
- [ ] Estado vacío y limpieza de filtros comprobados.
- [ ] Formularios, rutas, permisos y validaciones CRUD siguen funcionando.
- [ ] Sintaxis PHP comprobada y pruebas automatizadas disponibles ejecutadas.
- [ ] Pruebas manuales en navegador: datos, cero resultados, varias páginas, filtros combinados, orden ascendente/descendente, acciones y pantalla estrecha.
- [ ] Registrar expresamente qué pruebas se ejecutaron y cuáles quedaron pendientes.

**Regla de reutilización:** copiar el patrón estructural de Géneros, Países o Idiomas como punto de partida; adaptar la consulta y la interfaz a la entidad real. No considerar una tabla terminada únicamente porque compila o porque el ZIP se generó.

## 8. Estado de catálogos

| Catálogo | Patrón Livewire | Estado comunicado por el usuario |
|---|---|---|
| Géneros | Piloto | Aprobado |
| Países | Replicado | Aprobado |
| Idiomas | Replicado | Aprobado |

La aprobación funcional corresponde a la validación realizada por el usuario en su entorno. Las futuras implementaciones deben registrar sus propias pruebas; no heredan automáticamente esta aprobación.

### 8.1. Categorías de producto: implementación en curso para validación

El módulo `Categorías de producto` sigue el patrón aprobado de catálogos, con estas particularidades verificadas en el modelo y las rutas existentes:

- Buscar por ID, nombre y descripción; filtrar por estado activo/inactivo.
- Ordenar únicamente por ID, nombre, descripción, cantidad de productos y estado, mediante lista blanca.
- Usar `withCount('productos')` a partir de la relación `CategoriaProducto::productos()`; no cargar imágenes ni filas de productos en la tabla de categorías.
- Mantener `10`, `25` y `50` filas por página, reiniciar paginación al cambiar criterios y usar orden secundario estable por ID.
- Conservar los formularios CRUD existentes, el nombre único (máximo 80 caracteres), la descripción opcional (máximo 255), el estado por defecto activo y el cambio de estado sin borrado físico.
- La vista de índice únicamente monta el componente Livewire; no migrar los formularios a Livewire como parte de este trabajo.
- El módulo `Productos` se mantiene separado porque su manejo de imágenes y campos comerciales requiere diagnóstico e implementación propios.

**Estado:** implementación preparada para que el usuario la pruebe. La sintaxis PHP se revisó; las pruebas de integración Laravel y la validación visual en navegador deben registrarse como pendientes si no se ejecutan en un entorno con dependencias y base de datos configuradas.
