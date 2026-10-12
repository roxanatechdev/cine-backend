# 🚀 Guía de Configuración Inicial del Proyecto Backend

¡Bienvenida al equipo! Esta guía paso a paso te ayudará a configurar el entorno de desarrollo y preparar el proyecto en tu computadora local usando **Laragon**.

---

## 📌 Requisitos Previos
Asegúrate de tener instalado:
* **Laragon** (con PHP, MySQL y Composer activados).
* **Git** instalado en tu sistema.

---

## 📁 Paso 1: Clonar el Repositorio

1. Abre el explorador de archivos y navega hasta la carpeta `www` de tu instalación de Laragon. Por lo general se encuentra en:
   ```text
   C:\laragon\www
   ```
2. Haz clic derecho dentro de la carpeta `www` y selecciona **Git Bash Here** (o abre tu terminal favorita en esa ruta).
3. Clona el proyecto ejecutando el siguiente comando:
   ```bash
   git clone <URL_DEL_REPOSITORIO>
   ```

---

## 🔌 Paso 2: Iniciar Servidores en Laragon

1. Abre la aplicación **Laragon**.
2. Haz clic en el botón **"Iniciar todo"** (*Start All*).
3. Asegúrate de que los servicios de **Apache/Nginx** y **MySQL** estén encendidos (deben mostrar una luz verde o estar en estado activo).

---

## 🗄️ Paso 3: Crear la Base de Datos

Puedes crear la base de datos de dos formas. Elige la que te sea más cómoda:

### Opción A: Desde la interfaz de HeidiSQL (Recomendado si prefieres clics)
1. En Laragon, haz clic en el botón **Database** o Base de Datos (se abrirá HeidiSQL).
2. Conéctate (normalmente el usuario es `root` y la contraseña está en blanco).
3. Haz clic derecho sobre el panel izquierdo en el listado de bases de datos.
4. Selecciona **Crear nuevo** > **Base de datos**.
5. Escribe el nombre de tu base de datos (por ejemplo: `cine_backend_db`) y guarda los cambios.

### Opción B: Desde la Consola/CMD
1. Abre tu terminal o CMD.
2. Ingresa a MySQL con el usuario `root`:
   ```bash
   mysql -u root
   ```
3. Crea la base de datos ejecutando la consulta SQL (no olvides el punto y coma `;` al final):
   ```sql
   CREATE DATABASE nombre_base_datos;
   ```
4. Para salir de MySQL, escribe `exit;`.

---

## 📝 Paso 4: Configurar el Archivo `.env`

1. Entra a la carpeta del proyecto que acabas de clonar.
2. Copia el archivo de plantilla `.env.example` y renómbralo a `.env`.
   *(Puedes hacerlo manualmente desde el explorador de archivos o con el comando `cp .env.example .env` en la terminal).*
3. Abre el archivo `.env` con tu editor de código (VS Code, Sublime Text, etc.).
4. Busca el bloque de configuración de la base de datos y actualízalo con los datos correspondientes:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tu_nombre_base_datos
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   > ⚠️ **Importante:** En la variable `DB_DATABASE`, coloca el nombre **exacto** con el que creaste tu base de datos en el Paso 3.

---

## 🖥️ Paso 5: Abrir la Terminal de Laragon y Ubicarte en el Proyecto

1. En la ventana principal de Laragon, haz clic en el botón **Terminal**.
2. Navega hacia la carpeta del proyecto ejecutando:
   ```bash
   cd cine-backend
   ```
3. *(Opcional)* Si es la primera vez que clonas el proyecto, ejecuta:
   ```bash
   composer install
   php artisan key:generate
   ```

---

## 🔍 Paso 6: Verificar la Conexión a la Base de Datos

Para asegurarte de que Laravel está detectando la base de datos correcta y verificar los datos de la conexión, puedes usar el comando interactivo de Laravel (**Tinker**):

1. Abre Tinker ejecutando:
   ```bash
   php artisan tinker
   ```
2. Una vez dentro de la consola interactiva (`>`), consulta el nombre de la base de datos configurada ejecutando:
   ```php
   DB::connection()->getDatabaseName();
   ```
   *Debería devolverte el nombre exacto de la base de datos que especificaste en tu `.env`.*
3. Para salir de Tinker, escribe:
   ```php
   exit
   ```

---

## ⚙️ Paso 7: Verificar y Ejecutar las Migraciones

En este proyecto trabajamos con un conjunto específico de migraciones en la subcarpeta `v2`.

### 1. Simulación (Pretend)
Antes de modificar la base de datos, ejecuta el comando con la bandera `--pretend` para revisar que Laravel reconozca las migraciones correctamente:

```bash
php artisan migrate --database=mysql --path=database/migrations/v2 --pretend
```

> **¿Qué verificar?** Si el comando te muestra la lista de sentencias SQL y archivos que coinciden con el contenido de la carpeta `database/migrations/v2`, todo está funcionando correctamente.

### 2. Ejecución Real de las Migraciones
Si todo salió bien en la simulación, ejecuta la migración real:

```bash
php artisan migrate --database=mysql --path=database/migrations/v2
```

---

## ✅ Paso 8: Confirmar la Creación de Tablas

Puedes verificar que las tablas se hayan creado en tu base de datos de 3 formas:

1. **Desde HeidiSQL:** Abre la base de datos e inspecciona la lista de tablas en el panel izquierdo.
2. **Desde MySQL en CMD:**
   ```sql
   USE tu_nombre_base_datos;
   SHOW TABLES;
   ```
3. **Desde Artisan (Consola de Laragon):**
   ```bash
   php artisan migrate:status --database=mysql --path=database/migrations/v2
   ```
   *Verás una tabla interactiva mostrando en verde las migraciones aplicadas (`Ran` / `Yes`).*

---

## 🎉 ¡Listo para Empezar!

Una vez completados estos pasos, la base de datos estará configurada correctamente y podrás comenzar a trabajar en la creación de tu **Modelo** asignado.

Si encuentras algún error o tienes alguna duda durante el proceso, ¡no dudes en preguntar!