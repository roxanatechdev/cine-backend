# Informe · Datos de demostración de CinePrime v2

## 1. Qué genera el seeder (valores aproximados con la configuración por defecto)

| Tabla | Filas aprox. |
|---|---|
| idioma / pais / genero / categoria_producto | 11 / 21 / 13 / 7 |
| pelicula | 32 (25 cartelera, 5 próximamente, 1 BORRADOR, 1 INACTIVA) |
| pelicula_pais / pelicula_multimedia | ~45 / 128 (4 tipos por película) |
| persona / pelicula_credito | ~330 / ~370 |
| sala / asiento / sala_pasillo | 7 / 496 / ~130 |
| producto | 40 |
| funcion | ~900–1000 (4 salas × 3–5 funciones × ~61 días) |
| funcion_asiento | ~100 000 |
| comprador | 80 |
| compra / entrada | ~600 / ~1 700 |
| pago / ticket | ~500 / ~350 |
| detalle_compra | ~800 |
| reembolso | 0 |

## 2. Archivos y destino

| Archivo | Copiar en |
|---|---|
| `CinePrimeDemoSeeder.php` | `database/seeders/` |
| `catalogos.php`, `peliculas.php`, `productos.php` | `database/seeders/data/` |
| `INFORME_DATOS_DEMO.md` | raíz del proyecto (opcional) |

## 3. Cómo ejecutarlo

```bash
php artisan storage:link        # solo si no existe public/storage
php artisan db:seed --class=CinePrimeDemoSeeder
```

`LIMPIAR_ANTES` (constante al inicio del seeder):
- `true` (por defecto): vacía las tablas de dominio (TRUNCATE con FK desactivadas, reinicia AUTO_INCREMENT) y vuelve a sembrar. Avisa en consola.
- `false`: aborta si ya hay películas.

No toca `users`, `migrations` ni tablas de Laravel/Fortify. Aborta en producción.

## 4. Suposiciones

- **Cartelera vs. próximamente:** cartelera = `ACTIVA` con `fecha_estreno` ≤ hoy y funciones; próximamente = `ACTIVA` con estreno futuro y sin funciones.
- **Zona horaria:** la de la app (`config('app.timezone')`), con `Carbon::now()`. Se imprime al iniciar.
- **Rango de funciones:** día 1 del mes actual hasta el último día del mes siguiente, todos los días.
- **Imágenes:** se usa `storage/app/public/peliculas/placeholder.<ext>` (y `productos/`). Si no existe ninguno, se crea `placeholder.svg`. La URL guardada es `/storage/peliculas/placeholder.<ext>` porque no había vistas con ejemplos de renderizado; requiere `storage:link`.
- **Próximamente (fechas ficticias de prueba):** Interstellar (+7 días), El viaje de Chihiro (+14), Shrek (+21), El laberinto del fauno (+35), Ciudad de Dios (+50). Son reestrenos inventados, no estrenos reales anunciados (no hubo búsqueda web).
- **Cambios sobre la lista sugerida:** se sustituyó *Godzilla Minus One* por *Shrek*, y *Roma* por *El laberinto del fauno* (Roma no tiene compositor). Se añadieron *Matrix* (BORRADOR) y *El Padrino* (INACTIVA). *Coco* y *Encanto* se tratan como películas con edición 3D plausible.
- **Datos de películas de los que no estoy completamente seguro:** nombres de personajes en español latino (p. ej. «Morfeo», «Alegría»), la lista exacta de productores y compositores acreditados en *Top Gun: Maverick*, *Barbie* y *Ciudad de Dios*, y las fechas de estreno de *Mario* (según país) y *El laberinto del fauno*. Conviene revisarlos antes de una presentación. Las sinopsis son redacción propia.
- **IDs:** para `compra`, `funcion`, `funcion_asiento`, `asiento`, `pelicula`, `persona` y `producto` se asignan IDs explícitos consecutivos a partir de `MAX(id)+1`, en lugar de consultarlos tras insertar. Es seguro en una sola ejecución.
- **Trailer/Video:** todos usan la misma URL de YouTube (`URL_VIDEO`), de formato válido pero no relacionada con cada película.

## 5. Cómo ajustar cantidades

Constantes al inicio de `CinePrimeDemoSeeder`: `NUM_COMPRAS` (600), `NUM_COMPRADORES` (80), `PROB_DETALLE` (55 %), `MINUTOS_RESERVA` (15), `LOTE` (800), `URL_VIDEO`, `SEMILLA` (2026).

**Decisión importante sobre la ocupación.** Con ~1 000 funciones de ~100 asientos, 600 compras no pueden dar 30–85 % de ocupación a todas. El seeder aplica los rangos pedidos (pasadas 30–85 %, futuras 5–40 %, 4 casi llenas ≥ 95 % y 2 llenas) a un **subconjunto de ~30–40 funciones con ventas**, repartidas entre muchas películas; el resto queda sin ventas. Si quieres más funciones con ocupación, sube `NUM_COMPRAS` (p. ej. 3000).

## 6. Casos de prueba cubiertos

- Sala `ACTIVA` sin asientos con capacidad 96 («Sala 6») y sala `INACTIVA` sin asientos con capacidad `NULL` («Sala 7»): plantilla de salas vacías del editor.
- Sala en `MANTENIMIENTO` con 80 asientos y capacidad informada 90 (aviso de capacidad distinta).
- Asientos `ACCESIBLE` (extremos de filas A–B), `PREFERENCIAL` (filas centrales; toda la VIP) e inactivos (~1,5 %). Pasillos longitudinales y transversales.
- Funciones 2D/3D, matinés y miércoles con descuento, 3–5 funciones `CANCELADA`, `EN_CURSO` si aplica.
- 2 funciones llenas (VIP, futuras) y 4 casi llenas (Sala 1; 2 futuras y 2 pasadas): prueban «agotado».
- Compras en todos los estados; `EXPIRADA`/`CANCELADA` en asientos que luego se vuelven a vender (historial de reintentos); pagos `APROBADO`, `PENDIENTE` y `RECHAZADO`; tickets `EMITIDO`/`UTILIZADO`.
- Productos: promociones vigentes, vencidas y futuras (8), 3 `AGOTADO` con stock 0, `INACTIVO`, stock `NULL`, categoría inactiva.
- Películas en `BORRADOR`, `INACTIVA` y géneros sin películas (Aventura, Documental).

**Nota sobre `compra.total`:** en compras `CANCELADA`/`EXPIRADA` conserva el monto original (entradas + detalles), tal como se pidió; la verificación 4 las compara con todas las entradas, y con las vigentes en el resto.

## 7. Qué no pude verificar

- **No había PHP en el entorno:** el código **no se ha ejecutado ni pasado por `php -l`**. Puede haber errores de sintaxis o de ejecución. Lo primero que debes hacer es `php -l` sobre los cuatro archivos y una primera ejecución en una base de pruebas.
- No se probó en MySQL 8.4.3. Riesgos: el tiempo de inserción (~100 000 filas), consultas de verificación pesadas (solapamiento de funciones) y que las películas de «próximamente» o la regla de ≥ 3 funciones por semana por película no se cumplan en algún mes (se imprime un aviso, no un error).
- Las verificaciones del seeder lanzan excepción si se viola alguna regla dura; los avisos de 3D y de funciones por semana solo se muestran.
- No se revisaron vistas ni modelos reales (no estaban en el proyecto): la ruta de imágenes es una suposición.

## 8. Cómo revertir

Vuelve a ejecutar el seeder (con `LIMPIAR_ANTES=true` reinicia todo) o usa `php artisan migrate:fresh` (esto también borra `users`).

## Recomendación

Si quieres incluirlo en el flujo normal, añade `$this->call(CinePrimeDemoSeeder::class);` en `DatabaseSeeder`. No se ha modificado.
