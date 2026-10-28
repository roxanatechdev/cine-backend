-- ============================================================================
-- CinePrime v2 — Modelo físico de base de datos (completo)
-- Alineado con las migraciones Laravel del proyecto (octubre 2026)
-- Fuente de verdad: migraciones.txt
-- Motor objetivo: MySQL 8.0.16+ / MariaDB 10.5+ (InnoDB) — compatible con Laragon
-- Base: cineprime_v2
--
-- IMPORTANTE
-- 1. Crea una base NUEVA. No modifica ni elimina bases anteriores.
-- 2. No incluye datos de prueba de negocio (solo catálogos mínimos de idioma/país).
-- 3. Ejecutar una sola vez sobre base vacía (o tras DROP DATABASE cineprime_v2).
-- 4. Laravel gestiona el esquema vía migraciones. Este script sirve de referencia
--    documentada y de respaldo para despliegue manual o documentación de la feria.
-- 5. La disponibilidad de asientos se controla con el estado de ENTRADA (no hay
--    columnas estado/bloqueado_hasta en funcion_asiento). El bloqueo temporal
--    (15 minutos en el MVP) se expresa con compra.vence_reserva.
-- 6. Un ticket por compra (uq_ticket_compra). Una sola tarifa (precio en función /
--    funcion_asiento / entrada.precio_venta).
-- 7. Roles de autenticación: tabla users (Laravel) con columna role ('user'|'admin').
--    Comprador es independiente (permite compra como invitado).
-- 8. Tablas de dominio: 22 (incluye sala_pasillo para el mapa de sala).
-- 9. producto incluye atributos de variante y promoción añadidos en migraciones
--    posteriores a la creación inicial de la tabla.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS cineprime_v2
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cineprime_v2;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Catálogos
-- ---------------------------------------------------------------------------

CREATE TABLE idioma (
  codigo VARCHAR(15) NOT NULL COMMENT 'Etiqueta BCP 47 (es, en, pt-BR, ...)',
  nombre VARCHAR(100) NOT NULL,
  activo TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (codigo),
  UNIQUE KEY uq_idioma_nombre (nombre),
  CONSTRAINT chk_idioma_activo CHECK (activo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pais (
  codigo CHAR(2) NOT NULL COMMENT 'Codigo ISO 3166-1 alfa-2',
  nombre VARCHAR(100) NOT NULL,
  PRIMARY KEY (codigo),
  UNIQUE KEY uq_pais_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE genero (
  id_genero INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255) NULL,
  activo TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id_genero),
  UNIQUE KEY uq_genero_nombre (nombre),
  CONSTRAINT chk_genero_activo CHECK (activo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Películas, países de producción, multimedia y créditos
-- ---------------------------------------------------------------------------

CREATE TABLE pelicula (
  id_pelicula INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo VARCHAR(150) NOT NULL,
  sinopsis TEXT NOT NULL,
  id_genero_principal INT UNSIGNED NULL,
  clasificacion ENUM('G','PG','PG-13','R','NC-17') NULL,
  duracion_minutos SMALLINT UNSIGNED NULL,
  fecha_estreno DATE NULL,
  idioma_original_codigo VARCHAR(15) NULL,
  estado ENUM('BORRADOR','ACTIVA','INACTIVA') NOT NULL DEFAULT 'BORRADOR',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pelicula),
  KEY ix_pelicula_titulo (titulo),
  KEY ix_pelicula_estado_estreno (estado, fecha_estreno),
  KEY ix_pelicula_genero (id_genero_principal),
  KEY ix_pelicula_idioma (idioma_original_codigo),
  CONSTRAINT chk_pelicula_duracion CHECK (
    duracion_minutos IS NULL OR duracion_minutos > 0
  ),
  CONSTRAINT fk_pelicula_genero
    FOREIGN KEY (id_genero_principal) REFERENCES genero (id_genero)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_pelicula_idioma
    FOREIGN KEY (idioma_original_codigo) REFERENCES idioma (codigo)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pelicula_pais (
  id_pelicula INT UNSIGNED NOT NULL,
  codigo_pais CHAR(2) NOT NULL,
  PRIMARY KEY (id_pelicula, codigo_pais),
  KEY ix_pelicula_pais_pais (codigo_pais),
  CONSTRAINT fk_pelicula_pais_pelicula
    FOREIGN KEY (id_pelicula) REFERENCES pelicula (id_pelicula)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_pelicula_pais_pais
    FOREIGN KEY (codigo_pais) REFERENCES pais (codigo)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pelicula_multimedia (
  id_multimedia BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pelicula INT UNSIGNED NOT NULL,
  tipo ENUM('POSTER','TRAILER','IMAGEN','VIDEO') NOT NULL,
  url VARCHAR(500) NOT NULL,
  texto_alternativo VARCHAR(255) NULL,
  es_principal TINYINT NOT NULL DEFAULT 0,
  orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  activo TINYINT NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_multimedia),
  KEY ix_multimedia_pelicula_tipo (id_pelicula, tipo, activo, orden),
  KEY ix_multimedia_principal (id_pelicula, tipo, es_principal, activo),
  CONSTRAINT chk_multimedia_principal CHECK (es_principal IN (0, 1)),
  CONSTRAINT chk_multimedia_activo CHECK (activo IN (0, 1)),
  CONSTRAINT fk_multimedia_pelicula
    FOREIGN KEY (id_pelicula) REFERENCES pelicula (id_pelicula)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE persona (
  id_persona INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre_completo VARCHAR(180) NOT NULL,
  nombre_busqueda VARCHAR(180) NULL COMMENT 'Opcional para busquedas normalizadas',
  foto VARCHAR(255) NULL,
  PRIMARY KEY (id_persona),
  KEY ix_persona_nombre (nombre_completo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pelicula_credito (
  id_pelicula INT UNSIGNED NOT NULL,
  id_persona INT UNSIGNED NOT NULL,
  rol ENUM('DIRECTOR','ACTOR','GUIONISTA','PRODUCTOR','COMPOSITOR') NOT NULL,
  personaje VARCHAR(150) NULL COMMENT 'Principalmente para creditos de ACTOR',
  orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id_pelicula, id_persona, rol),
  KEY ix_credito_persona_rol (id_persona, rol),
  KEY ix_credito_pelicula_rol_orden (id_pelicula, rol, orden),
  CONSTRAINT fk_credito_pelicula
    FOREIGN KEY (id_pelicula) REFERENCES pelicula (id_pelicula)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_credito_persona
    FOREIGN KEY (id_persona) REFERENCES persona (id_persona)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Salas, asientos, pasillos y funciones
-- ---------------------------------------------------------------------------

CREATE TABLE sala (
  id_sala INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  capacidad SMALLINT UNSIGNED NULL COMMENT 'Informativo; la fuente de verdad son los asientos',
  margen_limpieza_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  estado ENUM('ACTIVA','INACTIVA','MANTENIMIENTO') NOT NULL DEFAULT 'ACTIVA',
  PRIMARY KEY (id_sala),
  UNIQUE KEY uq_sala_nombre (nombre),
  CONSTRAINT chk_sala_margen CHECK (margen_limpieza_minutos >= 0),
  CONSTRAINT chk_sala_capacidad CHECK (capacidad IS NULL OR capacidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE asiento (
  id_asiento INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_sala INT UNSIGNED NOT NULL,
  fila VARCHAR(5) NOT NULL,
  numero SMALLINT UNSIGNED NOT NULL,
  tipo ENUM('ESTANDAR','PREFERENCIAL','ACCESIBLE') NOT NULL DEFAULT 'ESTANDAR',
  activo TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id_asiento),
  UNIQUE KEY uq_asiento_sala_fila_numero (id_sala, fila, numero),
  KEY ix_asiento_sala_activo (id_sala, activo),
  CONSTRAINT chk_asiento_numero CHECK (numero > 0),
  CONSTRAINT chk_asiento_activo CHECK (activo IN (0, 1)),
  CONSTRAINT fk_asiento_sala
    FOREIGN KEY (id_sala) REFERENCES sala (id_sala)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pasillos del mapa de sala (longitudinales entre asientos de una fila
-- o transversales entre dos filas consecutivas).
CREATE TABLE sala_pasillo (
  id_pasillo INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_sala INT UNSIGNED NOT NULL,
  fila VARCHAR(5) NOT NULL,
  despues_de_numero SMALLINT UNSIGNED NULL COMMENT 'NULL en pasillos transversales',
  orientacion VARCHAR(20) NOT NULL DEFAULT 'LONGITUDINAL',
  fila_siguiente VARCHAR(5) NULL COMMENT 'Solo para pasillos transversales entre filas',
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id_pasillo),
  UNIQUE KEY uq_sala_pasillo_ubicacion (id_sala, fila, despues_de_numero),
  UNIQUE KEY uq_sala_pasillo_transversal (id_sala, orientacion, fila, fila_siguiente),
  CONSTRAINT fk_sala_pasillo_sala
    FOREIGN KEY (id_sala) REFERENCES sala (id_sala)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE funcion (
  id_funcion INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pelicula INT UNSIGNED NOT NULL,
  id_sala INT UNSIGNED NOT NULL,
  inicio DATETIME NOT NULL COMMENT 'Fecha y hora de inicio de la proyeccion',
  formato ENUM('2D','3D') NOT NULL DEFAULT '2D',
  precio_base DECIMAL(10,2) NOT NULL,
  estado ENUM('PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA') NOT NULL DEFAULT 'PROGRAMADA',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_funcion),
  KEY ix_funcion_sala_inicio_estado (id_sala, inicio, estado),
  KEY ix_funcion_pelicula_inicio (id_pelicula, inicio),
  CONSTRAINT chk_funcion_precio CHECK (precio_base >= 0),
  CONSTRAINT fk_funcion_pelicula
    FOREIGN KEY (id_pelicula) REFERENCES pelicula (id_pelicula)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_funcion_sala
    FOREIGN KEY (id_sala) REFERENCES sala (id_sala)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventario de asientos por función (precio aplicado). La disponibilidad
-- se deduce del estado de las filas en ENTRADA (RESERVADA / CONFIRMADA).
CREATE TABLE funcion_asiento (
  id_funcion_asiento INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_funcion INT UNSIGNED NOT NULL,
  id_asiento INT UNSIGNED NOT NULL,
  precio DECIMAL(10,2) NOT NULL COMMENT 'Precio aplicado a este asiento en esta funcion',
  PRIMARY KEY (id_funcion_asiento),
  UNIQUE KEY uq_funcion_asiento (id_funcion, id_asiento),
  KEY ix_funcion_asiento_asiento (id_asiento),
  CONSTRAINT chk_funcion_asiento_precio CHECK (precio >= 0),
  CONSTRAINT fk_funcion_asiento_funcion
    FOREIGN KEY (id_funcion) REFERENCES funcion (id_funcion)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_funcion_asiento_asiento
    FOREIGN KEY (id_asiento) REFERENCES asiento (id_asiento)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Compradores, compra, entradas, pagos, reembolsos y tickets
-- ---------------------------------------------------------------------------

CREATE TABLE comprador (
  id_comprador INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NULL,
  tipo_comprador ENUM('INVITADO','REGISTRADO') NOT NULL DEFAULT 'INVITADO',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_comprador),
  KEY ix_comprador_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE compra (
  id_compra INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_comprador INT UNSIGNED NOT NULL,
  creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  vence_reserva DATETIME NULL COMMENT 'Vencimiento del bloqueo temporal (MVP: 15 min)',
  moneda CHAR(3) NOT NULL DEFAULT 'BOB',
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  estado ENUM('INICIADA','PENDIENTE_PAGO','CONFIRMADA','CANCELADA','EXPIRADA')
    NOT NULL DEFAULT 'INICIADA',
  cancelada_en DATETIME NULL,
  motivo_cancelacion VARCHAR(255) NULL,
  PRIMARY KEY (id_compra),
  KEY ix_compra_comprador_fecha (id_comprador, creada_en),
  KEY ix_compra_estado_vence (estado, vence_reserva),
  CONSTRAINT chk_compra_total CHECK (total >= 0),
  CONSTRAINT fk_compra_comprador
    FOREIGN KEY (id_comprador) REFERENCES comprador (id_comprador)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de entradas. CANCELADA/EXPIRADA liberan el asiento (lógica de aplicación).
-- No hay UNIQUE sobre id_funcion_asiento: se permite historial de reintentos.
CREATE TABLE entrada (
  id_entrada INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_compra INT UNSIGNED NOT NULL,
  id_funcion_asiento INT UNSIGNED NOT NULL,
  precio_venta DECIMAL(10,2) NOT NULL,
  estado ENUM('RESERVADA','CONFIRMADA','UTILIZADA','CANCELADA','EXPIRADA')
    NOT NULL DEFAULT 'RESERVADA',
  reservada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmada_en DATETIME NULL,
  utilizada_en DATETIME NULL,
  cerrada_en DATETIME NULL COMMENT 'Fecha de cancelacion o expiracion',
  PRIMARY KEY (id_entrada),
  KEY ix_entrada_compra_estado (id_compra, estado),
  KEY ix_entrada_funcion_asiento_estado (id_funcion_asiento, estado),
  CONSTRAINT chk_entrada_precio CHECK (precio_venta >= 0),
  CONSTRAINT fk_entrada_compra
    FOREIGN KEY (id_compra) REFERENCES compra (id_compra)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_entrada_funcion_asiento
    FOREIGN KEY (id_funcion_asiento) REFERENCES funcion_asiento (id_funcion_asiento)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pago (
  id_pago INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_compra INT UNSIGNED NOT NULL,
  metodo ENUM('QR','TARJETA') NOT NULL,
  importe DECIMAL(12,2) NOT NULL,
  estado ENUM('PENDIENTE','APROBADO','RECHAZADO') NOT NULL DEFAULT 'PENDIENTE',
  referencia_externa VARCHAR(150) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aprobado_en DATETIME NULL,
  PRIMARY KEY (id_pago),
  KEY ix_pago_compra_estado (id_compra, estado),
  UNIQUE KEY uq_pago_referencia_externa (referencia_externa),
  CONSTRAINT chk_pago_importe CHECK (importe > 0),
  CONSTRAINT fk_pago_compra
    FOREIGN KEY (id_compra) REFERENCES compra (id_compra)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla preparada para ampliaciones; en el MVP el reembolso no forma parte del flujo principal.
CREATE TABLE reembolso (
  id_reembolso INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pago INT UNSIGNED NOT NULL,
  importe DECIMAL(12,2) NOT NULL,
  motivo VARCHAR(255) NULL,
  estado ENUM('SOLICITADO','PROCESANDO','COMPLETADO','RECHAZADO') NOT NULL DEFAULT 'SOLICITADO',
  solicitado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completado_en DATETIME NULL,
  referencia_externa VARCHAR(150) NULL,
  PRIMARY KEY (id_reembolso),
  KEY ix_reembolso_pago_estado (id_pago, estado),
  UNIQUE KEY uq_reembolso_referencia_externa (referencia_externa),
  CONSTRAINT chk_reembolso_importe CHECK (importe > 0),
  CONSTRAINT fk_reembolso_pago
    FOREIGN KEY (id_pago) REFERENCES pago (id_pago)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket (
  id_ticket INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_compra INT UNSIGNED NOT NULL,
  codigo_validacion VARCHAR(100) NOT NULL,
  emitido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('EMITIDO','UTILIZADO','ANULADO') NOT NULL DEFAULT 'EMITIDO',
  utilizado_en DATETIME NULL,
  PRIMARY KEY (id_ticket),
  UNIQUE KEY uq_ticket_compra (id_compra),
  UNIQUE KEY uq_ticket_codigo (codigo_validacion),
  CONSTRAINT fk_ticket_compra
    FOREIGN KEY (id_compra) REFERENCES compra (id_compra)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Productos y detalle de compra (concesiones)
-- ---------------------------------------------------------------------------

CREATE TABLE categoria_producto (
  id_categoria_producto INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255) NULL,
  activo TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id_categoria_producto),
  UNIQUE KEY uq_categoria_producto_nombre (nombre),
  CONSTRAINT chk_categoria_producto_activo CHECK (activo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE producto (
  id_producto INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_categoria_producto INT UNSIGNED NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  precio_actual DECIMAL(10,2) NOT NULL,
  imagen VARCHAR(255) NULL,
  stock INT UNSIGNED NULL COMMENT 'NULL = inventario no controlado',
  estado ENUM('ACTIVO','INACTIVO','AGOTADO') NOT NULL DEFAULT 'ACTIVO',
  -- Atributos opcionales de variante / promoción (migraciones posteriores)
  tamano VARCHAR(50) NULL,
  tipo_variante VARCHAR(50) NULL,
  contenido_ml SMALLINT UNSIGNED NULL,
  peso_gramos INT UNSIGNED NULL,
  es_promocion TINYINT(1) NOT NULL DEFAULT 0,
  precio_promocional DECIMAL(10,2) NULL,
  promocion_inicio DATETIME NULL,
  promocion_fin DATETIME NULL,
  PRIMARY KEY (id_producto),
  KEY ix_producto_categoria_estado (id_categoria_producto, estado),
  CONSTRAINT chk_producto_precio CHECK (precio_actual >= 0),
  CONSTRAINT fk_producto_categoria
    FOREIGN KEY (id_categoria_producto) REFERENCES categoria_producto (id_categoria_producto)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE detalle_compra (
  id_detalle_compra INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_compra INT UNSIGNED NOT NULL,
  id_producto INT UNSIGNED NOT NULL,
  cantidad SMALLINT UNSIGNED NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL COMMENT 'Precio congelado al momento de comprar',
  PRIMARY KEY (id_detalle_compra),
  UNIQUE KEY uq_detalle_compra_producto (id_compra, id_producto),
  KEY ix_detalle_compra_producto (id_producto),
  CONSTRAINT chk_detalle_cantidad CHECK (cantidad > 0),
  CONSTRAINT chk_detalle_precio CHECK (precio_unitario >= 0),
  CONSTRAINT fk_detalle_compra_compra
    FOREIGN KEY (id_compra) REFERENCES compra (id_compra)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_detalle_compra_producto
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Catálogos mínimos
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO idioma (codigo, nombre) VALUES
  ('es', 'Español'),
  ('en', 'Inglés'),
  ('pt', 'Portugués'),
  ('pt-BR', 'Portugués (Brasil)')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO pais (codigo, nombre) VALUES
  ('BO', 'Bolivia'),
  ('US', 'Estados Unidos'),
  ('GB', 'Reino Unido'),
  ('FR', 'Francia'),
  ('JP', 'Japón'),
  ('KR', 'Corea del Sur'),
  ('MX', 'México'),
  ('AR', 'Argentina'),
  ('BR', 'Brasil'),
  ('ES', 'España')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Fin del esquema CinePrime v2 (completo, alineado a migraciones octubre 2026).
-- Tablas de dominio: 22 (incluye sala_pasillo).
