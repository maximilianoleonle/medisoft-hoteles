-- Permitir placas repetidas entre hoteles.
-- Alcance:
-- - huesped_vehiculos
-- - Agrega hotel_id directo para poder aplicar unicidad por hotel.
-- - Convierte el indice unico global de placas a indice unico por hotel.
-- - No toca reservaciones, caja, PWA/offline, /api/sync ni datos de huespedes.

SET @migration_name := '20260624_004_huesped_vehiculos_placas_unicas_por_hotel.sql';

DROP PROCEDURE IF EXISTS migrar_huesped_vehiculos_placas_por_hotel;

DELIMITER $$

CREATE PROCEDURE migrar_huesped_vehiculos_placas_por_hotel()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_has_hotel_id INT DEFAULT 0;
    DECLARE v_has_huesped_id INT DEFAULT 0;
    DECLARE v_has_placas INT DEFAULT 0;
    DECLARE v_invalid_hotel_ids INT DEFAULT 0;
    DECLARE v_duplicados_por_scope INT DEFAULT 0;
    DECLARE v_has_hotel_index INT DEFAULT 0;
    DECLARE v_has_hotel_fk INT DEFAULT 0;
    DECLARE v_has_scoped_unique INT DEFAULT 0;
    DECLARE v_global_unique_placas_index VARCHAR(128) DEFAULT NULL;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huesped_vehiculos';

    IF v_has_table = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'huesped_vehiculos no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_has_huesped_id
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huesped_vehiculos'
      AND COLUMN_NAME = 'huesped_id';

    IF v_has_huesped_id = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'huesped_vehiculos.huesped_id no existe; no se puede derivar hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_has_placas
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huesped_vehiculos'
      AND COLUMN_NAME = 'placas';

    IF v_has_placas = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'huesped_vehiculos.placas no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_has_hotel_id
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huesped_vehiculos'
      AND COLUMN_NAME = 'hotel_id';

    IF v_has_hotel_id = 0 THEN
        ALTER TABLE huesped_vehiculos
            ADD COLUMN hotel_id INT NULL AFTER id;
    END IF;

    UPDATE huesped_vehiculos hv
    INNER JOIN huespedes h
        ON h.id = hv.huesped_id
    SET hv.hotel_id = h.hotel_id
    WHERE hv.hotel_id IS NULL;

    SELECT COUNT(*)
    INTO v_invalid_hotel_ids
    FROM huesped_vehiculos hv
    LEFT JOIN hoteles ho
        ON ho.id = hv.hotel_id
    WHERE hv.hotel_id IS NOT NULL
      AND ho.id IS NULL;

    IF v_invalid_hotel_ids > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Existen vehiculos con hotel_id invalido; corregir antes de cambiar el indice unico';
    END IF;

    SELECT COUNT(*)
    INTO v_duplicados_por_scope
    FROM (
        SELECT COALESCE(hotel_id, 0) AS hotel_scope, placas, COUNT(*) AS total
        FROM huesped_vehiculos
        GROUP BY COALESCE(hotel_id, 0), placas
        HAVING COUNT(*) > 1
    ) AS d;

    IF v_duplicados_por_scope > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Existen placas duplicadas dentro del mismo hotel o en vehiculos sin hotel';
    END IF;

    SELECT COUNT(DISTINCT INDEX_NAME)
    INTO v_has_hotel_index
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huesped_vehiculos'
      AND INDEX_NAME = 'idx_huesped_vehiculos_hotel_id';

    IF v_has_hotel_index = 0 THEN
        ALTER TABLE huesped_vehiculos
            ADD INDEX idx_huesped_vehiculos_hotel_id (hotel_id);
    END IF;

    SELECT COUNT(*)
    INTO v_has_hotel_fk
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huesped_vehiculos'
      AND CONSTRAINT_NAME = 'fk_huesped_vehiculos_hotel'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY';

    IF v_has_hotel_fk = 0 THEN
        ALTER TABLE huesped_vehiculos
            ADD CONSTRAINT fk_huesped_vehiculos_hotel
                FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT;
    END IF;

    SELECT COUNT(DISTINCT INDEX_NAME)
    INTO v_has_scoped_unique
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huesped_vehiculos'
      AND INDEX_NAME = 'uk_huesped_vehiculos_hotel_placas';

    IF v_has_scoped_unique = 0 THEN
        ALTER TABLE huesped_vehiculos
            ADD UNIQUE KEY uk_huesped_vehiculos_hotel_placas ((COALESCE(hotel_id, 0)), placas);
    END IF;

    SELECT INDEX_NAME
    INTO v_global_unique_placas_index
    FROM (
        SELECT INDEX_NAME,
               COUNT(*) AS total_columnas,
               SUM(CASE WHEN COLUMN_NAME = 'placas' THEN 1 ELSE 0 END) AS columnas_placas
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'huesped_vehiculos'
          AND NON_UNIQUE = 0
          AND INDEX_NAME <> 'PRIMARY'
          AND INDEX_NAME <> 'uk_huesped_vehiculos_hotel_placas'
        GROUP BY INDEX_NAME
        HAVING total_columnas = 1
           AND columnas_placas = 1
        LIMIT 1
    ) AS idx;

    IF v_global_unique_placas_index IS NOT NULL THEN
        SET @drop_global_placas_sql := CONCAT(
            'ALTER TABLE huesped_vehiculos DROP INDEX `',
            REPLACE(v_global_unique_placas_index, '`', '``'),
            '`'
        );
        PREPARE stmt FROM @drop_global_placas_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
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

CALL migrar_huesped_vehiculos_placas_por_hotel()$$

DROP PROCEDURE IF EXISTS migrar_huesped_vehiculos_placas_por_hotel$$

DELIMITER ;

-- Nota:
-- - Los vehiculos cuyo huesped ya no existe conservan hotel_id NULL.
-- - El indice funcional trata NULL como scope 0 para evitar placas duplicadas entre huerfanos.
--
-- Rollback manual:
-- 1) Confirmar que no existan placas repetidas globalmente:
--    SELECT placas, COUNT(*) FROM huesped_vehiculos GROUP BY placas HAVING COUNT(*) > 1;
-- 2) Si el resultado es vacio:
--    ALTER TABLE huesped_vehiculos ADD UNIQUE KEY placas_unique (placas);
--    ALTER TABLE huesped_vehiculos DROP INDEX uk_huesped_vehiculos_hotel_placas;
--    ALTER TABLE huesped_vehiculos DROP FOREIGN KEY fk_huesped_vehiculos_hotel;
--    ALTER TABLE huesped_vehiculos DROP INDEX idx_huesped_vehiculos_hotel_id;
--    ALTER TABLE huesped_vehiculos DROP COLUMN hotel_id;
-- 3) Quitar registro de migracion:
--    DELETE FROM migrations WHERE nombre = '20260624_004_huesped_vehiculos_placas_unicas_por_hotel.sql';
