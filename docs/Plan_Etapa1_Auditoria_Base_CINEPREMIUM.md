# CINEPREMIUM — Etapa 1: preparación y protección

Fecha de revisión: 2026-10-27

## Fuente base
- Archivo de origen: `cine-backend-correccion-prueba-intercambio.zip`
- SHA-256 del archivo: `9cfd7423ae70af1f4ab7dc2e78c8227f4dd85c98831be6fa128dd39feb599600`
- Copia de trabajo preparada en: `/mnt/data/cine-backend-etapa1/`
- No se modificó código del proyecto durante esta etapa.

## Archivos protegidos — SHA-256 inicial

| Archivo | SHA-256 |
|---|---|
| `app/livewire/admin/Salas/EditorDistribucionSala.php` | `79855f006cf510b81311f1f90d2652df174d4b1e34a832810b876486a8e75e96` |
| `resources/views/admin/salas/distribucion.blade.php` | `286e3d94a05bcf3f05e405c06fbf4789d6af1ebc6db5dd35e42f4633b46ae623` |
| `resources/views/livewire/admin/salas/editor-distribucion-sala.blade.php` | `76b20922002991a39e29147323793bc12633d2f88272c5c0f4a18e64bdbd4a5d` |
| `public/css/admin-distribucion-salas.css` | `be50c98594ad210b05224b8e13433e7626f153f0e00e2ed238674671e83c9176` |
| `tests/Feature/admin/EditorDistribucionSalaTest.php` | `b387814f820a4b7ede8b2911f289c2882570f02f5fee93cb75784bf8b68b355e` |
| `resources/views/admin/layout.blade.php` | `e534c31af0d0e969b80e15032d6f62a70161a9a2b75a52258d740ef003e135be` |
| `routes/web.php` | `4fd4f6e5901387a346dd65156e2eb99104f3553e0ed487941e250918e3356cc2` |

## Hallazgos de preparación

1. La vista de página extiende `admin.layout`, presenta el enlace de regreso a `admin.salas.index` y monta `admin.salas.editor-distribucion-sala` con `sala-id`.
2. La ruta está declarada como `admin.salas.distribucion` en `routes/web.php`; no se detecta necesidad inicial de cambiarla.
3. El layout global expone `@stack('styles')` y `@yield('content')`. La página puede añadir un contenedor y estilos propios sin modificar el layout global inicialmente.
4. El CSS actual usa selectores con prefijo `dist-`; en la revisión no se encontraron selectores directos para `body`, `.admin-shell`, `.admin-sidebar` ni `.admin-content`. La nueva hoja deberá mantener este aislamiento.
5. El componente contiene las operaciones de borrador, guardar y cancelar, validaciones de asientos y pasillos, selección múltiple, edición/eliminación masiva e intercambio de posiciones. La implementación visual debe preservar estas acciones.
6. La suite `EditorDistribucionSalaTest.php` contiene 30 métodos `test_`.
7. `php -l` pasó sin errores en el componente Livewire, controlador de salas, modelos `Sala` y `Asiento`, archivo de pruebas y rutas.
8. No fue posible ejecutar PHPUnit: la copia del proyecto no incluye `vendor/autoload.php`; tampoco está disponible el comando `composer` en este entorno. No se debe interpretar la comprobación sintáctica como sustituto de la suite de pruebas.

## Puntos de entrada/salida y CSS

- Entrada: ruta `admin.salas.distribucion` → `SalaAdminController::distribucion` → `resources/views/admin/salas/distribucion.blade.php` → componente Livewire.
- Salida: enlace a `admin.salas.index`. Antes de añadir una protección de salida, confirmar cómo se expone el estado `hayCambiosPendientes` y cómo debe cubrirse la navegación del navegador sin interferir con Livewire.
- Alcance de estilos: página de distribución y clases específicas del editor; no cambiar `resources/views/admin/layout.blade.php` salvo que una limitación demostrada lo haga imprescindible.

## Estado

Etapa 1 parcialmente completada: copia base y hashes preparados; reglas y puntos de entrada revisados; sintaxis PHP validada. Pendiente para cerrar la etapa: ejecutar la suite en un entorno con dependencias instaladas y comprobar contra el entorno original que las 30 pruebas pasan antes de modificar la interfaz.
