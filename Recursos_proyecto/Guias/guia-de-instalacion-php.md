# Guía de Instalación y Primeros Pasos con PHP Laravel (Con Laragon)

¡Bienvenido al mundo de **Laravel**! Si estás empezando, es normal que tantos términos nuevos suenen confusos. Esta guía combina los conceptos teóricos fundamentales, los requisitos previos y el uso específico de **Laragon** en Windows para que tengas un entorno de desarrollo profesional, rápido y completo desde cero.

---
## 1. Conceptos Fundamentales: ¿Qué necesitamos entender antes de empezar?

Antes de escribir código, Laravel y tu computadora necesitan un "terreno preparado". Conozcamos las herramientas principales que intervienen:

* **¿Qué es un servidor local y para qué sirve?**

Cuando desarrollas una página web o un sistema, tu computadora necesita "actuar" como si estuviera en internet para poder ejecutar código de servidor (**PHP**) y guardar información en una base de datos (**MySQL**). Un servidor local simula ese entorno dentro de tu propia computadora para que puedas probar tu sistema antes de subirlo a producción.

* **PHP:** Es el lenguaje de programación en el que está escrito Laravel. Piensa en él como el motor de un carro. Tu computadora necesita tener PHP instalado para entender las órdenes.

* **Composer:** Es el **gestor de paquetes de PHP**. Imagínalo como una tienda de aplicaciones (como la App Store o Google Play), pero para programadores. Lo usaremos para descargar Laravel y todas las herramientas que necesita automáticamente.

* **Node.js y NPM:**

* *¿Qué son?* Node.js es un entorno que permite ejecutar JavaScript fuera del navegador, y NPM (Node Package Manager) es el gestor de paquetes que lo acompaña para instalar herramientas y librerías frontend.

* *¿Es necesario?* Sí, es necesario si vas a compilar componentes o si decides integrar frameworks/librerías de frontend (como React o herramientas de diseño en Laravel).

* **Una Base de Datos (como MySQL o SQLite):** Es el lugar donde guardaremos la información de los usuarios, contraseñas, registros, etc.

* **Laravel:** Es el **Framework**. Un framework es como un kit de construcción de casas que ya viene con los cimientos, las tuberías y las puertas hechas. En lugar de construir una página web desde cero, Laravel te da una estructura limpia, segura y rápida para trabajar.

---

## 2. Laragon: Nuestra opción elegida y sus ventajas

Existen varias herramientas para crear un servidor local (como XAMPP o WampServer), pero para este proyecto elegimos **Laragon en Windows**.

### Ventajas principales de Laragon:

* **Es ultrarrápido y ligero:** Consume muy pocos recursos de tu computadora.

* **Viene "todo en uno":** Incluye PHP, MySQL, Apache/Nginx, Composer y Node.js listos para usar sin configuraciones complejas de variables de entorno manuales.

* **Crea URLs automáticas:** Si creas un proyecto en su carpeta correspondiente, Laragon automáticamente te da una dirección amigable (por ejemplo, `[http://nombre-proyecto.test](http://nombre-proyecto.test)`) para abrirlo en tu navegador.

* **Fácil de usar:** Su interfaz es muy limpia y amigable para principiantes.

---

## 3. Configuración y uso de Laragon
### 3.1. Encender Laragon y usar su interfaz

1. **Abrir Laragon:** Busca Laragon en tu menú de inicio de Windows y ábrelo. Verás una ventana pequeña con un botón grande en el centro.

2. **Encender los servicios:** Haz clic en el botón **"Start All" (Iniciar todo)**. Esto encenderá el servidor web (Apache/Nginx) y la base de datos MySQL.

### 3.2. Usar la terminal integrada

1. Haz clic derecho en cualquier parte vacía de la ventana principal de Laragon.

2. Selecciona **Terminal**.

3. Se abrirá una consola (generalmente con Cmder o CMD) ya configurada con todas las herramientas de PHP, Composer y Git listas para usar.

### 3.3. Manejo de Versiones de PHP (Cómo usar PHP 8.3 o 8.4)

Cuando descargas Laragon, este suele traer una versión estable por defecto (por ejemplo, PHP 8.3). Si deseas usar una versión más reciente (como PHP 8.4):

1. Descarga el archivo ZIP de la versión de PHP que deseas para Windows (**"Thread Safe"**) desde la web oficial de PHP.

2. Descomprime esa carpeta dentro de la ruta donde instalaste Laragon, específicamente en: `C:\laragon\bin\php\php-8.4.x...`

3. Para activarlo, ve a la interfaz de Laragon, haz clic derecho > **PHP** > **Version** y selecciona la nueva versión (8.4). Laragon se encargará de reconfigurar todo automáticamente.

---
## 4. Paso a paso para instalar Laravel por primera vez

Una vez que tengas Laragon encendido y tu terminal lista, sigue estos pasos:
### Paso 1: Ubicarse en la carpeta de proyectos de Laragon

Por defecto, Laragon almacena los proyectos en `C:\laragon\www\`. Desde la terminal integrada de Laragon, puedes trabajar directamente o asegurarte de estar posicionado en dicha ruta.
### Paso 2: Crear el proyecto con Composer

Escribe el siguiente comando en tu terminal y presiona Enter:

```bash
composer create-project laravel/laravel mi-primer-proyecto
```

*¿Qué está pasando aquí?*

- `composer create-project`: Le dice a Composer que descargue una plantilla base.

* `laravel/laravel`: Le indica que descargue específicamente el código oficial de Laravel.

* `mi-primer-proyecto`: Es el nombre que tendrá la carpeta de tu proyecto.

### Paso 3: Entrar a la carpeta del proyecto

Una vez que termine la descarga, entra a la carpeta creada:

```bash
cd mi-primer-proyecto
```

### Paso 4: Ejecutar el servidor o aprovechar Laragon

* **Opción A (Servidor interno de Laravel):** Puedes escribir `php artisan serve` y abrir `[http://127.0.0.1:8000](http://127.0.0.1:8000)` en tu navegador.

* **Opción B (URL automática de Laragon - Recomendada):** Gracias a Laragon, si creaste tu proyecto dentro de `C:\laragon\www\mi-primer-proyecto`, puedes ingresar directamente en tu navegador a: `[http://mi-primer-proyecto.test](http://mi-primer-proyecto.test)` sin necesidad de comandos adicionales.

---

## 5. Un vistazo rápido a la estructura de carpetas de Laravel

Cuando abras tu proyecto en un editor de código (como VS Code), verás carpetas clave:

* `app/`: Aquí vive la lógica principal de tu aplicación (controladores y modelos).

* `routes/`: Aquí defines las rutas o direcciones web de tu sistema (por ejemplo, `/inicio` o `/contacto`).

* `resources/`: Contiene las vistas y componentes de diseño visual (HTML/Blade, CSS, JS).

* `.env`: El archivo de configuración de entorno (conexión a la base de datos, credenciales y variables secretas).