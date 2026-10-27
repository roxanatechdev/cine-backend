# CINEPREMIUM — Patrones Livewire para estados, imágenes y selectores

**Fecha:** 24 de octubre de 2026  
**Alcance:** patrones aplicados a Categorías de productos y Productos.  
**Estado:** comportamiento de categorías y selector de estado de productos confirmado por el usuario; imágenes confirmadas como resueltas en el entorno del usuario. La validación automática no sustituye las pruebas en navegador.

## 1. Principios comunes

- Mantener la lógica de negocio en el componente/controlador y usar Blade para presentar el estado.
- En acciones reactivas, evitar redirecciones completas cuando el cambio puede resolverse con Livewire.
- Conservar búsqueda, filtros, orden y página actual cuando sea correcto para la operación.
- Usar `wire:key` estable en filas para que Livewire pueda identificar registros al actualizar el DOM.
- Validar en el servidor los identificadores, estados y valores permitidos; el estado del navegador no es una fuente confiable.
- Mantener etiquetas accesibles (`label`, `aria-label`, `alt`) aunque visualmente se usen iconos.
- Probar la interacción con datos reales después de modificar la vista: el código estático no demuestra por sí solo el comportamiento visual.

## 2. Categorías de productos — interruptor de estado

### Archivo de referencia

`app/Livewire/Admin/CategoriasProducto/TablaCategoriasProducto.php`

### Comportamiento aprobado

El método `alternarEstado(int $idCategoria)` busca la categoría, invierte el valor booleano `activo`, persiste el cambio y muestra un mensaje de sesión. Después invalida la propiedad computada `categorias` con `unset($this->categorias)` para que la consulta se vuelva a ejecutar en el siguiente render.

Patrón de referencia:

```php
public function alternarEstado(int $idCategoria): void
{
    $categoria = CategoriaProducto::query()->findOrFail($idCategoria);
    $categoria->activo = ! (bool) $categoria->activo;
    $categoria->save();

    session()->flash(
        'success',
        $categoria->activo
            ? 'Categoría activada correctamente.'
            : 'Categoría desactivada correctamente.'
    );

    unset($this->categorias);
}
```

El control de la vista debe invocar el método Livewire y no enviar al usuario a una ruta que redirija el listado. Así se evita la recarga completa y se preserva el estado reactivo de filtros, orden y paginación. El componente conserva una lista blanca de columnas ordenables y reinicia la página cuando cambia el orden o los filtros.

### Cuándo reutilizarlo

Usar un interruptor solo cuando la entidad realmente tenga un estado booleano de dos opciones, como `activo`/inactivo. No sustituir con él estados de negocio que tengan tres o más opciones.

## 3. Productos — carga y presentación de imágenes

### Archivos de referencia

- `app/Livewire/Admin/Productos/TablaProductos.php`
- `resources/views/livewire/admin/productos/tabla-productos.blade.php`

### Patrón visual

1. El contenedor de miniatura tiene dimensiones fijas.
2. El marcador de posición está visible desde el primer render.
3. La imagen real se posiciona encima, con opacidad inicial `0`.
4. Solo el evento `load` muestra la imagen y oculta el marcador.
5. Si falla la carga, se elimina el elemento `<img>` y el marcador queda visible.
6. Si no existe una ruta de imagen, solo se renderiza el marcador apropiado.

Fragmento de referencia simplificado:

```blade
<div class="producto-imagen-marco">
    <span class="producto-imagen-placeholder" aria-hidden="true">
        <span class="material-symbols-outlined">image</span>
    </span>
    @if ($producto->imagen)
        <img
            src="{{ $this->urlImagen($producto->imagen) }}"
            alt="Imagen de {{ $producto->nombre }}"
            class="producto-imagen-real"
            loading="lazy"
            decoding="async"
            width="44"
            height="44"
            onload="this.style.opacity='1';this.previousElementSibling.style.visibility='hidden'"
            onerror="this.onerror=null;this.remove()">
    @endif
</div>
```

CSS asociado en la vista actual: `.producto-imagen-marco`, `.producto-imagen-placeholder` y `.producto-imagen-real`. La imagen usa `object-fit: cover`; el contenedor recorta el contenido con `overflow: hidden`.

### Consideraciones

- Mantener dimensiones estables para evitar saltos de diseño durante la carga.
- No mostrar primero el `<img>` sin cargar y confiar únicamente en `onerror`: el navegador puede mostrar brevemente el icono de imagen rota.
- Conservar texto alternativo contextual.
- Mantener la generación de URL alineada con el almacenamiento real del proyecto; actualmente se utiliza `Storage::disk('public')->url(...)`.
- Comprobar al menos tres casos: imagen válida, producto sin imagen y ruta inválida.
- No asumir que `loading="lazy"` resuelve por sí solo el estado visual.

## 4. Productos — selector de estado con guardado explícito

### Decisión funcional

Productos admite tres estados válidos: `ACTIVO`, `INACTIVO` y `AGOTADO`. Por ello se mantiene un selector, no un interruptor binario ni un guardado automático. El usuario elige un valor y confirma con un botón de guardar.

### Comportamiento aprobado

- `estadosSeleccionados` mantiene el valor elegido por producto.
- El selector usa `wire:model.live` para sincronizar el valor elegido con Livewire.
- El botón se renderiza solo cuando el valor seleccionado es diferente del estado persistido del producto.
- El botón usa un icono de guardar, tooltip y `aria-label` contextual.
- Durante el guardado, los controles correspondientes se deshabilitan para reducir envíos duplicados.
- `guardarEstado(...)` debe validar el valor contra la lista de estados permitidos, guardar el cambio y refrescar los datos sin reiniciar innecesariamente filtros, orden ni paginación.

Condición visual clave:

```blade
@if (($estadosSeleccionados[$producto->id_producto] ?? $producto->estado) !== $producto->estado)
    {{-- Renderizar el botón de guardar solo si hay cambios pendientes. --}}
@endif
```

El nombre del estado original y el valor seleccionado deben compararse con la misma representación (por ejemplo, mayúsculas y valores ENUM normalizados). Si el botón no aparece al cambiar el selector, revisar primero que `wire:model.live` actualice la clave correcta y que el valor seleccionado sea distinto del valor persistido; no quitar la condición sin entender el problema.

### Diferencia frente a Categorías

| Caso | Control apropiado | Motivo |
|---|---|---|
| Categoría de producto | Interruptor de estado | Estado booleano `activo` de dos opciones. |
| Producto | Selector de tres opciones + guardar | Tres estados de negocio que no pueden representarse correctamente con un booleano. |

## 5. Tabla compacta y acciones

La tabla de Productos usa anchos definidos por columna y `table-layout: fixed`, una envoltura con desplazamiento horizontal y controles de acción compactos. El objetivo es reducir el desbordamiento sin ocultar información ni forzar que todas las tablas del panel tengan los mismos anchos.

Reglas:

- Ajustar anchuras solo en la tabla afectada, salvo que se demuestre que el problema pertenece al estilo compartido.
- Mantener suficiente espacio para el nombre del producto y su variante.
- Usar iconos con tooltip y nombre accesible para editar y guardar.
- Mantener el desplazamiento horizontal como respaldo en pantallas estrechas.
- Revisar el resultado en navegador; los anchos deben evaluarse con el contenedor real del panel.

## 6. Incidentes y lecciones aprendidas

### Ordenamiento de Promoción

Se produjo `Unknown column 'es_promocion' in 'order clause'` porque la base de datos activa todavía no tenía las columnas comerciales previstas. Tras actualizar el repositorio, el usuario confirmó que faltaba ejecutar las migraciones y que la columna volvió a funcionar después de ejecutarlas.

**Lección:** antes de retirar un campo del ordenamiento o cambiar el modelo, comparar la consulta con el esquema real y el estado de migraciones. Una columna presente en el modelo no implica que exista en la base de datos activa.

### Botón de guardar ausente

Al ocultar el botón cuando no había cambios, la condición inicial no reaccionó como se esperaba. Se corrigió en una versión posterior y el usuario confirmó que el botón aparece y funciona al cambiar el estado.

**Lección:** las condiciones Blade que dependen de estado reactivo deben probarse en el navegador con el valor original y el seleccionado; la sintaxis correcta no garantiza que el estado se actualice como se espera.

### Imágenes

El usuario confirmó que el tratamiento visual de las imágenes quedó resuelto.

## 7. Lista de pruebas de regresión

### Categorías de productos

- [ ] Activar y desactivar una categoría.
- [ ] Confirmar actualización sin recarga completa.
- [ ] Confirmar que búsqueda, filtros, orden y paginación se mantienen.
- [ ] Confirmar mensajes de éxito y persistencia después de recargar.

### Productos

- [ ] Imagen válida, ausente y URL inválida.
- [ ] Cambiar entre los tres estados y guardar.
- [ ] Confirmar que el botón no aparece cuando no hay cambios y sí aparece cuando el valor cambia.
- [ ] Confirmar que el botón desaparece después de guardar correctamente.
- [ ] Confirmar que filtros, orden y página se conservan después del guardado.
- [ ] Probar ordenamiento por promoción con las migraciones aplicadas.
- [ ] Probar anchuras en escritorio y en una ventana estrecha.

## 8. Próximo módulo: Personas — diagnóstico inicial

En la versión del proyecto inspeccionada, Personas todavía usa una tabla Blade tradicional y una búsqueda GET, no el patrón Livewire de Productos:

- Vista: `resources/views/admin/personas/index.blade.php`.
- Controlador: `app/Http/Controllers/admin/PersonaAdminController.php`.
- Búsqueda por `nombre_completo` y `nombre_busqueda`.
- Orden fijo por `nombre_completo` y `id_persona`.
- Paginación de 10 registros con `withQueryString()`.
- Columnas: foto, nombre completo, nombre de búsqueda y acción Editar.
- La foto se presenta con `asset('storage/' . $persona->foto)` cuando la ruta comienza por `personas/`; de lo contrario, se usa como URL externa. Si no hay foto, muestra “Sin foto”.
- La imagen actual tiene `loading="lazy"`, dimensiones declaradas y `object-fit: cover`, pero no tiene el patrón placeholder-first ni manejo explícito `onload`/`onerror` de Productos.
- La tabla usa `table-responsive` y componentes Bootstrap/estilos locales; no usa todavía el contenedor ni el pie de tabla compartidos de los listados Livewire.

### Recomendación para Personas

No copiar automáticamente el selector de Productos ni el interruptor de Categorías: el listado actual no muestra un campo de estado editable. El patrón de imágenes sí podría adaptarse a la foto de persona, conservando el tratamiento de URL local/externa. Antes de implementar, decidir si se quiere mantener Personas con búsqueda GET tradicional o migrar el listado a Livewire; esa decisión afecta filtros, ordenamiento, paginación y alcance del cambio.

**Estado del módulo Personas:** inspección inicial completada; todavía no se modificaron sus archivos.

## 9. Editor visual de distribución de salas — iteración 2

- La cuadrícula visual de asientos se mantiene como interfaz principal; no rediseñar su apariencia durante cambios funcionales.
- Las celdas vacías permiten crear asientos y las celdas ocupadas seleccionar un asiento para editarlo.
- Los asientos con registros en `funcion_asiento` conservan su identidad histórica: no se permite cambiar fila, número o tipo ni eliminarlos. Solo se permite cambiar `activo`.
- Los asientos sin historial pueden cambiar de fila, número y tipo, y pueden eliminarse con confirmación desde la interfaz.
- La protección se comprueba en el componente del servidor dentro de transacciones; ocultar o deshabilitar controles en Blade no sustituye las comprobaciones del servidor.
- La combinación sala/fila/número debe permanecer única; al mover un asiento, validar duplicados e ignorar su propio `id_asiento`.
- Pruebas automatizadas previstas en `tests/Feature/admin/EditorDistribucionSalaTest.php`: creación, edición de disponibilidad, protección de tipo/ubicación, movimiento y eliminación con y sin historial.
- No declarar la iteración verificada hasta ejecutar esas pruebas en un entorno con dependencias instaladas y revisar manualmente la interacción del plano.

## Editor visual de distribución de salas — Iteración 3

- La creación masiva se limita a una fila y a un rango de hasta 100 posiciones por operación.
- Antes de crear, el administrador debe generar una vista previa que detalle las posiciones existentes y las faltantes.
- La confirmación solo crea posiciones faltantes; los asientos existentes conservan tipo, disponibilidad e identidad.
- El servidor valida fila, rango, tipo y disponibilidad. No confiar en el estado de vista previa enviado por el cliente; al confirmar, recalcular las posiciones existentes dentro de la transacción.
- La inserción usa protección frente a duplicados. Mantener la restricción única de sala/fila/número como última defensa.
- Probar rango invertido, rango excesivo, fila con posiciones ya existentes y conservación de los datos preexistentes.
