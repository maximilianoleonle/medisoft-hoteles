-- =====================================================================
-- Modulo Lavanderia (clave 'lavanderia', ya sembrada en `modulos`):
-- control de blancos (stock limpio/sucio/en lavado con ledger), ciclos
-- de lavado por lote (interno o proveedor externo con gasto en Caja) y
-- pedidos de ropa de huesped (partidas con precio, cobro por Caja).
--
-- Migracion aditiva, idempotente y multi-hotel:
--   - 6 tablas nuevas lavanderia_* (hotel_id NOT NULL + FK a hoteles).
--   - ruta_base del modulo 'lavanderia' -> /lavanderia.
--   - Backfill de permisos lavanderia.* a roles existentes (paridad con
--     los presets de src/config/permisos.php; se salta roles con '*').
--
-- El dinero JAMAS se toca aqui: cobros/gastos entran en runtime por
-- MovimientoCaja::registrarMovimiento (candados de corte incluidos).
--
-- Rollback manual seguro:
--   DROP TABLE IF EXISTS lavanderia_pedido_items;
--   DROP TABLE IF EXISTS lavanderia_pedidos;
--   DROP TABLE IF EXISTS lavanderia_servicios;
--   DROP TABLE IF EXISTS lavanderia_lote_items;
--   DROP TABLE IF EXISTS lavanderia_movimientos;
--   DROP TABLE IF EXISTS lavanderia_lotes;
--   DROP TABLE IF EXISTS lavanderia_blancos;
--   UPDATE modulos SET ruta_base = NULL WHERE clave = 'lavanderia';
--   -- (los permisos agregados a roles se retiran editando el rol en la UI)
--   DELETE FROM migrations WHERE nombre = '20260718_001_lavanderia.sql';
-- =====================================================================

SET @migration_name := '20260718_001_lavanderia.sql';

-- ── Catalogo de blancos con stock por estado ─────────────────────────
-- categoria es VARCHAR validado por catalogo PHP (LavanderiaBlanco);
-- el stock por estado se muta SOLO bajo FOR UPDATE en el modelo.
CREATE TABLE IF NOT EXISTS lavanderia_blancos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    categoria VARCHAR(40) NOT NULL DEFAULT 'otro',
    stock_limpio INT NOT NULL DEFAULT 0,
    stock_sucio INT NOT NULL DEFAULT 0,
    stock_proceso INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    notas VARCHAR(300) NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lav_blancos_nombre (hotel_id, nombre),
    KEY idx_lav_blancos_activo (hotel_id, activo),
    CONSTRAINT fk_lav_blancos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Blancos del hotel (sabanas, toallas...) con stock limpio/sucio/en lavado';

-- ── Ciclos de lavado por lote ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS lavanderia_lotes (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    tipo VARCHAR(20) NOT NULL DEFAULT 'interno',
    estado ENUM('en_proceso','recibido','cancelado') NOT NULL DEFAULT 'en_proceso',
    proveedor VARCHAR(160) NULL DEFAULT NULL,
    piezas_enviadas INT NOT NULL DEFAULT 0,
    piezas_recibidas INT NULL DEFAULT NULL,
    merma_total INT NOT NULL DEFAULT 0,
    costo DECIMAL(10,2) NULL DEFAULT NULL,
    gasto_movimiento_id INT NULL DEFAULT NULL,
    gasto_registrado_en DATETIME NULL DEFAULT NULL,
    notas VARCHAR(500) NULL DEFAULT NULL,
    enviado_por_usuario_id INT NULL DEFAULT NULL,
    recibido_por_usuario_id INT NULL DEFAULT NULL,
    recibido_en DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lav_lotes_estado (hotel_id, estado),
    KEY idx_lav_lotes_gasto (hotel_id, gasto_movimiento_id),
    CONSTRAINT fk_lav_lotes_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ciclos de lavado por lote (interno o proveedor externo)';

CREATE TABLE IF NOT EXISTS lavanderia_lote_items (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    lote_id INT NOT NULL,
    blanco_id INT NOT NULL,
    cantidad_enviada INT NOT NULL,
    cantidad_recibida INT NULL DEFAULT NULL,
    merma INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lav_lote_items (lote_id, blanco_id),
    KEY idx_lav_lote_items_hotel (hotel_id, lote_id),
    CONSTRAINT fk_lav_lote_items_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_lav_lote_items_lote
        FOREIGN KEY (lote_id) REFERENCES lavanderia_lotes (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_lav_lote_items_blanco
        FOREIGN KEY (blanco_id) REFERENCES lavanderia_blancos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Partidas de un lote de lavado (piezas enviadas/recibidas/merma)';

-- ── Ledger de movimientos de blancos (bitacora inmutable) ────────────
-- tipo es VARCHAR validado en PHP: compra|uso|baja|envio|retorno|merma|ajuste.
CREATE TABLE IF NOT EXISTS lavanderia_movimientos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    blanco_id INT NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    cantidad INT NOT NULL,
    lote_id INT NULL DEFAULT NULL,
    usuario_id INT NULL DEFAULT NULL,
    notas VARCHAR(300) NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lav_movs_blanco (hotel_id, blanco_id),
    KEY idx_lav_movs_lote (hotel_id, lote_id),
    CONSTRAINT fk_lav_movs_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_lav_movs_blanco
        FOREIGN KEY (blanco_id) REFERENCES lavanderia_blancos (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_lav_movs_lote
        FOREIGN KEY (lote_id) REFERENCES lavanderia_lotes (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Bitacora de movimientos de blancos (compra, uso, envio, retorno, merma...)';

-- ── Catalogo de servicios/precios de lavanderia de huesped ───────────
CREATE TABLE IF NOT EXISTS lavanderia_servicios (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lav_servicios_nombre (hotel_id, nombre),
    KEY idx_lav_servicios_activo (hotel_id, activo),
    CONSTRAINT fk_lav_servicios_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Precios de lavanderia de huesped por hotel (camisa, planchado...)';

-- ── Pedidos de ropa de huesped ───────────────────────────────────────
-- reservacion_id es SOLO informativo (trazabilidad): el cobro entra por
-- movimientos_caja directo y JAMAS por reservacion_abonos ni CxC, para
-- no contaminar el saldo de hospedaje (resumenPagos).
CREATE TABLE IF NOT EXISTS lavanderia_pedidos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    cliente_nombre VARCHAR(160) NOT NULL,
    reservacion_id INT NULL DEFAULT NULL,
    habitacion_etiqueta VARCHAR(40) NULL DEFAULT NULL,
    estado ENUM('recibido','en_proceso','listo','entregado','cancelado') NOT NULL DEFAULT 'recibido',
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    cobro_movimiento_id INT NULL DEFAULT NULL,
    cobrado_en DATETIME NULL DEFAULT NULL,
    metodo_pago VARCHAR(20) NULL DEFAULT NULL,
    notas VARCHAR(500) NULL DEFAULT NULL,
    recibido_por_usuario_id INT NULL DEFAULT NULL,
    entregado_en DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lav_pedidos_estado (hotel_id, estado),
    KEY idx_lav_pedidos_reservacion (hotel_id, reservacion_id),
    KEY idx_lav_pedidos_cobro (hotel_id, cobro_movimiento_id),
    CONSTRAINT fk_lav_pedidos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_lav_pedidos_reservacion
        FOREIGN KEY (reservacion_id) REFERENCES reservaciones (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pedidos de lavanderia de huesped (cobro por Caja, vinculo a reserva solo informativo)';

CREATE TABLE IF NOT EXISTS lavanderia_pedido_items (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    pedido_id INT NOT NULL,
    descripcion VARCHAR(160) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    importe DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lav_pedido_items (hotel_id, pedido_id),
    CONSTRAINT fk_lav_pedido_items_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_lav_pedido_items_pedido
        FOREIGN KEY (pedido_id) REFERENCES lavanderia_pedidos (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Partidas de un pedido de lavanderia de huesped';

START TRANSACTION;

-- ── El modulo ya existe en el catalogo; solo gana su ruta base ───────
UPDATE modulos
SET ruta_base = '/lavanderia'
WHERE clave = 'lavanderia'
  AND (ruta_base IS NULL OR ruta_base = '');

-- ── Backfill de permisos a roles EXISTENTES (paridad con presets) ────
-- Patron canonico 20260715_001: JSON_ARRAY_APPEND idempotente, saltando
-- roles con comodin '*'. Hoteles nuevos los reciben via presets.

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'lavanderia.all'),
    updated_at = NOW()
WHERE clave = 'gerente'
  AND permisos_json IS NOT NULL
  AND JSON_VALID(permisos_json)
  AND JSON_SEARCH(permisos_json, 'one', 'lavanderia.all') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', '*') IS NULL;

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'lavanderia.all'),
    updated_at = NOW()
WHERE clave = 'administrador'
  AND permisos_json IS NOT NULL
  AND JSON_VALID(permisos_json)
  AND JSON_SEARCH(permisos_json, 'one', 'lavanderia.all') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', '*') IS NULL;

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'lavanderia.view'),
    updated_at = NOW()
WHERE clave = 'recepcionista'
  AND permisos_json IS NOT NULL
  AND JSON_VALID(permisos_json)
  AND JSON_SEARCH(permisos_json, 'one', 'lavanderia.view') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', '*') IS NULL;

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'lavanderia.operar'),
    updated_at = NOW()
WHERE clave = 'recepcionista'
  AND permisos_json IS NOT NULL
  AND JSON_VALID(permisos_json)
  AND JSON_SEARCH(permisos_json, 'one', 'lavanderia.operar') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', '*') IS NULL;

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'lavanderia.cobrar'),
    updated_at = NOW()
WHERE clave = 'recepcionista'
  AND permisos_json IS NOT NULL
  AND JSON_VALID(permisos_json)
  AND JSON_SEARCH(permisos_json, 'one', 'lavanderia.cobrar') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', '*') IS NULL;

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'lavanderia.view'),
    updated_at = NOW()
WHERE clave = 'dueno_remoto'
  AND permisos_json IS NOT NULL
  AND JSON_VALID(permisos_json)
  AND JSON_SEARCH(permisos_json, 'one', 'lavanderia.view') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', '*') IS NULL;

-- ── Auto-registro legacy ─────────────────────────────────────────────
INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
