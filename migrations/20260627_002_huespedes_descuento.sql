-- Agregar descuento por huesped (porcentaje o monto fijo) a huespedes.
-- Alcance:
-- - huespedes (agrega 2 columnas nullable; sin descuento por defecto).
-- - Aditiva: huespedes existentes quedan con descuento_tipo/descuento_valor NULL (sin descuento).
-- - No toca reservaciones, caja ni el calculo de precios existente.

SET @migration_name := '20260627_002_huespedes_descuento.sql';

DROP PROCEDURE IF EXISTS migrar_huespedes_descuento;

DELIMITER $$

CREATE PROCEDURE migrar_huespedes_descuento()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_has_tipo INT DEFAULT 0;
    DECLARE v_has_valor INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huespedes';

    IF v_has_table = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'huespedes no existe; no se puede agregar descuento';
    END IF;

    SELECT COUNT(*)
    INTO v_has_tipo
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huespedes'
      AND COLUMN_NAME = 'descuento_tipo';

    IF v_has_tipo = 0 THEN
        ALTER TABLE huespedes
            ADD COLUMN descuento_tipo VARCHAR(12) NULL AFTER email;
    END IF;

    SELECT COUNT(*)
    INTO v_has_valor
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huespedes'
      AND COLUMN_NAME = 'descuento_valor';

    IF v_has_valor = 0 THEN
        ALTER TABLE huespedes
            ADD COLUMN descuento_valor DECIMAL(10,2) NULL AFTER descuento_tipo;
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

CALL migrar_huespedes_descuento()$$

DROP PROCEDURE IF EXISTS migrar_huespedes_descuento$$

DELIMITER ;

-- Rollback manual:
-- ALTER TABLE huespedes DROP COLUMN descuento_valor;
-- ALTER TABLE huespedes DROP COLUMN descuento_tipo;
-- DELETE FROM migrations WHERE nombre = '20260627_002_huespedes_descuento.sql';
