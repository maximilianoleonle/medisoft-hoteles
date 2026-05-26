-- Fase Inventario 1-E-B-PREP - Schema/backfill hotel_id para movimientos_inventario
-- Alcance permitido:
-- - movimientos_inventario
--
-- Tablas usadas solo para lectura/validacion:
-- - inventario_productos
-- - habitaciones
-- - hoteles
-- - migrations
--
-- IMPORTANTE:
-- MySQL ejecuta commits implicitos con ALTER TABLE. No depender de
-- START TRANSACTION para revertir esta migracion. Usar el rollback
-- documentado en docs/fase_inventario_1_E_B_prep_hotel_id_movimientos.md.
--
-- Esta migracion no modifica reservaciones, inventario_productos,
-- inventario_categorias, inventario_config_habitacion, inventario_movimientos,
-- alertas_inventario, caja, PWA/offline ni codigo funcional.

DROP PROCEDURE IF EXISTS preparar_movimientos_inventario_hotel_id;

DELIMITER $$

CREATE PROCEDURE preparar_movimientos_inventario_hotel_id()
BEGIN
    DECLARE v_los_cedros_id INT DEFAULT NULL;

    -- Precondicion 1: debe existir el hotel base para validar el contexto local.
    SELECT id
    INTO v_los_cedros_id
    FROM hoteles
    WHERE slug = 'los-cedros'
    LIMIT 1;

    IF v_los_cedros_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-E-B detenida: no existe hoteles.slug = los-cedros';
    END IF;

    -- Precondicion 2: la tabla objetivo debe existir antes de cualquier ALTER TABLE.
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'movimientos_inventario'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-E-B detenida: no existe la tabla movimientos_inventario';
    END IF;

    -- Precondicion 3: las columnas fuente deben existir para derivar y validar hotel_id.
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_productos'
          AND COLUMN_NAME = 'hotel_id'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-E-B detenida: inventario_productos.hotel_id no existe';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'habitaciones'
          AND COLUMN_NAME = 'hotel_id'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-E-B detenida: habitaciones.hotel_id no existe';
    END IF;

    -- Precondicion 4: la migracion no debe correr si movimientos_inventario ya tiene hotel_id.
    IF EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'movimientos_inventario'
          AND COLUMN_NAME = 'hotel_id'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-E-B detenida: movimientos_inventario ya tiene hotel_id';
    END IF;

    -- Precondicion 5: todo movimiento debe tener producto con hotel_id derivable.
    IF EXISTS (
        SELECT 1
        FROM movimientos_inventario mi
        LEFT JOIN inventario_productos ip ON mi.producto_id = ip.id
        WHERE ip.id IS NULL
           OR ip.hotel_id IS NULL
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-E-B detenida: existen movimientos sin producto o con producto sin hotel_id';
    END IF;

    -- Precondicion 6: si hay habitacion_id, debe coincidir con el hotel del producto.
    IF EXISTS (
        SELECT 1
        FROM movimientos_inventario mi
        JOIN inventario_productos ip ON mi.producto_id = ip.id
        LEFT JOIN habitaciones h ON mi.habitacion_id = h.id
        WHERE mi.habitacion_id IS NOT NULL
          AND (
              h.id IS NULL
              OR h.hotel_id IS NULL
              OR h.hotel_id <> ip.hotel_id
          )
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-E-B detenida: existen movimientos con hotel distinto entre producto y habitacion';
    END IF;

    ALTER TABLE movimientos_inventario
        ADD COLUMN hotel_id INT NULL AFTER producto_id;

    UPDATE movimientos_inventario mi
    JOIN inventario_productos ip ON mi.producto_id = ip.id
    SET mi.hotel_id = ip.hotel_id
    WHERE mi.hotel_id IS NULL;

    IF EXISTS (
        SELECT 1
        FROM movimientos_inventario mi
        LEFT JOIN inventario_productos ip ON mi.producto_id = ip.id
        WHERE ip.id IS NULL
           OR ip.hotel_id IS NULL
           OR mi.hotel_id IS NULL
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-E-B detenida: quedaron movimientos sin hotel_id despues del backfill';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM movimientos_inventario mi
        JOIN habitaciones h ON mi.habitacion_id = h.id
        WHERE mi.habitacion_id IS NOT NULL
          AND (
              h.hotel_id IS NULL
              OR h.hotel_id <> mi.hotel_id
          )
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-E-B detenida: validacion post-backfill fallo por diferencia producto/habitacion';
    END IF;

    ALTER TABLE movimientos_inventario
        ADD INDEX idx_movimientos_inventario_hotel_id (hotel_id),
        ADD CONSTRAINT fk_movimientos_inventario_hotel
            FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT;

    INSERT INTO migrations (nombre, batch, checksum, estado)
    VALUES ('20260526_007_add_hotel_id_movimientos_inventario.sql', 4, NULL, 'ejecutada')
    ON DUPLICATE KEY UPDATE
        estado = VALUES(estado),
        ejecutada_en = CURRENT_TIMESTAMP;
END$$

CALL preparar_movimientos_inventario_hotel_id()$$

DROP PROCEDURE IF EXISTS preparar_movimientos_inventario_hotel_id$$

DELIMITER ;
