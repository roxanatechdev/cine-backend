-- ============================================================================
-- CinePrime v2 — Datos de prueba
-- Base: cineprime_v2
-- Requiere: cineprime_v2_schema.sql ya ejecutado
--
-- Contenido orientativo:
--   5 generos, 4 peliculas (creditos, paises, multimedia)
--   2 salas, asientos, 4 funciones sin solapamiento
--   compradores, compras (confirmada, expirada, con productos)
--   entradas, pagos, ticket, productos
--
-- Codificacion: UTF-8. Ejecutar con cliente en utf8mb4
--   (SET NAMES utf8mb4). Nombres sin caracteres problemáticos
--   por compatibilidad con consolas mal configuradas.
-- ============================================================================

USE cineprime_v2;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Limpieza opcional de datos de negocio (conserva idioma/pais del schema)
-- ---------------------------------------------------------------------------
DELETE FROM detalle_compra;
DELETE FROM ticket;
DELETE FROM reembolso;
DELETE FROM pago;
DELETE FROM entrada;
DELETE FROM compra;
DELETE FROM comprador;
DELETE FROM funcion_asiento;
DELETE FROM funcion;
DELETE FROM asiento;
DELETE FROM sala;
DELETE FROM producto;
DELETE FROM categoria_producto;
DELETE FROM pelicula_credito;
DELETE FROM persona;
DELETE FROM pelicula_multimedia;
DELETE FROM pelicula_pais;
DELETE FROM pelicula;
DELETE FROM genero;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Generos
-- ---------------------------------------------------------------------------
INSERT INTO genero (id_genero, nombre, descripcion, activo) VALUES
  (1, 'Accion', 'Peliculas de accion y aventura', 1),
  (2, 'Drama', 'Drama contemporaneo', 1),
  (3, 'Ciencia ficcion', 'Ciencia ficcion y fantasia', 1),
  (4, 'Comedia', 'Comedia', 1),
  (5, 'Animacion', 'Animacion familiar', 1);

-- ---------------------------------------------------------------------------
-- Personas y peliculas
-- ---------------------------------------------------------------------------
INSERT INTO persona (id_persona, nombre_completo, nombre_busqueda, foto) VALUES
  (1, 'Ana Torres', 'ana torres', NULL),
  (2, 'Luis Mendoza', 'luis mendoza', NULL),
  (3, 'Maria Vega', 'maria vega', NULL),
  (4, 'Carlos Ruiz', 'carlos ruiz', NULL),
  (5, 'Elena Soto', 'elena soto', NULL),
  (6, 'Pedro Nava', 'pedro nava', NULL),
  (7, 'Sofia Rios', 'sofia rios', NULL),
  (8, 'Diego Palma', 'diego palma', NULL);

INSERT INTO pelicula (
  id_pelicula, titulo, sinopsis, id_genero_principal, clasificacion,
  duracion_minutos, fecha_estreno, idioma_original_codigo, estado
) VALUES
  (1,
   'Horizonte Rojo',
   'Un piloto debe atravesar una zona de conflicto para entregar un mensaje critico.',
   1, 'PG-13', 128, '2026-09-15', 'es', 'ACTIVA'),
  (2,
   'Eco del Silencio',
   'Una periodista investiga un caso olvidado que conecta tres generaciones.',
   2, 'R', 112, '2026-08-01', 'es', 'ACTIVA'),
  (3,
   'Orbita 9',
   'Tripulantes de una estacion espacial descubren una senal de origen desconocido.',
   3, 'PG-13', 135, '2026-10-01', 'en', 'ACTIVA'),
  (4,
   'Pixel Heroes',
   'Un grupo de personajes de videojuego debe salvar su mundo digital.',
   5, 'G', 98, '2026-07-20', 'en', 'ACTIVA');

INSERT INTO pelicula_pais (id_pelicula, codigo_pais) VALUES
  (1, 'BO'),
  (1, 'MX'),
  (2, 'AR'),
  (2, 'ES'),
  (3, 'US'),
  (3, 'GB'),
  (4, 'US'),
  (4, 'JP');

INSERT INTO pelicula_credito (id_pelicula, id_persona, rol, personaje, orden) VALUES
  (1, 1, 'DIRECTOR', NULL, 1),
  (1, 2, 'ACTOR', 'Capitan Rivas', 1),
  (1, 3, 'ACTOR', 'Teniente Mora', 2),
  (1, 4, 'GUIONISTA', NULL, 1),
  (1, 5, 'COMPOSITOR', NULL, 1),
  (2, 6, 'DIRECTOR', NULL, 1),
  (2, 3, 'ACTOR', 'Laura Benitez', 1),
  (2, 7, 'ACTOR', 'Inspector Salas', 2),
  (2, 4, 'GUIONISTA', NULL, 1),
  (3, 8, 'DIRECTOR', NULL, 1),
  (3, 2, 'ACTOR', 'Commander Hale', 1),
  (3, 5, 'ACTOR', 'Dr. Quinn', 2),
  (3, 1, 'PRODUCTOR', NULL, 1),
  (4, 7, 'DIRECTOR', NULL, 1),
  (4, 6, 'ACTOR', 'Spark', 1),
  (4, 8, 'ACTOR', 'Byte', 2),
  (4, 5, 'COMPOSITOR', NULL, 1);

INSERT INTO pelicula_multimedia (
  id_pelicula, tipo, url, texto_alternativo, es_principal, orden, activo
) VALUES
  (1, 'POSTER', 'media/horizonte-rojo-poster.jpg', 'Poster Horizonte Rojo', 1, 1, 1),
  (1, 'TRAILER', 'https://example.com/trailers/horizonte-rojo', 'Trailer oficial', 1, 1, 1),
  (1, 'IMAGEN', 'media/horizonte-rojo-1.jpg', 'Fotograma 1', 0, 2, 1),
  (1, 'IMAGEN', 'media/horizonte-rojo-2.jpg', 'Fotograma 2', 0, 3, 1),
  (2, 'POSTER', 'media/eco-silencio-poster.jpg', 'Poster Eco del Silencio', 1, 1, 1),
  (2, 'TRAILER', 'https://example.com/trailers/eco-silencio', 'Trailer oficial', 1, 1, 1),
  (3, 'POSTER', 'media/orbita9-poster.jpg', 'Poster Orbita 9', 1, 1, 1),
  (3, 'TRAILER', 'https://example.com/trailers/orbita9', 'Trailer oficial', 1, 1, 1),
  (3, 'VIDEO', 'https://example.com/videos/orbita9-behind', 'Detras de camaras', 0, 2, 1),
  (4, 'POSTER', 'media/pixel-heroes-poster.jpg', 'Poster Pixel Heroes', 1, 1, 1),
  (4, 'TRAILER', 'https://example.com/trailers/pixel-heroes', 'Trailer oficial', 1, 1, 1);

-- ---------------------------------------------------------------------------
-- Salas y asientos
-- Sala A: 2 filas x 4 = 8 asientos, margen 30
-- Sala B: 2 filas x 3 = 6 asientos, margen 30
-- ---------------------------------------------------------------------------
INSERT INTO sala (id_sala, nombre, capacidad, margen_limpieza_minutos, estado) VALUES
  (1, 'Sala Premium 1', 8, 30, 'ACTIVA'),
  (2, 'Sala Estandar 2', 6, 30, 'ACTIVA');

INSERT INTO asiento (id_asiento, id_sala, fila, numero, tipo, activo) VALUES
  -- Sala 1
  (1, 1, 'A', 1, 'ESTANDAR', 1),
  (2, 1, 'A', 2, 'ESTANDAR', 1),
  (3, 1, 'A', 3, 'PREFERENCIAL', 1),
  (4, 1, 'A', 4, 'ACCESIBLE', 1),
  (5, 1, 'B', 1, 'ESTANDAR', 1),
  (6, 1, 'B', 2, 'ESTANDAR', 1),
  (7, 1, 'B', 3, 'ESTANDAR', 1),
  (8, 1, 'B', 4, 'ESTANDAR', 1),
  -- Sala 2
  (9,  2, 'A', 1, 'ESTANDAR', 1),
  (10, 2, 'A', 2, 'ESTANDAR', 1),
  (11, 2, 'A', 3, 'PREFERENCIAL', 1),
  (12, 2, 'B', 1, 'ESTANDAR', 1),
  (13, 2, 'B', 2, 'ESTANDAR', 1),
  (14, 2, 'B', 3, 'ACCESIBLE', 1);

-- ---------------------------------------------------------------------------
-- Funciones (sin solapamiento: duracion + margen 30)
-- Horizonte Rojo 128 min + 30 = 158 min ~ 2h 38m
--   F1 Sala1 2026-10-20 15:00 -> libre desde ~17:38
--   F2 Sala1 2026-10-20 18:00 OK
-- Eco 112 + 30 = 142 min
--   F3 Sala2 2026-10-20 16:00
-- Orbita 135 + 30 = 165 min
--   F4 Sala2 2026-10-21 19:00
-- ---------------------------------------------------------------------------
INSERT INTO funcion (
  id_funcion, id_pelicula, id_sala, inicio, formato, precio_base, estado
) VALUES
  (1, 1, 1, '2026-10-20 15:00:00', '2D', 35.00, 'PROGRAMADA'),
  (2, 1, 1, '2026-10-20 18:00:00', '3D', 45.00, 'PROGRAMADA'),
  (3, 2, 2, '2026-10-20 16:00:00', '2D', 30.00, 'PROGRAMADA'),
  (4, 3, 2, '2026-10-21 19:00:00', '3D', 50.00, 'PROGRAMADA');

-- Inventario de asientos por funcion (precio = precio_base de la funcion)
INSERT INTO funcion_asiento (id_funcion_asiento, id_funcion, id_asiento, precio) VALUES
  -- Funcion 1 (Sala 1, asientos 1-8)
  (1, 1, 1, 35.00), (2, 1, 2, 35.00), (3, 1, 3, 35.00), (4, 1, 4, 35.00),
  (5, 1, 5, 35.00), (6, 1, 6, 35.00), (7, 1, 7, 35.00), (8, 1, 8, 35.00),
  -- Funcion 2 (Sala 1)
  (9,  2, 1, 45.00), (10, 2, 2, 45.00), (11, 2, 3, 45.00), (12, 2, 4, 45.00),
  (13, 2, 5, 45.00), (14, 2, 6, 45.00), (15, 2, 7, 45.00), (16, 2, 8, 45.00),
  -- Funcion 3 (Sala 2, asientos 9-14)
  (17, 3, 9, 30.00), (18, 3, 10, 30.00), (19, 3, 11, 30.00),
  (20, 3, 12, 30.00), (21, 3, 13, 30.00), (22, 3, 14, 30.00),
  -- Funcion 4 (Sala 2)
  (23, 4, 9, 50.00), (24, 4, 10, 50.00), (25, 4, 11, 50.00),
  (26, 4, 12, 50.00), (27, 4, 13, 50.00), (28, 4, 14, 50.00);

-- ---------------------------------------------------------------------------
-- Productos
-- ---------------------------------------------------------------------------
INSERT INTO categoria_producto (id_categoria_producto, nombre, descripcion, activo) VALUES
  (1, 'Bebidas', 'Gaseosas y agua', 1),
  (2, 'Snacks', 'Pochoclo y golosinas', 1),
  (3, 'Combos', 'Combos cine', 1);

INSERT INTO producto (
  id_producto, id_categoria_producto, nombre, descripcion, precio_actual, imagen, stock, estado
) VALUES
  (1, 1, 'Gaseosa mediana', 'Vaso 500 ml', 12.00, NULL, 100, 'ACTIVO'),
  (2, 1, 'Agua 500 ml', 'Botella de agua', 8.00, NULL, 80, 'ACTIVO'),
  (3, 2, 'Pochoclo grande', 'Balde familiar', 25.00, NULL, 50, 'ACTIVO'),
  (4, 2, 'Nachos', 'Nachos con queso', 20.00, NULL, 40, 'ACTIVO'),
  (5, 3, 'Combo Duo', '2 gaseosas + pochoclo grande', 45.00, NULL, 30, 'ACTIVO');

-- ---------------------------------------------------------------------------
-- Compradores
-- ---------------------------------------------------------------------------
INSERT INTO comprador (id_comprador, nombre, correo, telefono, tipo_comprador) VALUES
  (1, 'Juan Perez', 'juan.perez@email.com', '70000001', 'REGISTRADO'),
  (2, 'Lucia Gomez', 'lucia.gomez@email.com', '70000002', 'INVITADO'),
  (3, 'Marco Diaz', 'marco.diaz@email.com', NULL, 'INVITADO');

-- ---------------------------------------------------------------------------
-- Escenario A: Compra CONFIRMADA con 2 entradas + productos + pago + ticket
-- Funcion 1, asientos A1 y A2 (funcion_asiento 1 y 2)
-- Total: 35+35 + 12+25 = 107.00
-- ---------------------------------------------------------------------------
INSERT INTO compra (
  id_compra, id_comprador, creada_en, vence_reserva, moneda, total, estado
) VALUES
  (1, 1, '2026-10-18 10:00:00', '2026-10-18 10:15:00', 'BOB', 107.00, 'CONFIRMADA');

INSERT INTO entrada (
  id_entrada, id_compra, id_funcion_asiento, precio_venta, estado,
  reservada_en, confirmada_en
) VALUES
  (1, 1, 1, 35.00, 'CONFIRMADA', '2026-10-18 10:00:00', '2026-10-18 10:05:00'),
  (2, 1, 2, 35.00, 'CONFIRMADA', '2026-10-18 10:00:00', '2026-10-18 10:05:00');

INSERT INTO detalle_compra (id_detalle_compra, id_compra, id_producto, cantidad, precio_unitario) VALUES
  (1, 1, 1, 1, 12.00),
  (2, 1, 3, 1, 25.00);

INSERT INTO pago (
  id_pago, id_compra, metodo, importe, estado, referencia_externa, creado_en, aprobado_en
) VALUES
  (1, 1, 'QR', 107.00, 'APROBADO', 'PAY-TEST-0001', '2026-10-18 10:04:00', '2026-10-18 10:05:00');

INSERT INTO ticket (
  id_ticket, id_compra, codigo_validacion, emitido_en, estado
) VALUES
  (1, 1, 'TKT-CINEPRIME-0001', '2026-10-18 10:05:10', 'EMITIDO');

-- ---------------------------------------------------------------------------
-- Escenario B: Compra EXPIRADA (historial) — asiento liberado para reventa
-- Funcion 1, asiento A3 (funcion_asiento 3)
-- Luego Compra CONFIRMADA sobre el mismo asiento (reventa)
-- ---------------------------------------------------------------------------
INSERT INTO compra (
  id_compra, id_comprador, creada_en, vence_reserva, moneda, total, estado
) VALUES
  (2, 2, '2026-10-18 11:00:00', '2026-10-18 11:10:00', 'BOB', 35.00, 'EXPIRADA');

INSERT INTO entrada (
  id_entrada, id_compra, id_funcion_asiento, precio_venta, estado,
  reservada_en, cerrada_en
) VALUES
  (3, 2, 3, 35.00, 'EXPIRADA', '2026-10-18 11:00:00', '2026-10-18 11:10:00');

-- Reventa del mismo funcion_asiento 3
INSERT INTO compra (
  id_compra, id_comprador, creada_en, vence_reserva, moneda, total, estado
) VALUES
  (3, 3, '2026-10-18 12:00:00', '2026-10-18 12:15:00', 'BOB', 35.00, 'CONFIRMADA');

INSERT INTO entrada (
  id_entrada, id_compra, id_funcion_asiento, precio_venta, estado,
  reservada_en, confirmada_en
) VALUES
  (4, 3, 3, 35.00, 'CONFIRMADA', '2026-10-18 12:00:00', '2026-10-18 12:03:00');

INSERT INTO pago (
  id_pago, id_compra, metodo, importe, estado, referencia_externa, creado_en, aprobado_en
) VALUES
  (2, 3, 'TARJETA', 35.00, 'RECHAZADO', 'PAY-TEST-0002-FAIL', '2026-10-18 12:01:00', NULL),
  (3, 3, 'TARJETA', 35.00, 'APROBADO', 'PAY-TEST-0002', '2026-10-18 12:02:30', '2026-10-18 12:03:00');

INSERT INTO ticket (
  id_ticket, id_compra, codigo_validacion, emitido_en, estado
) VALUES
  (2, 3, 'TKT-CINEPRIME-0002', '2026-10-18 12:03:10', 'EMITIDO');

-- ---------------------------------------------------------------------------
-- Escenario C: Compra CANCELADA + reembolso COMPLETADO
-- Funcion 2, asiento A1 (funcion_asiento 9)
-- ---------------------------------------------------------------------------
INSERT INTO compra (
  id_compra, id_comprador, creada_en, vence_reserva, moneda, total, estado,
  cancelada_en, motivo_cancelacion
) VALUES
  (4, 1, '2026-10-19 09:00:00', '2026-10-19 09:15:00', 'BOB', 45.00, 'CANCELADA',
   '2026-10-19 14:00:00', 'Cambio de planes del cliente');

INSERT INTO entrada (
  id_entrada, id_compra, id_funcion_asiento, precio_venta, estado,
  reservada_en, confirmada_en, cerrada_en
) VALUES
  (5, 4, 9, 45.00, 'CANCELADA', '2026-10-19 09:00:00', '2026-10-19 09:05:00', '2026-10-19 14:00:00');

INSERT INTO pago (
  id_pago, id_compra, metodo, importe, estado, referencia_externa, creado_en, aprobado_en
) VALUES
  (4, 4, 'QR', 45.00, 'APROBADO', 'PAY-TEST-0003', '2026-10-19 09:04:00', '2026-10-19 09:05:00');

INSERT INTO ticket (
  id_ticket, id_compra, codigo_validacion, emitido_en, estado
) VALUES
  (3, 4, 'TKT-CINEPRIME-0003', '2026-10-19 09:05:10', 'ANULADO');

INSERT INTO reembolso (
  id_reembolso, id_pago, importe, motivo, estado, solicitado_en, completado_en, referencia_externa
) VALUES
  (1, 4, 45.00, 'Cancelacion solicitada por el cliente', 'COMPLETADO',
   '2026-10-19 14:00:00', '2026-10-19 14:10:00', 'REF-TEST-0001');

-- ---------------------------------------------------------------------------
-- Escenario D: Compra PENDIENTE_PAGO (reserva vigente de ejemplo)
-- Funcion 3, asiento A1 sala2 (funcion_asiento 17)
-- ---------------------------------------------------------------------------
INSERT INTO compra (
  id_compra, id_comprador, creada_en, vence_reserva, moneda, total, estado
) VALUES
  (5, 2, '2026-10-19 16:00:00', '2026-12-31 23:59:59', 'BOB', 30.00, 'PENDIENTE_PAGO');

INSERT INTO entrada (
  id_entrada, id_compra, id_funcion_asiento, precio_venta, estado, reservada_en
) VALUES
  (6, 5, 17, 30.00, 'RESERVADA', '2026-10-19 16:00:00');

INSERT INTO pago (
  id_pago, id_compra, metodo, importe, estado, referencia_externa, creado_en
) VALUES
  (5, 5, 'QR', 30.00, 'PENDIENTE', 'PAY-TEST-0004-PEND', '2026-10-19 16:01:00');

-- ---------------------------------------------------------------------------
-- Ajustar AUTO_INCREMENT para siguientes inserciones manuales
-- ---------------------------------------------------------------------------
ALTER TABLE genero AUTO_INCREMENT = 6;
ALTER TABLE persona AUTO_INCREMENT = 9;
ALTER TABLE pelicula AUTO_INCREMENT = 5;
ALTER TABLE sala AUTO_INCREMENT = 3;
ALTER TABLE asiento AUTO_INCREMENT = 15;
ALTER TABLE funcion AUTO_INCREMENT = 5;
ALTER TABLE funcion_asiento AUTO_INCREMENT = 29;
ALTER TABLE categoria_producto AUTO_INCREMENT = 4;
ALTER TABLE producto AUTO_INCREMENT = 6;
ALTER TABLE comprador AUTO_INCREMENT = 4;
ALTER TABLE compra AUTO_INCREMENT = 6;
ALTER TABLE entrada AUTO_INCREMENT = 7;
ALTER TABLE pago AUTO_INCREMENT = 6;
ALTER TABLE reembolso AUTO_INCREMENT = 2;
ALTER TABLE ticket AUTO_INCREMENT = 4;
ALTER TABLE detalle_compra AUTO_INCREMENT = 3;

-- ---------------------------------------------------------------------------
-- Consultas rapidas de verificacion (opcionales)
-- ---------------------------------------------------------------------------
-- SELECT COUNT(*) AS peliculas FROM pelicula;
-- SELECT f.id_funcion, p.titulo, s.nombre AS sala, f.inicio, f.formato, f.estado
-- FROM funcion f
-- JOIN pelicula p ON p.id_pelicula = f.id_pelicula
-- JOIN sala s ON s.id_sala = f.id_sala
-- ORDER BY f.inicio;
-- SELECT c.id_compra, c.estado, c.total, COUNT(e.id_entrada) AS entradas
-- FROM compra c LEFT JOIN entrada e ON e.id_compra = c.id_compra
-- GROUP BY c.id_compra, c.estado, c.total;

-- Fin datos de prueba CinePrime v2.
