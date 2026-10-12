# Guía Avanzada: Modelos Complejos y Gestión de Roles y Permisos en Laravel

En la primera parte de nuestro curso aprendimos a crear un modelo simple (`Pelicula`), conectarlo a una base de datos mediante migraciones y un controlador, probarlo con rutas y consumirlo desde un frontend independiente. 

En esta segunda parte abordaremos un escenario más complejo y profesional: **la gestión de usuarios con Roles y Permisos**, indispensable para diferenciar entre un cliente común y un administrador del sistema de cine.

---

## 1. Conceptos Clave antes de Empezar

Para entender este nivel avanzado, debemos familiarizarnos con tres nuevos conceptos fundamentales en la arquitectura de aplicaciones web modernas:

### A. Relaciones Avanzadas en Bases de Datos (Eloquent ORM)
Hasta ahora hemos conectado tablas de uno a uno o uno a muchos (como una Sala que tiene muchos Asientos). Sin embargo, para los roles y permisos necesitamos una relación **Muchos a Muchos (Many-to-Many)**:
* Un **Usuario** puede tener múltiples **Roles** (ej. *Administrador*, *Cajero*, *Cliente*).
* Un **Rol** puede pertenecer a múltiples **Usuarios**.
* Para lograr esto en bases de datos relacionales, se utiliza una tabla intermedia (o pivote), comúnmente llamada `role_user`.

### B. Control de Acceso Basado en Roles (RBAC - Role-Based Access Control)
Es un enfoque para restringir el acceso al sistema según los roles asignados a los usuarios individuales. 
* En lugar de programar permisos uno por uno directamente en las páginas, agrupamos los permisos dentro de **Roles** y simplemente evaluamos si el usuario posee el rol adecuado.

### C. Paquetes de Autorización en Laravel (Spatie Laravel Permission)
Aunque podríamos escribir toda la lógica de roles y permisos desde cero, en el desarrollo profesional de Laravel se utiliza un estándar de la industria: el paquete **`spatie/laravel-permission`**. Este paquete maneja de forma limpia, segura y optimizada la asignación de roles, permisos, caché y directivas en las vistas.

---

## 2. Instalación y Configuración del Paquete de Roles y Permisos

Para integrar la gestión avanzada de permisos, seguiremos estos pasos dentro de nuestro proyecto backend (`cine-backend`):

### Paso 2.1: Instalar el paquete mediante Composer
Abre tu terminal en la raíz del proyecto y ejecuta:
```bash
composer require spatie/laravel-permission
````

### Paso 2.2: Publicar la migración y la configuración

El paquete necesita crear sus propias tablas para gestionar los roles y permisos. Para ello, publicamos los archivos de migración y configuración ejecutando:

```Bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Esto generará los archivos de migración necesarios en la carpeta `database/migrations/`.

### Paso 2.3: Ejecutar las migraciones

Ejecuta el comando para aplicar los cambios en tu base de datos MySQL (`cine_mvp`):

```Bash
php artisan migrate
```

Esto creará las siguientes tablas adicionales:

- `roles`: Almacena los nombres de los roles (ej. `admin`, `cajero`).
- `permissions`: Almacena las acciones permitidas (ej. `crear-pelicula`, `eliminar-pelicula`).
- `model_has_roles`: Tabla pivote que conecta usuarios con roles.
- `model_has_permissions`: Tabla pivote que conecta usuarios directamente con permisos específicos.
- `role_has_permissions`: Tabla pivote que conecta roles con los permisos que poseen.

## 3. Preparar el Modelo de Usuario (`User`)

Para que Eloquent sepa que nuestro modelo de usuario puede tener roles y permisos, debemos añadir el _trait_ `HasRoles` provisto por el paquete Spatie.

Abre tu archivo `app/Models/User.php` y modifíquelo de la siguiente forma:

```PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // <-- 1. Importar el Trait

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // <-- 2. Incluirlo en la clase

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Si ya manejabas un campo de rol básico
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

## 4. Creación y Asignación de Roles y Permisos (Semillas / Seeders)

En lugar de crear roles manualmente desde la base de datos, Laravel nos permite usar **Seeders** para poblar datos iniciales automáticamente.
### Paso 4.1: Crear el Seeder

Ejecuta en la terminal:

```Bash
php artisan make:seeder RolePermissionSeeder
```

### Paso 4.2: Programar la lógica de creación

Abre el archivo generado en `database/seeders/RolePermissionSeeder.php` y añade el siguiente código:


```PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Limpiar caché de permisos para evitar conflictos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Crear Permisos para el Sistema de Cine
        Permission::create(['name' => 'ver cartelera']);
        Permission::create(['name' => 'gestionar peliculas']);
        Permission::create(['name' => 'gestionar funciones']);
        Permission::create(['name' => 'vender entradas']);

        // 3. Crear Roles y asignarles Permisos
        $adminRole = Role::create(['name' => 'Administrador']);
        // El administrador obtiene todos los permisos automáticamente
        $adminRole->givePermissionTo(Permission::all());

        $cajeroRole = Role::create(['name' => 'Cajero']);
        $cajeroRole->givePermissionTo(['ver cartelera', 'vender entradas']);

        $clienteRole = Role::create(['name' => 'Cliente']);
        $clienteRole->givePermissionTo(['ver cartelera']);

        // 4. Crear un usuario Administrador de prueba y asignarle el rol
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@cineprime.com'],
            [
                'name' => 'Administrador General',
                'password' => bcrypt('PasswordSeguro2026!'),
                'role' => 'admin'
            ]
        );
        
        $adminUser->assignRole('Administrador');
    }
}
```

### Paso 4.3: Ejecutar el Seeder

Corre el comando en tu terminal para sembrar los datos:

```Bash
php artisan db:seed --class=RolePermissionSeeder
```

## 5. Proteger Rutas y Controladores usando Roles y Permisos

Una vez configurados los roles, podemos restringir el acceso a nuestros controladores administrativos utilizando los Middleware integrados de Spatie.
### Proteger mediante Rutas (`routes/web.php` o `routes/api.php`)

Podemos exigir que el usuario autenticado posea un rol específico para acceder a ciertas URLs:

```PHP
use App\Http\Controllers\Admin\PeliculaAdminController;

Route::middleware(['auth', 'role:Administrador'])->prefix('admin')->group(function () {
    Route::resource('peliculas', PeliculaAdminController::class);
});
```
### Proteger directamente en el Controlador

También puedes validar permisos específicos dentro de los métodos de tu controlador:

```PHP
public function store(Request $request)
{
    // Verificamos si el usuario autenticado tiene el permiso requerido
    if (!auth()->user()->can('gestionar peliculas')) {
        abort(403, 'No tienes autorización para realizar esta acción.');
    }

    // Lógica para guardar la película...
}
```

¡Excelente! Con esta guía avanzada ya comprendes cómo implementar un modelo complejo de control de acceso basado en roles y permisos en Laravel utilizando estándares profesionales. Esto garantiza que la gestión de tu sistema de cine sea robusta, segura y escalable.