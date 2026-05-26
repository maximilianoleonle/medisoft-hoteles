-- Fase Inventario 1-C-PREP - Schema/backfill hotel_id para catalogos/configuracion de Inventario
-- Alcance permitido:
-- - inventario_categorias
-- - inventario_productos
-- - inventario_config_habitacion
--
-- IMPORTANTE:
-- MySQL ejecuta commits implicitos con ALTER TABLE. No depender de
-- START TRANSACTION para revertir esta migracion. Usar el rollback
-- documentado en docs/fase_inventario_1_C_prep_hotel_id_base.md.
--
-- Esta migracion no cambia indices unicos globales, no modifica codigo
-- funcional, no toca movimientos de inventario, reservaciones,
-- check-in/check-out, caja ni PWA/offline.

DROP PROCEDURE IF EXISTS preparar_inventario_base_hotel_id;

DELIMITER $$

CREATE PROCEDURE preparar_inventario_base_hotel_id()
BEGIN
    DECLARE v_los_cedros_id INT DEFAULT NULL;

    -- Precondicion 1: debe existir el hotel base para backfill.
    SELECT id
    INTO v_los_cedros_id
    FROM hoteles
    WHERE slug = 'los-cedros'
    LIMIT 1;

    IF v_los_cedros_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-C detenida: no existe hoteles.slug = los-cedros';
    END IF;

    -- Precondicion 2: las tres tablas objetivo deben existir antes de cualquier ALTER TABLE.
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_categorias'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-C detenida: no existe la tabla inventario_categorias';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_productos'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-C detenida: no existe la tabla inventario_productos';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_config_habitacion'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-C detenida: no existe la tabla inventario_config_habitacion';
    END IF;

    -- Precondicion 3: la migracion no debe correr si alguna tabla ya tiene hotel_id.
    IF EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN (
              'inventario_categorias',
              'inventario_productos',
              'inventario_config_habitacion'
          )
          AND COLUMN_NAME = 'hotel_id'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-C detenida: alguna tabla base de Inventario ya tiene hotel_id';
    END IF;

    ALTER TABLE inventario_categorias
        ADD COLUMN hotel_id INT NULL AFTER id;

    ALTER TABLE inventario_productos
        ADD COLUMN hotel_id INT NULL AFTER id;

    ALTER TABLE inventario_config_habitacion
        ADD COLUMN hotel_id INT NULL AFTER id;

    UPDATE inventario_categorias
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    UPDATE inventario_productos
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    UPDATE inventario_config_habitacion
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    IF EXISTS (SELECT 1 FROM inventario_categorias WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-C detenida: inventario_categorias tiene hotel_id NULL despues del backfill';
    END IF;

    IF EXISTS (SELECT 1 FROM inventario_productos WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-C detenida: inventario_productos tiene hotel_id NULL despues del backfill';
    END IF;

    IF EXISTS (SELECT 1 FROM inventario_config_habitacion WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventario 1-C detenida: inventario_config_habitacion tiene hotel_id NULL despues del backfill';
    END IF;

    ALTER TABLE inventario_categorias
        ADD INDEX idx_inventario_categorias_hotel_id (hotel_id),
        ADD CONSTRAINT fk_inventario_categorias_hotel
            FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT;

    ALTER TABLE inventario_productos
        ADD INDEX idx_inventario_productos_hotel_id (hotel_id),
        ADD CONSTRAINT fk_inventario_productos_hotel
            FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT;

    ALTER TABLE inventario_config_habitacion
        ADD INDEX idx_inventario_config_habitacion_hotel_id (hotel_id),
        ADD CONSTRAINT fk_inventario_config_habitacion_hotel
            FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT;

    INSERT INTO migrations (nombre, batch, checksum, estado)
    VALUES ('20260526_006_add_hotel_id_inventario_base.sql', 3, NULL, 'ejecutada')
    ON DUPLICATE KEY UPDATE
        estado = VALUES(estado),
        ejecutada_en = CURRENT_TIMESTAMP;
END$$

CALL preparar_inventario_base_hotel_id()$$

DROP PROCEDURE IF EXISTS preparar_inventario_base_hotel_id$$

DELIMITER ;
