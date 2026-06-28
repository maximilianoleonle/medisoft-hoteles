-- Agregar 'clase' a incrementos_tarifas para reutilizar el subsistema de tarifas
-- tambien como descuentos (incremento|descuento), sin crear tablas nuevas.
-- Alcance:
-- - incrementos_tarifas (solo agrega columna NOT NULL con DEFAULT 'incremento').
-- - Aditiva: todos los registros existentes quedan como 'incremento' (comportamiento previo).
-- - No toca reservaciones, caja, huespedes ni el calculo de precios existente.

SET @migration_name := '20260627_001_incrementos_tarifas_clase.sql';

DROP PROCEDURE IF EXISTS migrar_incrementos_tarifas_clase;

DELIMITER $$

CREATE PROCEDURE migrar_incrementos_tarifas_clase()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_has_clase INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'incrementos_tarifas';

    IF v_has_table = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'incrementos_tarifas no existe; no se puede agregar clase';
    END IF;

    SELECT COUNT(*)
    INTO v_has_clase
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'incrementos_tarifas'
      AND COLUMN_NAME = 'clase';

    IF v_has_clase = 0 THEN
        ALTER TABLE incrementos_tarifas
            ADD COLUMN clase VARCHAR(20) NOT NULL DEFAULT 'incremento' AFTER tipo_incremento;
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

CALL migrar_incrementos_tarifas_clase()$$

DROP PROCEDURE IF EXISTS migrar_incrementos_tarifas_clase$$

DELIMITER ;

-- Rollback manual:
-- ALTER TABLE incrementos_tarifas DROP COLUMN clase;
-- DELETE FROM migrations WHERE nombre = '20260627_001_incrementos_tarifas_clase.sql';
