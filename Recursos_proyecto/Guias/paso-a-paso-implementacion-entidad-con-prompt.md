### 1. El objetivo principal (¿Para qué sirve este prompt?)

Sirve para que, cada vez que queramos agregar una nueva tabla o sección a nuestro sistema (una entidad), sigamos un orden estricto. En programación, si haces las cosas a la loca, todo falla; este prompt nos obliga a ser ordenados desde la base de datos hasta la pantalla que ve el usuario.

### Las 10 Etapas Explicadas de Forma Sencilla:

#### 📌 Etapa 1: Diagnóstico de la entidad (Investigar antes de tocar)

- **¿Qué hacemos?** Antes de escribir una sola línea de código, revisamos cómo está creada la tabla en la base de datos (qué columnas tiene, cómo se llama, qué campos son obligatorios).
- **¿Por qué?** Porque si inventamos un nombre de columna que no existe en la base de datos (por ejemplo, escribir `nombre_pelicula` cuando en la base de datos se llama `titulo`), Laravel dará un error y nada funcionará. Primero miramos, luego actuamos.

#### 📌 Etapa 2: Definir el alcance (¿Qué vamos a hacer exactamente?)

- **¿Qué hacemos?** Decidimos qué funciones tendrá esta sección: ¿Solo vamos a listar las películas, o también vamos a poder crearlas, editarlas y borrarse?
- **¿Por qué?** Para no perder el tiempo programando cosas de más que no nos pidieron. Trabajamos con un plan claro.

#### 📌 Etapa 3: Revisión del Modelo Eloquent (El puente con la base de datos)

- **¿Qué hacemos?** Configuramos el archivo del **Modelo** en Laravel (por ejemplo, `Pelicula.php`) para que se conecte perfectamente con la tabla de la base de datos, indicándole su nombre exacto y su llave primaria.
- **¿Por qué?** El modelo es como un traductor: le permite a PHP hablar con la base de datos usando un lenguaje limpio y fácil, sin tener que escribir código SQL difícil.

#### 📌 Etapa 4: Implementación del Controlador (El cerebro de las operaciones)

- **¿Qué hacemos?** Creamos el **Controlador** (`PeliculaController.php`), que es el que recibe las órdenes cuando el usuario hace clic en un botón (guardar, actualizar, eliminar).
- **¿Por qué?** El controlador toma los datos que envía el usuario, los valida (por ejemplo, que no dejen el título vacío) y le ordena al modelo que los guarde en la base de datos. Es el "intermediario" inteligente.

#### 📌 Etapa 5: Implementación de las vistas Blade solo para administrador (Lo que ve el usuario)

- **¿Qué hacemos?** Diseñamos las pantallas usando **Blade** (el sistema de plantillas de Laravel): la tabla donde se ven los registros (`index`), el formulario para rellenar (`form`), etc.
- **¿Por qué?** Para que el usuario final pueda interactuar con el sistema de forma bonita, viendo mensajes de éxito o errores si algo salió mal.

#### 📌 Etapa 6: Relaciones y subrecursos (Conectar tablas entre sí)

- **¿Qué hacemos?** Si una película pertenece a un género o tiene actores, aquí conectamos esas tablas.
- **¿Por qué?** En un sistema real las cosas se relacionan (una película tiene un género, un usuario tiene un rol). Esta etapa nos enseña a unirlas correctamente sin duplicar información.

#### 📌 Etapa 7: Gestión de imágenes y archivos si la entidad te lo pide (Subir afiches o fotos)

- **¿Qué hacemos?** Configuramos cómo la aplicación va a guardar las imágenes (por ejemplo, el póster de una película) ya sea en una carpeta local o mediante un enlace web.
- **¿Por qué?** Para asegurarnos de que las imágenes se guarden bien, no ocupen espacio innecesario y se muestren correctamente en la pantalla sin romper la página si falta alguna.

#### 📌 Etapa 8: Integración con rutas y navegación (Las direcciones web)

- **¿Qué hacemos?** Registramos las direcciones URL en el archivo `routes/web.php` para que, cuando el usuario entre a `tuproyecto.test/peliculas`, el sistema sepa a qué controlador y función debe llamar.
- **¿Por qué?** Sin rutas, las páginas son invisibles; son el mapa que conecta los clics del usuario con nuestro código.

#### 📌 Etapa 9: Validación y pruebas (Asegurarnos de que funcione)

- **¿Qué hacemos?** Probamos que todo sirva: que el formulario guarde, que si escribes mal algo te avise, que los botones lleven a donde deben.
- **¿Por qué?** Para estar 100% seguros de que nuestro código funciona antes de decir "ya terminé". Nunca hay que dar por hecho que algo sirve sin probarlo.

#### 📌 Etapa 10: Cierre y documentación (El reporte final)

- **¿Qué hacemos?** Anotamos qué archivos creamos, qué cambiamos y qué dejamos listo.
- **¿Por qué?** Para que, si otro compañero (o tú mismo la próxima semana) lee el proyecto, sepa exactamente qué se hizo y cómo funciona.

### 💡 En resumen para un principiante:

Este prompt te lleva de la mano como un mapa: **Primero miras la base de datos $\rightarrow$ Creas el puente (Modelo) $\rightarrow$ Creas la lógica (Controlador) $\rightarrow$ Creas la pantalla (Vista) $\rightarrow$ Lo conectas con una dirección (Ruta) $\rightarrow$ Lo pruebas.**
