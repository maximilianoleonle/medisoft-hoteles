-- Agregar hotel_id a incrementos_tarifas para aislar tarifas dinamicas por hotel.
-- Alcance:
-- - incrementos_tarifas
-- - No cambia formulas, montos ni fechas de tarifas.
-- - No toca caja, PWA/offline, /api/sync ni datos de reservaciones.

SET @migration_name := '20260624_005_incrementos_tarifas_hotel_id.sql';

DROP PROCEDURE IF EXISTS migrar_incrementos_tarifas_hotel_id;

DELIMITER $$

CREATE PROCEDURE migrar_incrementos_tarifas_hotel_id()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_has_hotel_id INT DEFAULT 0;
    DECLARE v_los_cedros_id INT DEFAULT NULL;
    DECLARE v_nulls INT DEFAULT 0;
    DECLARE v_invalid_hotel_ids INT DEFAULT 0;
    DECLARE v_has_index INT DEFAULT 0;
    DECLARE v_has_fk INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'incrementos_tarifas';

    IF v_has_table = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'incrementos_tarifas no existe; no se puede agregar hotel_id';
    END IF;

    SELECT id
    INTO v_los_cedros_id
    FROM hoteles
    WHERE slug = 'los-cedros'
    LIMIT 1;

    IF v_los_cedros_id IS NULL THEN
        SELECT id
        INTO v_los_cedros_id
        FROM hoteles
        ORDER BY id
        LIMIT 1;
    END IF;

    IF v_los_cedros_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No existe hotel base para backfill de incrementos_tarifas';
    END IF;

    SELECT COUNT(*)
    INTO v_has_hotel_id
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'incrementos_tarifas'
      AND COLUMN_NAME = 'hotel_id';

    IF v_has_hotel_id = 0 THEN
        ALTER TABLE incrementos_tarifas
            ADD COLUMN hotel_id INT NULL AFTER id;
    END IF;

    UPDATE incrementos_tarifas it
    INNER JOIN (
        SELECT
            it2.id,
            MIN(h.hotel_id) AS hotel_id
        FROM incrementos_tarifas it2
        JOIN JSON_TABLE(
            CASE
                WHEN JSON_VALID(it2.habitaciones) THEN it2.habitaciones
                ELSE JSON_ARRAY()
            END,
            '$[*]' COLUMNS (habitacion_id INT PATH '$')
        ) AS jt
        JOIN habitaciones h
            ON h.id = jt.habitacion_id
        WHERE it2.hotel_id IS NULL
          AND it2.alcance = 'habitacion'
        GROUP BY it2.id
        HAVING COUNT(DISTINCT h.hotel_id) = 1
    ) AS derivado
        ON derivado.id = it.id
    SET it.hotel_id = derivado.hotel_id
    WHERE it.hotel_id IS NULL;

    UPDATE incrementos_tarifas
    SET hotel_id = v_los_cedros_id
    WHERE hotel_id IS NULL;

    SELECT COUNT(*)
    INTO v_nulls
    FROM incrementos_tarifas
    WHERE hotel_id IS NULL;

    IF v_nulls > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'incrementos_tarifas tiene hotel_id NULL despues del backfill';
    END IF;

    SELECT COUNT(*)
    INTO v_invalid_hotel_ids
    FROM incrementos_tarifas it
    LEFT JOIN hoteles h
        ON h.id = it.hotel_id
    WHERE it.hotel_id IS NOT NULL
      AND h.id IS NULL;

    IF v_invalid_hotel_ids > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'incrementos_tarifas tiene hotel_id invalido';
    END IF;

    SELECT COUNT(DISTINCT INDEX_NAME)
    INTO v_has_index
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'incrementos_tarifas'
      AND INDEX_NAME = 'idx_incrementos_tarifas_hotel_fecha';

    IF v_has_index = 0 THEN
        ALTER TABLE incrementos_tarifas
            ADD INDEX idx_incrementos_tarifas_hotel_fecha (hotel_id, activo, fecha_inicio, fecha_fin);
    END IF;

    SELECT COUNT(*)
    INTO v_has_fk
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'incrementos_tarifas'
      AND CONSTRAINT_NAME = 'fk_incrementos_tarifas_hotel'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY';

    IF v_has_fk = 0 THEN
        ALTER TABLE incrementos_tarifas
            ADD CONSTRAINT fk_incrementos_tarifas_hotel
                FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT;
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

CALL migrar_incrementos_tarifas_hotel_id()$$

DROP PROCEDURE IF EXISTS migrar_incrementos_tarifas_hotel_id$$

DELIMITER ;

-- Rollback manual:
-- ALTER TABLE incrementos_tarifas DROP FOREIGN KEY fk_incrementos_tarifas_hotel;
-- ALTER TABLE incrementos_tarifas DROP INDEX idx_incrementos_tarifas_hotel_fecha;
-- ALTER TABLE incrementos_tarifas DROP COLUMN hotel_id;
-- DELETE FROM migrations WHERE nombre = '20260624_005_incrementos_tarifas_hotel_id.sql';
