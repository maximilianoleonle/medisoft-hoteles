-- Reservaciones 1-F-B-PREP - Schema/backfill hotel_id para Caja base
-- Alcance permitido:
-- - cajas
-- - cortes_caja
-- - movimientos_caja
--
-- IMPORTANTE:
-- MySQL ejecuta commits implicitos con ALTER TABLE. No depender de
-- START TRANSACTION para revertir esta migracion. Usar el rollback
-- documentado en docs/fase_reservaciones_1_F_B_PREP_hotel_id_caja.md.
--
-- Esta migracion no modifica codigo funcional, reservaciones,
-- reservacion_pagos, reservacion_habitaciones, habitaciones,
-- movimientos_inventario, huespedes, PWA/offline, Sync, APIs,
-- Dashboard/reportes ni flujos funcionales de Caja. No crea foreign keys
-- estrictas y no convierte hotel_id a NOT NULL.

DROP PROCEDURE IF EXISTS preparar_caja_base_hotel_id;

DELIMITER $$

CREATE PROCEDURE preparar_caja_base_hotel_id()
BEGIN
    DECLARE v_los_cedros_id INT DEFAULT NULL;
    DECLARE v_tablas_objetivo INT DEFAULT 0;
    DECLARE v_siguiente_batch INT DEFAULT 1;

    -- Precondicion 1: debe existir el hotel base para backfill/fallback.
    SELECT id
    INTO v_los_cedros_id
    FROM hoteles
    WHERE slug = 'los-cedros'
      AND activo = 1
    LIMIT 1;

    IF v_los_cedros_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: no existe hoteles.slug los-cedros';
    END IF;

    -- Precondicion 2: la tabla migrations debe existir con columnas actuales.
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'migrations'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: no existe tabla migrations';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'migrations'
          AND COLUMN_NAME = 'nombre'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: migrations.nombre no existe';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'migrations'
          AND COLUMN_NAME = 'batch'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: migrations.batch no existe';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'migrations'
          AND COLUMN_NAME = 'checksum'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: migrations.checksum no existe';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'migrations'
          AND COLUMN_NAME = 'estado'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: migrations.estado no existe';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'migrations'
          AND COLUMN_NAME = 'ejecutada_en'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: migrations.ejecutada_en no existe';
    END IF;

    -- Precondicion 3: deben existir todas las tablas objetivo.
    SELECT COUNT(*)
    INTO v_tablas_objetivo
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN (
          'cajas',
          'cortes_caja',
          'movimientos_caja'
      );

    IF v_tablas_objetivo <> 3 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: faltan tablas objetivo';
    END IF;

    -- Precondicion 4: reservaciones.hotel_id debe existir para derivar movimientos con reservacion valida.
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'reservaciones'
          AND COLUMN_NAME = 'hotel_id'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: reservaciones.hotel_id no existe';
    END IF;

    -- Precondicion 5: ninguna tabla objetivo debe tener hotel_id todavia.
    IF EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN (
              'cajas',
              'cortes_caja',
              'movimientos_caja'
          )
          AND COLUMN_NAME = 'hotel_id'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: una tabla objetivo ya tiene hotel_id';
    END IF;

    ALTER TABLE cajas
        ADD COLUMN hotel_id INT NULL AFTER id;

    ALTER TABLE cortes_caja
        ADD COLUMN hotel_id INT NULL AFTER id;

    ALTER TABLE movimientos_caja
        ADD COLUMN hotel_id INT NULL AFTER id;

    -- Backfill de caja global actual.
    UPDATE cajas
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    -- Backfill de cortes desde su caja.
    UPDATE cortes_caja cc
    INNER JOIN cajas c ON c.id = cc.caja_id
    SET cc.hotel_id = c.hotel_id
    WHERE cc.hotel_id IS NULL
      AND c.hotel_id IS NOT NULL;

    -- Fallback mono-hotel para cortes historicos sin caja derivable.
    UPDATE cortes_caja
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    -- Backfill de movimientos con reservacion valida.
    UPDATE movimientos_caja mc
    INNER JOIN reservaciones r ON r.id = mc.reservacion_id
    SET mc.hotel_id = r.hotel_id
    WHERE mc.hotel_id IS NULL
      AND r.hotel_id IS NOT NULL;

    -- Backfill de movimientos sin reservacion valida o huerfanos desde el corte.
    UPDATE movimientos_caja mc
    INNER JOIN cortes_caja cc ON cc.id = mc.corte_id
    SET mc.hotel_id = cc.hotel_id
    WHERE mc.hotel_id IS NULL
      AND cc.hotel_id IS NOT NULL;

    -- Fallback mono-hotel historico para cualquier movimiento restante.
    UPDATE movimientos_caja
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    -- Validaciones post-backfill: no deben quedar NULL.
    IF EXISTS (SELECT 1 FROM cajas WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: cajas tiene hotel_id NULL';
    END IF;

    IF EXISTS (SELECT 1 FROM cortes_caja WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: cortes_caja tiene hotel_id NULL';
    END IF;

    IF EXISTS (SELECT 1 FROM movimientos_caja WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: movimientos_caja tiene hotel_id NULL';
    END IF;

    -- Validacion: cortes con caja derivable deben coincidir con la caja.
    IF EXISTS (
        SELECT 1
        FROM cortes_caja cc
        INNER JOIN cajas c ON c.id = cc.caja_id
        WHERE cc.hotel_id <> c.hotel_id
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: corte y caja tienen hotel distinto';
    END IF;

    -- Validacion: movimientos con reservacion valida deben coincidir con la reservacion.
    IF EXISTS (
        SELECT 1
        FROM movimientos_caja mc
        INNER JOIN reservaciones r ON r.id = mc.reservacion_id
        WHERE mc.hotel_id <> r.hotel_id
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: movimiento y reservacion tienen hotel distinto';
    END IF;

    -- Validacion: movimientos con corte derivable deben coincidir con el corte.
    IF EXISTS (
        SELECT 1
        FROM movimientos_caja mc
        INNER JOIN cortes_caja cc ON cc.id = mc.corte_id
        WHERE mc.hotel_id <> cc.hotel_id
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Caja 1-F-B detenida: movimiento y corte tienen hotel distinto';
    END IF;

    ALTER TABLE cajas
        ADD INDEX idx_cajas_hotel_id (hotel_id);

    ALTER TABLE cortes_caja
        ADD INDEX idx_cortes_caja_hotel_id (hotel_id);

    ALTER TABLE movimientos_caja
        ADD INDEX idx_movimientos_caja_hotel_id (hotel_id);

    SELECT COALESCE(MAX(batch), 0) + 1
    INTO v_siguiente_batch
    FROM migrations;

    INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
    VALUES (
        '20260526_009_add_hotel_id_caja_base.sql',
        v_siguiente_batch,
        NULL,
        'ejecutada',
        CURRENT_TIMESTAMP
    )
    ON DUPLICATE KEY UPDATE
        estado = VALUES(estado),
        ejecutada_en = CURRENT_TIMESTAMP;
END$$

CALL preparar_caja_base_hotel_id()$$

DROP PROCEDURE IF EXISTS preparar_caja_base_hotel_id$$

DELIMITER ;
