-- Fase 2A.2-FIX-B - Permitir estado programado en mantenimientos_habitaciones
-- Migracion minima de schema. No toca datos, hotel_id, indices unicos ni logica funcional.

DELIMITER $$

DROP PROCEDURE IF EXISTS fix_estado_programado_mantenimientos$$

CREATE PROCEDURE fix_estado_programado_mantenimientos()
BEGIN
    DECLARE v_table_count INT DEFAULT 0;
    DECLARE v_column_type TEXT DEFAULT NULL;

    SELECT COUNT(*)
    INTO v_table_count
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mantenimientos_habitaciones';

    IF v_table_count = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fase 2A.2-FIX-B detenida: no existe la tabla mantenimientos_habitaciones';
    END IF;

    SELECT COLUMN_TYPE
    INTO v_column_type
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mantenimientos_habitaciones'
      AND COLUMN_NAME = 'estado';

    IF v_column_type IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fase 2A.2-FIX-B detenida: no existe la columna mantenimientos_habitaciones.estado';
    END IF;

    IF v_column_type NOT LIKE '%''programado''%' THEN
        IF v_column_type <> 'enum(''en_proceso'',''completado'',''cancelado'')' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Fase 2A.2-FIX-B detenida: enum estado tiene valores inesperados';
        END IF;

        ALTER TABLE mantenimientos_habitaciones
            MODIFY COLUMN estado ENUM('en_proceso','completado','cancelado','programado')
            COLLATE utf8mb4_unicode_ci
            DEFAULT 'en_proceso';
    END IF;
END$$

CALL fix_estado_programado_mantenimientos()$$

DROP PROCEDURE IF EXISTS fix_estado_programado_mantenimientos$$

DELIMITER ;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260526_004_fix_estado_programado_mantenimientos.sql', 2, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
