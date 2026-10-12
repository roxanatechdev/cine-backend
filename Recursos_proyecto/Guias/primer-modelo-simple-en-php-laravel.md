# Primer Modelo Simple en PHP Laravel (Entidad Película)

En esta guía aprenderemos cómo estructurar y conectar un modelo simple en Laravel utilizando nuestra entidad de **Película**, desde la base de datos hasta mostrarlo en la interfaz web.

## Paso 1: ¿Qué es un Modelo en Laravel y para qué sirve?

En Laravel, el **Modelo** es una clase de PHP que representa una tabla de tu base de datos (por ejemplo, la tabla `pelicula`).

- Actúa como un puente entre tu código y la base de datos.
- Te permite buscar, crear, actualizar o eliminar registros usando código limpio de PHP (mediante una tecnología llamada _Eloquent ORM_) sin tener que escribir consultas SQL complejas a mano todo el tiempo.

## Paso 2: Crear el Modelo para la Película

Para crear un modelo limpio y vacío, usamos la terminal de Laravel dentro de la carpeta de tu proyecto (`cine-backend`).

1. Abre tu terminal y ejecuta el siguiente comando:

```Bash
php artisan make:model Pelicula
```

2. Esto creará un archivo en la ruta: `app/Models/Pelicula.php`.

### Configuración clave del Modelo

Como nuestra tabla en MySQL se llama singularmente `pelicula` (y no el plural predeterminado `peliculas`) y su llave primaria es `id_pelicula` (en lugar de `id`), debemos indicárselo explícitamente a Laravel dentro del archivo `app/Models/Pelicula.php`:

```PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelicula extends Model
{
    // Nombre exacto de la tabla en MySQL
    protected $table = 'pelicula';

    // Llave primaria personalizada
    protected $primaryKey = 'id_pelicula';

    // Desactivar timestamps automáticos si la tabla no tiene created_at / updated_at
    public $timestamps = false;

    // Campos que permitimos rellenar masivamente
    protected $fillable = [
        'titulo',
        'sinopsis',
        'imagen',
        'genero',
        'clasificacion',
        'duracion_minutos',
        'fecha_estreno',
        'trailer',
        'estado',
    ];
}
```

_⚠️ **Nota importante:** Asegúrate de que la etiqueta `<?php` sea la primera línea absoluta del archivo, sin espacios ni líneas en blanco arriba, para evitar errores de _namespace_._

## Paso 3: Crear el Controlador (`PeliculaController`)

El **Controlador** es el intermediario: recibe la petición web (o de la API), le pide los datos al Modelo (la película), y devuelve una respuesta (en este caso, en formato JSON).

1. Ejecuta el comando en tu terminal:

```Bash
php artisan make:controller PeliculaController
```

2. Se creará el archivo en: `app/Http/Controllers/PeliculaController.php`.
3. Abre el archivo y añade la lógica para consultar las películas activas:

```PHP
<?php

namespace App\Http\Controllers;

use App\Models\Pelicula;
use Illuminate\Http\JsonResponse;

class PeliculaController extends Controller
{
    public function index(): JsonResponse
    {
        // Consultamos todas las películas que tengan estado 'ACTIVA' ordenadas por título
        $peliculas = Pelicula::query()
            ->where('estado', 'ACTIVA')
            ->orderBy('titulo')
            ->get();

        // Devolvemos los datos en formato JSON para que cualquier frontend pueda leerlos
        return response()->json($peliculas);
    }
}
```

## Paso 4: Registrar la Ruta y Probar el Endpoint

Para que el navegador o tu frontend puedan invocar al controlador, necesitamos registrar una ruta web que responda con JSON.
1. Abre el archivo de rutas: `routes/web.php`.
2. Añade la importación del controlador y la ruta GET:

```PHP
use App\Http\Controllers\PeliculaController;

// Ruta API pública de prueba para listar películas
Route::get('/api/peliculas', [PeliculaController::class, 'index']);
```

### ¿Cómo probarlo?

1. Enciende tu servidor local de Laravel ejecutando: `php artisan serve` (si usas el entorno de Laragon, tu dominio local ya estará activo).
2. Abre tu navegador web y visita la dirección:
    `[http://127.0.0.1:8000/api/peliculas](http://127.0.0.1:8000/api/peliculas)` (o tu enlace de Laragon).
3. Deberás ver una respuesta en formato JSON (si cargaste datos previamente, los verás en forma de lista de objetos; si está vacío, devolverá `[]`).

## Paso 5: Conectar el Modelo (Backend) con el Frontend

Ahora que Laravel nos entrega los datos en formato JSON mediante la URL, podemos consumirlos desde nuestro frontend independiente (creado con HTML, Bootstrap y JavaScript puro) usando la función `fetch()`.
1. En tu archivo de JavaScript (por ejemplo, `js/peliculas.js`), añade la lógica para conectarte a Laravel y pintar las tarjetas:

```JavaScript
document.addEventListener('DOMContentLoaded', () => {
    const API_URL = 'http://127.0.0.1:8000/api/peliculas';

    // Contenedor HTML donde se mostrarán las películas
    const contenedor = document.querySelector('#cartelera-section'); 

    if (!contenedor) return;

    fetch(API_URL)
        .then(response => response.json())
        .then(peliculas => {
            contenedor.innerHTML = ''; // Limpiamos texto de carga

            if (peliculas.length === 0) {
                contenedor.innerHTML = '<p>No hay películas disponibles.</p>';
                return;
            }

            // Recorremos cada película y construimos su tarjeta visual con Bootstrap
            peliculas.forEach(pelicula => {
                const tarjetaHtml = `
                    col-md-3 mb-4">
                        <div class="card bg-dark text-white h-100">
                            <img src="${pelicula.imagen}" class="card-img-top" alt="${pelicula.titulo}" style="height: 300px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title">${pelicula.titulo}</h5>
                                <p class="card-text text-muted">${pelicula.genero || 'General'}</p>
                                <span class="badge bg-secondary">${pelicula.clasificacion || 'ATP'}</span>
                                <p class="small mt-2">${pelicula.sinopsis}</p>
                            </div>
                        </div>
                    </div>
                `;
                contenedor.innerHTML += tarjetaHtml;
            });
        })
        .catch(error => console.error('Error al conectar con la API de Laravel:', error));
});
```

¡Listo! Con esto ya tienes conectado tu primer modelo simple de Laravel interactuando de forma limpia con tu base de datos y reflejándose directamente en tu diseño frontend.
