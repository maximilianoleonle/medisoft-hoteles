-- Reservaciones 1-B-PREP-B - Schema/backfill hotel_id para Reservaciones base
-- Alcance permitido:
-- - reservaciones
-- - reservacion_habitaciones
-- - reservacion_pagos
-- - reservacion_abonos
-- - reservacion_notas
-- - solicitudes_factura
--
-- IMPORTANTE:
-- MySQL ejecuta commits implicitos con ALTER TABLE. No depender de
-- START TRANSACTION para revertir esta migracion. Usar el rollback
-- documentado en docs/fase_reservaciones_1_B_PREP_B_hotel_id_base.md.
--
-- Esta migracion no modifica caja, cortes, movimientos_caja, huespedes,
-- movimientos_inventario, Sync/PWA, check-in/check-out, facturacion funcional
-- ni codigo funcional. No crea foreign keys estrictas y no convierte hotel_id
-- a NOT NULL.

DROP PROCEDURE IF EXISTS preparar_reservaciones_base_hotel_id;

DELIMITER $$

CREATE PROCEDURE preparar_reservaciones_base_hotel_id()
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
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: no existe hoteles.slug los-cedros';
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
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: no existe tabla migrations';
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
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: migrations.nombre no existe';
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
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: migrations.batch no existe';
    END IF;

    -- Precondicion 3: deben existir todas las tablas objetivo.
    SELECT COUNT(*)
    INTO v_tablas_objetivo
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN (
          'reservaciones',
          'reservacion_habitaciones',
          'reservacion_pagos',
          'reservacion_abonos',
          'reservacion_notas',
          'solicitudes_factura'
      );

    IF v_tablas_objetivo <> 6 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: faltan tablas objetivo';
    END IF;

    -- Precondicion 4: habitaciones.hotel_id debe existir para derivar hotel.
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'habitaciones'
          AND COLUMN_NAME = 'hotel_id'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: habitaciones.hotel_id no existe';
    END IF;

    -- Precondicion 5: ninguna tabla objetivo debe tener hotel_id todavia.
    IF EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN (
              'reservaciones',
              'reservacion_habitaciones',
              'reservacion_pagos',
              'reservacion_abonos',
              'reservacion_notas',
              'solicitudes_factura'
          )
          AND COLUMN_NAME = 'hotel_id'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: una tabla objetivo ya tiene hotel_id';
    END IF;

    -- Precondicion 6: no debe haber reservaciones con habitaciones de hoteles mixtos.
    IF EXISTS (
        SELECT 1
        FROM (
            SELECT rh.reservacion_id
            FROM reservacion_habitaciones rh
            INNER JOIN habitaciones h ON h.id = rh.habitacion_id
            WHERE h.hotel_id IS NOT NULL
            GROUP BY rh.reservacion_id
            HAVING COUNT(DISTINCT h.hotel_id) > 1
        ) hoteles_mixtos
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: hay reservaciones multi-hotel';
    END IF;

    ALTER TABLE reservaciones
        ADD COLUMN hotel_id INT NULL AFTER id;

    ALTER TABLE reservacion_habitaciones
        ADD COLUMN hotel_id INT NULL AFTER id;

    ALTER TABLE reservacion_pagos
        ADD COLUMN hotel_id INT NULL AFTER id;

    ALTER TABLE reservacion_abonos
        ADD COLUMN hotel_id INT NULL AFTER id;

    ALTER TABLE reservacion_notas
        ADD COLUMN hotel_id INT NULL AFTER id;

    ALTER TABLE solicitudes_factura
        ADD COLUMN hotel_id INT NULL AFTER id;

    -- Backfill principal: reservaciones desde sus habitaciones.
    UPDATE reservaciones r
    INNER JOIN (
        SELECT
            rh.reservacion_id,
            MIN(h.hotel_id) AS hotel_id
        FROM reservacion_habitaciones rh
        INNER JOIN habitaciones h ON h.id = rh.habitacion_id
        WHERE h.hotel_id IS NOT NULL
        GROUP BY rh.reservacion_id
    ) derivado ON derivado.reservacion_id = r.id
    SET r.hotel_id = derivado.hotel_id
    WHERE r.hotel_id IS NULL;

    -- Fallback aprobado para reservaciones historicas sin habitacion.
    UPDATE reservaciones
    SET hotel_id = v_los_cedros_id
    WHERE id IN (48, 49)
      AND hotel_id IS NULL;

    -- Backfill de habitaciones de reservacion, incluso si la reservacion historica ya no existe.
    UPDATE reservacion_habitaciones rh
    INNER JOIN habitaciones h ON h.id = rh.habitacion_id
    SET rh.hotel_id = h.hotel_id
    WHERE rh.hotel_id IS NULL
      AND h.hotel_id IS NOT NULL;

    -- Backfill de hijos desde reservaciones existentes.
    UPDATE reservacion_pagos rp
    INNER JOIN reservaciones r ON r.id = rp.reservacion_id
    SET rp.hotel_id = r.hotel_id
    WHERE rp.hotel_id IS NULL
      AND r.hotel_id IS NOT NULL;

    UPDATE reservacion_abonos ra
    INNER JOIN reservaciones r ON r.id = ra.reservacion_id
    SET ra.hotel_id = r.hotel_id
    WHERE ra.hotel_id IS NULL
      AND r.hotel_id IS NOT NULL;

    UPDATE reservacion_notas rn
    INNER JOIN reservaciones r ON r.id = rn.reservacion_id
    SET rn.hotel_id = r.hotel_id
    WHERE rn.hotel_id IS NULL
      AND r.hotel_id IS NOT NULL;

    UPDATE solicitudes_factura sf
    INNER JOIN reservaciones r ON r.id = sf.reservacion_id
    SET sf.hotel_id = r.hotel_id
    WHERE sf.hotel_id IS NULL
      AND r.hotel_id IS NOT NULL;

    -- Fallback aprobado para huerfanos historicos de tablas objetivo.
    UPDATE reservacion_pagos
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    UPDATE reservacion_abonos
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    UPDATE reservacion_notas
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    UPDATE solicitudes_factura
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    -- Validaciones post-backfill: no deben quedar NULL en tablas objetivo con datos.
    IF EXISTS (SELECT 1 FROM reservaciones WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: reservaciones tiene hotel_id NULL';
    END IF;

    IF EXISTS (SELECT 1 FROM reservacion_habitaciones WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: reservacion_habitaciones tiene hotel_id NULL';
    END IF;

    IF EXISTS (SELECT 1 FROM reservacion_pagos WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: reservacion_pagos tiene hotel_id NULL';
    END IF;

    IF EXISTS (SELECT 1 FROM reservacion_abonos WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: reservacion_abonos tiene hotel_id NULL';
    END IF;

    IF EXISTS (SELECT 1 FROM reservacion_notas WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: reservacion_notas tiene hotel_id NULL';
    END IF;

    IF EXISTS (SELECT 1 FROM solicitudes_factura WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservaciones 1-B detenida: solicitudes_factura tiene hotel_id NULL';
    END IF;

    ALTER TABLE reservaciones
        ADD INDEX idx_reservaciones_hotel_id (hotel_id);

    ALTER TABLE reservacion_habitaciones
        ADD INDEX idx_reservacion_habitaciones_hotel_id (hotel_id);

    ALTER TABLE reservacion_pagos
        ADD INDEX idx_reservacion_pagos_hotel_id (hotel_id);

    ALTER TABLE reservacion_abonos
        ADD INDEX idx_reservacion_abonos_hotel_id (hotel_id);

    ALTER TABLE reservacion_notas
        ADD INDEX idx_reservacion_notas_hotel_id (hotel_id);

    ALTER TABLE solicitudes_factura
        ADD INDEX idx_solicitudes_factura_hotel_id (hotel_id);

    SELECT COALESCE(MAX(batch), 0) + 1
    INTO v_siguiente_batch
    FROM migrations;

    INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
    VALUES (
        '20260526_008_add_hotel_id_reservaciones_base.sql',
        v_siguiente_batch,
        NULL,
        'ejecutada',
        CURRENT_TIMESTAMP
    )
    ON DUPLICATE KEY UPDATE
        estado = VALUES(estado),
        ejecutada_en = CURRENT_TIMESTAMP;
END$$

CALL preparar_reservaciones_base_hotel_id()$$

DROP PROCEDURE IF EXISTS preparar_reservaciones_base_hotel_id$$

DELIMITER ;
