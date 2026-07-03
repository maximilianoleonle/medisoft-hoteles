-- Bloque 3 (loop de mejora): indices compuestos por hotel para queries calientes.
--
-- Motivacion: las tablas operativas solo tenian indice simple en hotel_id.
-- Las consultas reales siempre combinan hotel_id con fecha/estado/nombre;
-- con historial amplio eso obliga a filtrar miles de filas del indice simple.
--
-- Los indices no cambian resultados, solo planes de ejecucion.
-- Rollback: DROP INDEX <nombre> ON <tabla>; (documentado abajo por indice).
--
-- IMPORTANTE: ALTER TABLE hace commit implicito; la migracion es idempotente
-- (verifica information_schema.STATISTICS antes de crear cada indice).

DROP PROCEDURE IF EXISTS crear_indices_compuestos_hotel;

DELIMITER $$

CREATE PROCEDURE crear_indices_compuestos_hotel()
BEGIN
    -- reservaciones: listados y dashboard filtran hotel + fecha / hotel + estado
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservaciones'
                     AND INDEX_NAME = 'idx_reservaciones_hotel_entrada') THEN
        ALTER TABLE reservaciones ADD INDEX idx_reservaciones_hotel_entrada (hotel_id, fecha_entrada);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservaciones'
                     AND INDEX_NAME = 'idx_reservaciones_hotel_salida') THEN
        ALTER TABLE reservaciones ADD INDEX idx_reservaciones_hotel_salida (hotel_id, fecha_salida);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservaciones'
                     AND INDEX_NAME = 'idx_reservaciones_hotel_estado') THEN
        ALTER TABLE reservaciones ADD INDEX idx_reservaciones_hotel_estado (hotel_id, estado);
    END IF;

    -- movimientos_caja: ingresos del dia/mes por hotel (hotel + tipo + fecha)
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimientos_caja'
                     AND INDEX_NAME = 'idx_movimientos_hotel_tipo_created') THEN
        ALTER TABLE movimientos_caja ADD INDEX idx_movimientos_hotel_tipo_created (hotel_id, tipo, created_at);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimientos_caja'
                     AND INDEX_NAME = 'idx_movimientos_hotel_created') THEN
        ALTER TABLE movimientos_caja ADD INDEX idx_movimientos_hotel_created (hotel_id, created_at);
    END IF;

    -- habitaciones: tablero por hotel + estado
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitaciones'
                     AND INDEX_NAME = 'idx_habitaciones_hotel_estado') THEN
        ALTER TABLE habitaciones ADD INDEX idx_habitaciones_hotel_estado (hotel_id, estado);
    END IF;

    -- huespedes: listado alfabetico por hotel
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'huespedes'
                     AND INDEX_NAME = 'idx_huespedes_hotel_nombre') THEN
        ALTER TABLE huespedes ADD INDEX idx_huespedes_hotel_nombre (hotel_id, nombre_completo);
    END IF;
END$$

DELIMITER ;

CALL crear_indices_compuestos_hotel();
DROP PROCEDURE IF EXISTS crear_indices_compuestos_hotel;

-- Rollback manual si hiciera falta:
--   DROP INDEX idx_reservaciones_hotel_entrada ON reservaciones;
--   DROP INDEX idx_reservaciones_hotel_salida ON reservaciones;
--   DROP INDEX idx_reservaciones_hotel_estado ON reservaciones;
--   DROP INDEX idx_movimientos_hotel_tipo_created ON movimientos_caja;
--   DROP INDEX idx_movimientos_hotel_created ON movimientos_caja;
--   DROP INDEX idx_habitaciones_hotel_estado ON habitaciones;
--   DROP INDEX idx_huespedes_hotel_nombre ON huespedes;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_007_indices_compuestos_hotel.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
