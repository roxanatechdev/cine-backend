\# CINEPREMIUM — Descripción funcional y técnica del editor de distribución de salas



La página de distribución de salas funciona como una pizarra interactiva donde el administrador puede crear y editar la distribución de los asientos, cambiar sus tipos y estados, gestionar selecciones individuales o múltiples y revisar la distribución antes de guardarla.



La idea principal es que el administrador trabaje visualmente sobre el plano, sin tener que abrir una página diferente para cada operación.



\## 1. Funcionalidades de la interfaz



\### Herramientas de la pizarra



\- Seleccionar asiento: permite seleccionar un asiento para consultar sus propiedades y acceder a las acciones disponibles.

\- Crear asiento: permite incorporar un asiento en una posición disponible de la sala, respetando las reglas de distribución.

\- Pincel de tipo: permite cambiar el tipo de varios asientos consecutivamente, por ejemplo, estándar, preferencial o accesible.

\- Pincel de estado: permite marcar asientos como activos o inactivos.

\- Mover pizarra: permite desplazar el plano sin modificar la distribución.

\- Zoom: permite acercar, alejar y ajustar la vista para trabajar con salas de distintos tamaños.



Los pinceles permanecen activos hasta que el administrador selecciona otra herramienta. Así puede realizar varias operaciones consecutivas sin repetir pasos.



\### Selección individual y múltiple



\- Al seleccionar un asiento, la interfaz muestra sus propiedades y las acciones disponibles.

\- Al seleccionar varios asientos, aparece una barra contextual con el número de elementos seleccionados y las operaciones aplicables al conjunto.

\- Las acciones masivas permiten modificar propiedades compatibles de varios asientos a la vez.

\- Cuando hay exactamente dos asientos seleccionados, puede ofrecerse la acción de intercambiar sus posiciones, si ambos cumplen las reglas de negocio.



\### Estado y disponibilidad



Cada asiento tiene un tipo y un estado de disponibilidad.



\- Un botón o pincel permite cambiar el estado de un asiento individual.

\- Mediante la selección múltiple y la acción correspondiente, se puede cambiar el estado de varios asientos.

\- Los asientos que tienen historial de funciones conservan su identidad y no pueden cambiar de tipo ni eliminarse.

\- La disponibilidad de estos asientos sí puede cambiar, de acuerdo con las reglas de negocio existentes.



\### Paneles de trabajo



\- Panel de herramientas: contiene las herramientas de selección, creación, pinceles y navegación.

\- Pizarra central: representa visualmente la sala y sus asientos.

\- Barra contextual: muestra acciones relacionadas con el asiento o los asientos seleccionados.

\- Inspector: muestra las propiedades, el resumen y las advertencias de la distribución.

\- Panel de guardado: indica si existen cambios pendientes y permite guardar la distribución o cancelar las modificaciones.



La distribución de los paneles se adapta a escritorio, tableta y móvil.



\## 2. Cómo funciona el flujo de edición



El flujo esperado es el siguiente:



1\. El servidor carga la distribución persistida de la sala.

2\. La interfaz inicializa el plano y prepara el estado de edición.

3\. El administrador selecciona herramientas, crea asientos o modifica los existentes.

4\. Las operaciones se aplican al borrador de la distribución.

5\. La interfaz actualiza la representación visual y las propiedades correspondientes.

6\. Se ejecutan las validaciones necesarias, incluyendo las restricciones de los asientos protegidos.

7\. El administrador revisa el resultado y las advertencias.

8\. Al pulsar Guardar distribución, se envía el borrador al servidor para validarlo y persistirlo.

9\. Si el guardado es correcto, el estado original se actualiza y la interfaz deja de mostrar cambios pendientes.

10\. Si el administrador cancela, el borrador se descarta y se recupera la distribución persistida.



Las operaciones realizadas antes de guardar no deben persistirse directamente en la base de datos.



\## 3. Arquitectura técnica



La arquitectura debe separar la interacción visual, el estado del borrador y la persistencia.



| Capa            | Responsabilidad                                                                                                                     |

| --------------- | ----------------------------------------------------------------------------------------------------------------------------------- |

| Blade           | Estructura HTML de la página, paneles, herramientas y elementos del plano.                                                          |

| CSS             | Apariencia de la pizarra, estados activos, paneles y adaptación responsive.                                                         |

| Alpine.js       | Interacciones locales, herramienta activa, pinceles, selección visual, zoom, desplazamiento y actualización inmediata del borrador. |

| Livewire        | Comunicación con el servidor, validaciones, reglas de negocio y coordinación del guardado.                                          |

| Backend Laravel | Validación definitiva, comprobación de permisos e invariantes, y persistencia transaccional.                                        |

| Base de datos   | Almacenamiento definitivo de asientos, tipos, disponibilidad, posiciones y pasillos.                                                |



Importante: esta es la arquitectura objetivo. No debemos asumir que todas las operaciones ya se ejecutan exclusivamente en Alpine.js sin revisar el código instalado. Si actualmente algunos cambios llaman a Livewire inmediatamente, habrá que comprobar qué llamadas conviene mantener y cuáles se pueden sustituir por edición local.



\## 4. Ejemplo del JSON enviado para guardar



Como propuesta de contrato de datos, el cliente podría enviar una estructura similar a esta:



```

{

&#x20; "sala\_id": 12,

&#x20; "asientos": \[

&#x20;   {

&#x20;     "id": 101,

&#x20;     "fila": "A",

&#x20;     "numero": 1,

&#x20;     "tipo": "ESTANDAR",

&#x20;     "activo": true

&#x20;   },

&#x20;   {

&#x20;     "id": 102,

&#x20;     "fila": "A",

&#x20;     "numero": 2,

&#x20;     "tipo": "PREFERENCIAL",

&#x20;     "activo": true

&#x20;   },

&#x20;   {

&#x20;     "id": null,

&#x20;     "fila": "A",

&#x20;     "numero": 3,

&#x20;     "tipo": "ACCESIBLE",

&#x20;     "activo": true

&#x20;   }

&#x20; ],

&#x20; "pasillos": {

&#x20;   "longitudinales": \[

&#x20;     {

&#x20;       "fila": "A",

&#x20;       "despues\_de\_numero": 2

&#x20;     }

&#x20;   ],

&#x20;   "transversales": \[

&#x20;     {

&#x20;       "fila": "B",

&#x20;       "fila\_siguiente": "C"

&#x20;     }

&#x20;   ]

&#x20; }

}

```



En este ejemplo:



\- `sala\_id` identifica la sala que se está editando.

\- `asientos` contiene la distribución deseada.

\- `id` identifica un asiento persistido; `null` indica uno nuevo.

\- `fila` y `numero` definen su posición.

\- `tipo` determina la categoría del asiento.

\- `activo` indica su disponibilidad.

\- `pasillos` contiene la definición de los pasillos longitudinales y transversales.



Este JSON es ilustrativo, no el contrato confirmado del proyecto actual. Antes de implementarlo debemos contrastarlo con las columnas reales, los métodos de Livewire y el formato de los borradores existentes. También habría que definir cómo representar eliminaciones y qué campos son editables según el historial de cada asiento.



\## 5. Endpoint de persistencia



Si la implementación utiliza Livewire, el guardado normalmente se realiza invocando un método del componente, por ejemplo:



```

$this->guardarDistribucion($payload);

```



Esto es ilustrativo: el nombre y la firma deben corresponder al método real del proyecto. Livewire transporta la llamada mediante su propio mecanismo; no necesariamente existe una ruta HTTP REST dedicada que reciba directamente el JSON.



Si decidimos utilizar un endpoint específico, podría ser una ruta `PUT` para actualizar la distribución, pero no conviene crearla sin necesidad si el componente Livewire ya resuelve el flujo de forma segura.



En cualquiera de las dos alternativas, el servidor debe:



1\. Verificar autorización sobre la sala.

2\. Validar la estructura y los tipos de los datos.

3\. Confirmar que los identificadores de asiento pertenecen a esa sala.

4\. Impedir cambios de tipo o eliminaciones no permitidas en asientos protegidos.

5\. Validar posiciones, duplicados y pasillos.

6\. Guardar los cambios dentro de una transacción.

7\. Rechazar el conjunto completo si contiene errores que impiden una distribución válida.



El cliente nunca debe ser la autoridad definitiva de las reglas de negocio. Aunque Alpine.js gestione el borrador, Laravel debe volver a validar los datos antes de escribirlos.



\## 6. Resultado esperado



El objetivo final es que el administrador pueda editar una sala de manera fluida, como si trabajara sobre una pizarra:



\- Las acciones visuales se ejecutan sin recargar la página.

\- Los pinceles se mantienen activos durante las operaciones consecutivas.

\- Los cambios se reflejan inmediatamente en el plano.

\- La selección múltiple permite operaciones masivas.

\- Los asientos protegidos respetan sus restricciones.

\- Guardar envía una única distribución coherente al servidor.

\- Cancelar restaura el estado persistido.

\- Las validaciones del servidor impiden guardar una distribución inconsistente.



Para cerrar la documentación técnica con precisión, el siguiente paso sería contrastar esta descripción con el componente Livewire, la vista Blade y los métodos de guardado reales de CINEPREMIUM, de modo que podamos distinguir claramente lo que ya está implementado de lo que todavía forma parte del diseño objetivo.

