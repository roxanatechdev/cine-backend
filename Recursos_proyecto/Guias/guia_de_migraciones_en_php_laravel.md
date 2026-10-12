# Guía Completa de Migraciones en Laravel

Cuando trabajamos con bases de datos en proyectos web, mantener el control de las tablas y sus estructuras puede ser complicado si se hace de forma manual. **Las migraciones** en Laravel resuelven este problema.

## 1. ¿Qué es una migración y por qué se usa?

### La analogía del Control de Versiones (como Git)

Imagina que las migraciones son como un **control de versiones (Git) pero para tu base de datos**.

En lugar de entrar a un administrador visual como PhpMyAdmin, DBeaver o la consola de MySQL para crear tablas manualmente con comandos SQL (`CREATE TABLE ...`), en Laravel creas las tablas escribiendo código PHP dentro de archivos llamados **migraciones**.

### ¿Por qué se usan? (Ventajas principales)

1. **Trabajo en equipo sin problemas:** Si creas una tabla nueva o modificas una columna en tu computadora, tus compañeros de equipo solo tienen que ejecutar un comando en sus terminales para tener exactamente la misma estructura de base de datos sin necesidad de exportar e importar archivos `.sql`.

2. **Historial de cambios:** Puedes ver cuándo y cómo cambió la base de datos a lo largo del tiempo.

3. **Deshacer cambios (Rollback):** Si cometes un error o quieres volver al estado anterior, puedes "rebobinar" o revertir la base de datos fácilmente.

4. **Independencia del motor de base de datos:** Escribes el código en PHP y Laravel se encarga de traducirlo a MySQL, PostgreSQL, SQLite o el motor que estés usando.

## 2. ¿Cómo funcionan las migraciones?

Cada archivo de migración contiene una clase PHP con dos métodos principales:

```php
public function up(): void
{
    // Código que CREA o MODIFICA tablas (se ejecuta cuando aplicas la migración)
}

public function down(): void
{
    // Código que DESTRUYE o REVIERTE lo que hizo el método up() (se ejecuta cuando das marcha atrás)
}
```

### Ejemplo de un archivo de migración:

Si creamos una migración para la tabla de usuarios (`users`), el código se ve así:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Crea un campo 'id' auto-incremental
            $table->string('name'); // Crea una columna de texto para el nombre
            $table->string('email')->unique(); // Columna de email que no se puede repetir
            $table->string('password'); // Columna para la contraseña
            $table->timestamps(); // Crea automáticamente 'created_at' y 'updated_at'
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users'); // Elimina la tabla si se revierte la migración
    }
};

```

## 3. ¿Dónde se guardan las migraciones?

Todos los archivos de migración se almacenan en la siguiente carpeta dentro de tu proyecto:
`database/migrations/`

Laravel le pone a cada archivo un prefijo con la fecha y hora de creación (ejemplo: `2026_10_10_000000_create_users_table.php`). De esta forma, Laravel sabe exactamente en qué orden debe ejecutarlas.

## 4. Comandos principales de Artisan para Migraciones

Todos los comandos de migraciones se ejecutan desde la terminal usando la herramienta interna de Laravel llamada **Artisan**.

### 1. Crear una nueva migración

Para crear un archivo de migración para una nueva tabla:

```php
php artisan make:migration create_peliculas_table
```

*Consejo:* Si usas la convención `create_nombre_table`, Laravel preparará la plantilla automáticamente para crear esa tabla.
### 2. Ejecutar las migraciones pendientes (`migrate`)

Para aplicar todas las migraciones que aún no se han ejecutado en tu base de datos:

```php
php artisan migrate
```

*¿Qué hace?* Lee los archivos en `database/migrations/` que no hayan sido procesados previamente y crea las tablas en tu base de datos.

### 3. Revertir la última operación (`migrate:rollback`)

Si acabas de ejecutar migraciones y te das cuenta de que te faltó un campo o tuviste un error:

```php
php artisan migrate:rollback
```

*¿Qué hace?* Ejecuta el método `down()` únicamente del último lote ("batch") de migraciones aplicadas.

### 4. Reiniciar toda la base de datos (`migrate:fresh`)

Es uno de los comandos más usados en desarrollo. **¡Atención! Elimina todas las tablas y datos existentes** y ejecuta todas las migraciones desde cero.

```php
php artisan migrate:fresh
```

Si además tienes datos de prueba (Seeders) preparados y quieres cargarlos inmediatamente:

```php
php artisan migrate:fresh --seed
```

### 5. Ver el estado de las migraciones (`migrate:status`)

Si quieres comprobar qué migraciones ya fueron aplicadas en la base de datos y cuáles están pendientes:

```php
php artisan migrate:status
```

## 5. Resumen del Flujo de Trabajo Típico

1. **Creas la migración:** `php artisan make:migration create_productos_table`

2. **Editas el archivo:** Abres el archivo en `database/migrations/` y defines las columnas (`$table->string('nombre');`, `$table->decimal('precio', 8, 2);`, etc.).

3. **Ejecutas el comando:** Corres `php artisan migrate` para aplicar los cambios a la base de datos real.