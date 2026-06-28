-- Persistir el descuento aplicado en cada reservacion (monto total ya resuelto
-- + desglose JSON) para que la vista y el PDF sean fieles sin recalcular.
-- Alcance:
-- - reservaciones (descuento_total NOT NULL DEFAULT 0 + descuento_detalle_json TEXT NULL).
-- - precio_total sigue siendo el TOTAL FINAL (ya con descuento aplicado).
-- - Aditiva: reservaciones existentes quedan con descuento_total = 0 (sin descuento).
-- - No toca caja ni el flujo de check-in/out.

SET @migration_name := '20260627_003_reservaciones_descuento.sql';

DROP PROCEDURE IF EXISTS migrar_reservaciones_descuento;

DELIMITER $$

CREATE PROCEDURE migrar_reservaciones_descuento()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_has_total INT DEFAULT 0;
    DECLARE v_has_detalle INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'reservaciones';

    IF v_has_table = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'reservaciones no existe; no se puede agregar descuento';
    END IF;

    SELECT COUNT(*)
    INTO v_has_total
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'reservaciones'
      AND COLUMN_NAME = 'descuento_total';

    IF v_has_total = 0 THEN
        ALTER TABLE reservaciones
            ADD COLUMN descuento_total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER precio_total;
    END IF;

    SELECT COUNT(*)
    INTO v_has_detalle
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'reservaciones'
      AND COLUMN_NAME = 'descuento_detalle_json';

    IF v_has_detalle = 0 THEN
        ALTER TABLE reservaciones
            ADD COLUMN descuento_detalle_json TEXT NULL AFTER descuento_total;
    END IF;

    INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
    SELECT
        @migration_name,
        COALESCE(MAX(batch), 0) + 1,
        NULL,
        'ejecutada',
        NOW()
    FROM migrations
    ON DUPLICATE KEY UPDATE
        estado = VALUES(estado),
        ejecutada_en = CURRENT_TIMESTAMP;
END$$

CALL migrar_reservaciones_descuento()$$

DROP PROCEDURE IF EXISTS migrar_reservaciones_descuento$$

DELIMITER ;

-- Rollback manual:
-- ALTER TABLE reservaciones DROP COLUMN descuento_detalle_json;
-- ALTER TABLE reservaciones DROP COLUMN descuento_total;
-- DELETE FROM migrations WHERE nombre = '20260627_003_reservaciones_descuento.sql';
