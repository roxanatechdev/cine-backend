# Guía del modelo físico — CinePrime v2

**Versión:** 1.2 (alineada con migraciones octubre 2026; incluye `sala_pasillo`)  
**Motor objetivo:** MySQL 8.0.16+ / MariaDB 10.5+ (InnoDB), compatible con Laragon  
**Base nueva:** `cineprime_v2`  
**Scripts asociados:**
- `cineprime_v2_schema.sql` — estructura
- `cineprime_v2_datos_prueba.sql` — datos de prueba  
**Base de respaldo (no modificar):** `cine_mvp`  
**Tablas de dominio:** 22

## 1. Antes de ejecutar el script

- El script crea `cineprime_v2`; **no elimina ni modifica** `cine_mvp`.
- Ejecutarlo una sola vez sobre una base vacía (o tras `DROP DATABASE cineprime_v2`).
- Crea solo tablas del **dominio CinePrime**. Las tablas internas de Laravel (`users`, `sessions`, `cache`, `jobs`, `migrations`, etc.) se gestionan con migraciones Laravel.
- **Integración Laravel:** no ejecutar este SQL y luego migraciones antiguas que creen otra versión de `pelicula`. Elegir **una sola fuente de verdad** para el dominio.
- Intercalación: `utf8mb4_unicode_ci`.
- **Sin columnas generadas `STORED`:** en MariaDB/Laragon provocan `ERROR 1215` al referenciar columnas involucradas en FK. La unicidad condicional se aplica en la aplicación con transacciones (sección 14).
- Los `CHECK` requieren MySQL 8.0.16+ o MariaDB 10.2+.

## 2. Principios del modelo

1. **Catálogos normalizados:** género, idioma y país no se guardan como texto libre repetido en cada película.
2. **Relaciones explícitas:** una película puede tener varios países, varios recursos multimedia y varias personas acreditadas.
3. **Historial transaccional:** las entradas canceladas o expiradas **no se borran**; el cambio de estado libera el asiento para una nueva venta.
4. **Instantáneas de precio:** `entrada.precio_venta`, `detalle_compra.precio_unitario` y `compra.total` conservan el importe aplicado en el momento de la operación.
5. **Integridad referencial:** claves foráneas con `ON DELETE RESTRICT` para no borrar historial por accidente.
6. **Reglas entre filas:** solapamiento de funciones, total de compra, un pago aprobado, una ocupación activa y un recurso multimedia principal requieren **transacciones y lógica de aplicación**.

## 3. Inventario de tablas (22)

| # | Tabla | Responsabilidad |
| --- | --- | --- |
| 1 | `idioma` | Catálogo de idiomas (código BCP 47). |
| 2 | `pais` | Países por código ISO 3166-1 alfa-2. |
| 3 | `genero` | Catálogo de géneros cinematográficos. |
| 4 | `pelicula` | Datos principales, clasificación, duración, estreno, estado, género principal e idioma original. |
| 5 | `pelicula_pais` | Relación N:M película ↔ país de producción. |
| 6 | `pelicula_multimedia` | Póster, tráiler, imágenes y vídeos; un principal activo por tipo (app). |
| 7 | `persona` | Personas que participan en películas. |
| 8 | `pelicula_credito` | Roles (director, actor, guionista, productor, compositor) con orden y personaje. |
| 9 | `sala` | Sala y margen de limpieza configurable (default 30 min). |
| 10 | `asiento` | Asientos físicos de una sala (`ESTANDAR`, `PREFERENCIAL`, `ACCESIBLE`). |
| 11 | `sala_pasillo` | Pasillos del mapa de sala (longitudinales entre asientos o transversales entre filas). |
| 12 | `funcion` | Proyección: `inicio` DATETIME, formato 2D/3D, precio base y estado. |
| 13 | `funcion_asiento` | Inventario de asientos de una función y precio por asiento. |
| 14 | `comprador` | Datos de contacto (invitado o registrado). Correo **no único**. |
| 15 | `compra` | Cabecera: vencimiento de reserva, total, moneda y estado. |
| 16 | `entrada` | Una fila por asiento reservado/comprado; conserva historial de estados. |
| 17 | `pago` | Intentos de pago; como máximo un `APROBADO` por compra (app). |
| 18 | `reembolso` | Solicitudes de reembolso asociadas a un pago. |
| 19 | `ticket` | Un ticket por compra (`uq_ticket_compra`), código de validación único. |
| 20 | `categoria_producto` | Categorías del catálogo de productos. |
| 21 | `producto` | Productos con precio, stock opcional, variantes (`tamano`, `tipo_variante`, etc.) y promoción. |
| 22 | `detalle_compra` | Líneas de productos: cantidad y precio unitario histórico. |

## 4. Cardinalidades principales

- `genero` 1 — N `pelicula` (género principal; puede ser NULL en borrador).
- `idioma` 1 — N `pelicula`.
- `pelicula` N — M `pais` mediante `pelicula_pais`.
- `pelicula` 1 — N `pelicula_multimedia`.
- `pelicula` N — M `persona` mediante `pelicula_credito`.
- `sala` 1 — N `asiento`.
- `sala` 1 — N `sala_pasillo` (pasillos del mapa; se eliminan en cascada al borrar la sala).
- `pelicula` 1 — N `funcion`; `sala` 1 — N `funcion`.
- `funcion` 1 — N `funcion_asiento`; un asiento participa en muchas funciones a lo largo del tiempo.
- `comprador` 1 — N `compra`.
- `compra` 1 — N `entrada`; `funcion_asiento` 1 — N `entrada` histórica, con **solo una activa** (regla de aplicación).
- `compra` 1 — N `pago` (intentos); **como máximo un pago APROBADO** (regla de aplicación).
- `pago` 1 — N `reembolso`.
- `compra` 1 — 0..1 `ticket`.
- `compra` 1 — N `detalle_compra`; `producto` 1 — N `detalle_compra`.

## 5. Decisiones de negocio incorporadas

### Películas y catálogo

| Tema | Decisión en el esquema |
| --- | --- |
| Clasificación | `ENUM('G','PG','PG-13','R','NC-17')`, nullable hasta publicar. |
| Género | Un género principal: `pelicula.id_genero_principal` → `genero`. |
| Idioma original | `idioma_original_codigo` VARCHAR(15) → `idioma.codigo` (BCP 47). |
| Países | Varios países vía `pelicula_pais` (PK compuesta). |
| Multimedia | Tipos: `POSTER`, `TRAILER`, `IMAGEN`, `VIDEO`. Un principal activo por película y tipo → **aplicación**. |
| Créditos | Roles: `DIRECTOR`, `ACTOR`, `GUIONISTA`, `PRODUCTOR`, `COMPOSITOR`. PK `(id_pelicula, id_persona, rol)`. |
| Estados película | `BORRADOR`, `ACTIVA`, `INACTIVA`. Al publicar, la app debe exigir datos mínimos. |
| Timestamps | `creado_en`, `actualizado_en` en `pelicula` (no `created_at`/`updated_at`). |

### Funciones y salas

| Tema | Decisión en el esquema |
| --- | --- |
| Formato | Solo `2D` y `3D` en `funcion` (no en la película). |
| Margen | `sala.margen_limpieza_minutos` DEFAULT 30. |
| Inicio | `funcion.inicio` DATETIME; el fin se calcula: inicio + duración película + margen. |
| Precio | `funcion.precio_base` y `funcion_asiento.precio` (precio aplicado por asiento). |
| Solapamientos | Validación **transaccional** en la aplicación. |
| Asiento–sala | Las FK no garantizan que el asiento pertenezca a la sala de la función; validar al crear `funcion_asiento`. |
| Tipos de asiento | `ESTANDAR`, `PREFERENCIAL`, `ACCESIBLE`. |
| Capacidad de sala | Informativa (`NULL` permitido); la fuente de verdad son los asientos. |
| Pasillos (`sala_pasillo`) | Mapa de sala: `orientacion` (p. ej. `LONGITUDINAL`). Longitudinales usan `despues_de_numero`; transversales usan `fila_siguiente` y dejan `despues_de_numero` en `NULL`. FK a sala con `ON DELETE CASCADE`. |

### Compras, entradas y pagos

| Tema | Decisión en el esquema |
| --- | --- |
| Compras mixtas | Una compra puede incluir entradas y productos (`detalle_compra`). |
| Historial de entradas | Se conservan `CANCELADA` / `EXPIRADA`; no se borran. |
| Ocupación activa | **Sin UNIQUE en BD.** Máximo una entrada activa (`RESERVADA`/`CONFIRMADA`/`UTILIZADA`) por `funcion_asiento` → **aplicación** + índice `ix_entrada_funcion_asiento_estado`. |
| Ticket | Un ticket por compra (`uq_ticket_compra`); `codigo_validacion` único. |
| Pagos | Varios intentos; métodos `QR` y `TARJETA`. Un solo `APROBADO` por compra → **aplicación** + índice `ix_pago_compra_estado`. |
| Importe pagado | El pago aprobado debe cubrir **exactamente** `compra.total` → aplicación. |
| Reembolso | Proceso separado de la cancelación. Alcance v2: reembolso **total**. |
| Correo comprador | Índice normal, **no único** (permite invitados con el mismo correo). |
| Reserva | `compra.vence_reserva`; comprobar en cada operación y con tarea programada. |
| Moneda | `compra.moneda` CHAR(3) DEFAULT `'BOB'`. |

### Precios e historial

- `funcion_asiento.precio` y `entrada.precio_venta`: precio de la butaca en esa venta.
- `detalle_compra.precio_unitario`: precio del producto en esa compra (no se recalcula con `producto.precio_actual`).
- `compra.total`: instantánea; la app calcula y valida la suma de entradas + productos.
- `producto.stock`: `NULL` = inventario no controlado.
- `producto` variantes/promoción: `tamano`, `tipo_variante`, `contenido_ml`, `peso_gramos`, `es_promocion`, `precio_promocional`, `promocion_inicio`, `promocion_fin` (opcionales; añadidos en migraciones posteriores).

## 6. Estados y transiciones sugeridas

| Entidad | Estados en el ENUM | Observación |
| --- | --- | --- |
| Película | `BORRADOR`, `ACTIVA`, `INACTIVA` | Antes de activar: validar recursos y datos obligatorios. |
| Sala | `ACTIVA`, `INACTIVA`, `MANTENIMIENTO` | No programar funciones nuevas en salas no activas. |
| Función | `PROGRAMADA`, `EN_CURSO`, `FINALIZADA`, `CANCELADA` | Definir avance de estado y efecto sobre ventas si se cancela. |
| Compra | `INICIADA`, `PENDIENTE_PAGO`, `CONFIRMADA`, `CANCELADA`, `EXPIRADA` | El reembolso se consulta en `reembolso`, no como estado de compra. |
| Entrada | `RESERVADA`, `CONFIRMADA`, `UTILIZADA`, `CANCELADA`, `EXPIRADA` | Solo cancelar/expirar liberan el asiento para reventa. |
| Pago | `PENDIENTE`, `APROBADO`, `RECHAZADO` | Un `APROBADO` debe coincidir con el total. |
| Reembolso | `SOLICITADO`, `PROCESANDO`, `COMPLETADO`, `RECHAZADO` | Solo `COMPLETADO` cuenta en el tope de importe. |
| Ticket | `EMITIDO`, `UTILIZADO`, `ANULADO` | Emitir solo tras compra confirmada. |
| Producto | `ACTIVO`, `INACTIVO`, `AGOTADO` | — |

Los `ENUM` limitan valores; **no impiden transiciones inválidas**. Codificar transiciones en servicios de dominio y probarlas.

## 7. Qué garantiza el SQL vs. qué debe hacer la aplicación

### Principalmente por el esquema

- Claves primarias y foráneas (`ON DELETE RESTRICT` / `ON UPDATE CASCADE`).
- Un asiento físico no se repite en la misma sala (`uq_asiento_sala_fila_numero`).
- Un asiento de función se registra una sola vez (`uq_funcion_asiento`).
- Un solo ticket por compra (`uq_ticket_compra`); códigos de ticket y referencias externas de pago/reembolso únicos.
- Importes ≥ 0 o > 0 y cantidades > 0 según `CHECK`.
- Índices de apoyo: multimedia por película/tipo, entradas por asiento/estado, pagos por compra/estado, funciones por sala/inicio.

### Requiere lógica transaccional en la aplicación

1. Que el asiento de una entrada pertenezca a la sala de la función.
2. Que dos funciones no se solapen (duración de la película + `margen_limpieza_minutos`).
3. Que `compra.total` coincida con entradas + productos.
4. Que un pago aprobado cubra exactamente el total.
5. Que exista **como máximo un pago `APROBADO`** por compra.
6. Que exista **como máximo una entrada activa** (`RESERVADA` / `CONFIRMADA` / `UTILIZADA`) por `funcion_asiento`.
7. Que exista **como máximo un recurso principal activo** por película y tipo en `pelicula_multimedia`.
8. Que los reembolsos `COMPLETADO` no superen el importe del pago.
9. Que no se confirme una compra ya vencida (concurrencia reserva/pago).
10. Que una película `ACTIVA` tenga los datos y recursos mínimos.
11. Transiciones de estado válidas.
12. Descuento/reposición de stock y emisión de ticket solo tras confirmación.

## 8. Reserva, pago y expiración

1. **Reservar:** en una transacción, crear compra + entradas `RESERVADA`, asignar `vence_reserva`, usar filas de `funcion_asiento`. Comprobar que no haya otra entrada activa sobre esos asientos.
2. **Pagar:** bloquear la compra; verificar que no venció; comprobar importe exacto; comprobar que no exista otro pago `APROBADO`; marcar pago `APROBADO` y compra/entradas `CONFIRMADA`; emitir ticket.
3. **Expirar:** marcar compra `EXPIRADA` y entradas `EXPIRADA` (el asiento queda libre al dejar de estar en estado activo). Misma regla en operaciones en tiempo real y en tarea programada.
4. **Pago tardío** sobre compra ya expirada: no reactivar asientos automáticamente; flujo de conciliación/devolución explícito.
5. **Cancelar:** registrar `estado`, `cancelada_en` y `motivo_cancelacion` en la compra; pasar entradas a `CANCELADA`; anular ticket si existía; el reembolso se gestiona en `reembolso`.

## 9. Integración con Laravel

1. Apuntar `.env` / `config/database.php` a `cineprime_v2` en desarrollo **sin borrar** `cine_mvp`.
2. Alinear migraciones de dominio con este esquema (o dejar de usar migraciones antiguas de `pelicula` que choquen).
3. Modelo `Pelicula`: PK `id_pelicula`; ya no existen columnas `imagen`, `trailer`, `genero` directas → relaciones con `pelicula_multimedia`, `genero`, etc.
4. Timestamps: el esquema usa `creado_en` / `actualizado_en` en varias tablas. En Eloquent, mapear nombres o desactivar timestamps automáticos donde corresponda.
5. Crear relaciones Eloquent para países, multimedia, créditos, funciones, etc.
6. Servicios transaccionales: reserva, confirmación de pago, cancelación, expiración, reembolso (aquí se implementan las reglas de la sección 7).
7. Probar en la versión exacta de MySQL/MariaDB de Laragon (`CHECK`, collation, utf8mb4).

## 10. Pruebas de aceptación recomendadas

- Película con varios países, créditos y multimedia; la app debe rechazar dos pósteres principales activos.
- Sala + función: cada asiento activo de la sala una sola vez en `funcion_asiento`.
- Dos compras con entradas activas sobre el mismo `funcion_asiento` → la segunda debe fallar en la aplicación.
- Cancelar o expirar una entrada y crear otra para el mismo asiento **sin borrar** el registro histórico.
- Dos funciones solapadas en la misma sala → la app debe rechazar la segunda.
- Pago con importe distinto del total → rechazo en aplicación.
- Segundo pago `APROBADO` para la misma compra → rechazo en aplicación (sí se permiten intentos `RECHAZADO` / `PENDIENTE`).
- Reembolso que exceda el importe del pago → rechazo.
- Reserva que vence al mismo tiempo que llega un pago → solo una transición consistente.
- Borrado de película/compra/pago con historial → bloqueado por FK (`ON DELETE RESTRICT`).

Los datos de `cineprime_v2_datos_prueba.sql` cubren escenarios A–D (confirmada mixta, expirada + reventa, cancelada + reembolso, pendiente de pago).

## 11. Pendientes de alcance (antes de producción)

- Si el género e idioma deben ser obligatorios al publicar.
- Política de vinculación `comprador` ↔ `users` (FK opcional única).
- Variación de precios por tipo de asiento, horario o promociones.
- Descuentos, cupones, impuestos o redondeos (no hay tablas específicas aún).
- Cancelación masiva de función y devolución de todas las compras afectadas.
- Momento de descontar stock (al reservar vs. al confirmar pago).
- Varios géneros por película (hoy solo género principal).
- Conservación, anonimización y acceso a datos personales del comprador.
- Zona horaria operativa para guardar/mostrar horarios de funciones.
- Si más adelante se necesita un ticket por entrada en lugar de uno por compra.

## 12. Advertencias técnicas

- **No hay columnas generadas** en este esquema (decisión por compatibilidad MariaDB/Laragon).
- No hay triggers: la lógica de negocio vive en la aplicación.
- Este diseño **no** incluye importación automática desde `cine_mvp`, usuarios administradores ni migraciones Laravel reconciliadas.
- `ENUM` es cómodo para el MVP; si los estados deben ser configurables, valorar catálogos en tablas.
- Capacidad de `sala` es informativa; la fuente de verdad de butacas son los registros de `asiento`.
- `pago.importe` exige `> 0`; `compra.total` permite `>= 0`.
- Ejecutar scripts con cliente en `utf8mb4` (`SET NAMES utf8mb4`) para acentos y ñ.

## 13. Relación con el esquema anterior (`cine_mvp`)

| Aspecto | cine_mvp | cineprime_v2 |
| --- | --- | --- |
| Película multimedia | Columnas `imagen`, `trailer` | Tabla `pelicula_multimedia` |
| Género | Texto en `pelicula` | Catálogo `genero` + FK |
| Idioma / países | No existían | Catálogos + relaciones |
| Créditos | No existían | `persona` + `pelicula_credito` |
| Formato función | 2D, 3D, XD, PREMIER | Solo 2D, 3D |
| Margen entre funciones | No | `sala.margen_limpieza_minutos` |
| Función fecha/hora | `fecha` + `hora` | `inicio` DATETIME |
| Entrada historial | Sin estado | Estados + reventa (regla en app) |
| Pago único aprobado | Varios pagos sin límite de aprobación | Varios intentos; un `APROBADO` en app |
| Reembolso | No | Tabla `reembolso` |
| Ticket | 1 por compra | Se conserva (`uq_ticket_compra`) |
| `funcion_asiento` | Estados DISPONIBLE/BLOQUEADO/VENDIDO | Solo inventario + precio; ocupación en `entrada` |

`cine_mvp` se mantiene intacta como respaldo. Cualquier migración de datos de películas existentes debe planificarse aparte (mapeo de `genero` texto → `genero`, `imagen`/`trailer` → `pelicula_multimedia`, etc.).

## 14. Unicidad condicional (sin columnas generadas)

En MariaDB/Laragon, las columnas `GENERATED ... STORED` que referencian campos involucrados en claves foráneas provocan **ERROR 1215**. Por eso el esquema v2 no las usa.

| Regla | Mecanismo en v2 |
| --- | --- |
| Un principal activo por película y tipo | Transacción + consulta previa en `pelicula_multimedia` (índice `ix_multimedia_principal`) |
| Una ocupación activa por asiento de función | Transacción + `SELECT ... FOR UPDATE` sobre entradas activas del `funcion_asiento` (índice `ix_entrada_funcion_asiento_estado`) |
| Un pago aprobado por compra | Transacción + comprobar que no exista otro `APROBADO` para `id_compra` (índice `ix_pago_compra_estado`) |

Cuando el proyecto use MySQL 8 verificado y se necesite reforzar en BD, se puede valorar reintroducir unicidad por columna generada en una migración posterior.

## 15. Diccionario resumido de columnas clave

### `pelicula`
`id_pelicula`, `titulo`, `sinopsis`, `id_genero_principal`, `clasificacion`, `duracion_minutos`, `fecha_estreno`, `idioma_original_codigo`, `estado`, `creado_en`, `actualizado_en`

### `pelicula_multimedia`
`id_multimedia`, `id_pelicula`, `tipo`, `url`, `texto_alternativo`, `es_principal`, `orden`, `activo`, `creado_en`

### `funcion`
`id_funcion`, `id_pelicula`, `id_sala`, `inicio`, `formato`, `precio_base`, `estado`, `creado_en`

### `funcion_asiento`
`id_funcion_asiento`, `id_funcion`, `id_asiento`, `precio`

### `compra`
`id_compra`, `id_comprador`, `creada_en`, `vence_reserva`, `moneda`, `total`, `estado`, `cancelada_en`, `motivo_cancelacion`

### `entrada`
`id_entrada`, `id_compra`, `id_funcion_asiento`, `precio_venta`, `estado`, `reservada_en`, `confirmada_en`, `utilizada_en`, `cerrada_en`

### `pago`
`id_pago`, `id_compra`, `metodo`, `importe`, `estado`, `referencia_externa`, `creado_en`, `aprobado_en`

### `reembolso`
`id_reembolso`, `id_pago`, `importe`, `motivo`, `estado`, `solicitado_en`, `completado_en`, `referencia_externa`

### `ticket`
`id_ticket`, `id_compra`, `codigo_validacion`, `emitido_en`, `estado`, `utilizado_en`
