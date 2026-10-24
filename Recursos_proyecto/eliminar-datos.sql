USE cine_prime_v2;

-- 1. Desactivar temporalmente la revisión de llaves foráneas
SET FOREIGN_KEY_CHECKS = 0;

-- 2. Tu bloque de TRUNCATE
TRUNCATE TABLE asiento;
TRUNCATE TABLE categoria_producto;
TRUNCATE TABLE funcion;
TRUNCATE TABLE genero;
TRUNCATE TABLE compra;
TRUNCATE TABLE comprador;
TRUNCATE TABLE detalle_compra;
TRUNCATE TABLE entrada;
TRUNCATE TABLE funcion_asiento;
TRUNCATE TABLE idioma;
TRUNCATE TABLE pago;
TRUNCATE TABLE pais;
TRUNCATE TABLE pelicula;
TRUNCATE TABLE pelicula_credito;
TRUNCATE TABLE pelicula_multimedia;
TRUNCATE TABLE pelicula_pais;
TRUNCATE TABLE persona;
TRUNCATE TABLE producto;
TRUNCATE TABLE reembolso;
TRUNCATE TABLE sala;
TRUNCATE TABLE ticket;

-- 3. Volver a activar la revisión de llaves foráneas
SET FOREIGN_KEY_CHECKS = 1;