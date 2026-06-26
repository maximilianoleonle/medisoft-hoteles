-- Archivar vehiculos huerfanos de huesped_vehiculos.
-- Alcance:
-- - Copia registros sin huesped existente a una tabla de archivo.
-- - Retira esos registros de la tabla activa para dejar hotel_id sin NULL por huerfanos.
-- - No toca huespedes, reservaciones, caja, PWA/offline ni /api/sync.

SET @migration_name := '20260624_007_archivar_huesped_vehiculos_huerfanos.sql';

DROP PROCEDURE IF EXISTS migrar_huesped_vehiculos_huerfanos_archivo;

DELIMITER $$

CREATE PROCEDURE migrar_huesped_vehiculos_huerfanos_archivo()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_orphans_before INT DEFAULT 0;
    DECLARE v_archived_current INT DEFAULT 0;
    DECLARE v_orphans_after INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huesped_vehiculos';

    IF v_has_table = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'huesped_vehiculos no existe; no se puede archivar huerfanos';
    END IF;

    CREATE TABLE IF NOT EXISTS huesped_vehiculos_orfanos_archivo (
        id INT NOT NULL AUTO_INCREMENT,
        vehiculo_id INT NOT NULL,
        hotel_id INT NULL,
        huesped_id INT NOT NULL,
        marca VARCHAR(50) NOT NULL,
        modelo VARCHAR(50) NULL,
        placas VARCHAR(20) NOT NULL,
        color VARCHAR(30) NULL,
        estacionamiento VARCHAR(50) NULL,
        datos_extra_json TEXT NULL,
        activo TINYINT(1) NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        migration_name VARCHAR(191) NOT NULL,
        motivo VARCHAR(255) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_hvoa_vehiculo_migration (vehiculo_id, migration_name),
        KEY idx_hvoa_huesped_id (huesped_id),
        KEY idx_hvoa_placas (placas)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    SELECT COUNT(*)
    INTO v_orphans_before
    FROM huesped_vehiculos hv
    LEFT JOIN huespedes h
        ON h.id = hv.huesped_id
    WHERE h.id IS NULL;

    INSERT IGNORE INTO huesped_vehiculos_orfanos_archivo (
        vehiculo_id,
        hotel_id,
        huesped_id,
        marca,
        modelo,
        placas,
        color,
        estacionamiento,
        datos_extra_json,
        activo,
        created_at,
        updated_at,
        migration_name,
        motivo
    )
    SELECT
        hv.id,
        hv.hotel_id,
        hv.huesped_id,
        hv.marca,
        hv.modelo,
        hv.placas,
        hv.color,
        hv.estacionamiento,
        hv.datos_extra_json,
        hv.activo,
        hv.created_at,
        hv.updated_at,
        @migration_name,
        'Vehiculo sin huesped asociado en huespedes'
    FROM huesped_vehiculos hv
    LEFT JOIN huespedes h
        ON h.id = hv.huesped_id
    WHERE h.id IS NULL;

    SELECT COUNT(*)
    INTO v_archived_current
    FROM huesped_vehiculos_orfanos_archivo
    WHERE migration_name = @migration_name;

    IF v_archived_current < v_orphans_before THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se archivaron todos los vehiculos huerfanos; abortar saneamiento';
    END IF;

    DELETE hv
    FROM huesped_vehiculos hv
    LEFT JOIN huespedes h
        ON h.id = hv.huesped_id
    INNER JOIN huesped_vehiculos_orfanos_archivo a
        ON a.vehiculo_id = hv.id
       AND a.migration_name = @migration_name
    WHERE h.id IS NULL;

    SELECT COUNT(*)
    INTO v_orphans_after
    FROM huesped_vehiculos hv
    LEFT JOIN huespedes h
        ON h.id = hv.huesped_id
    WHERE h.id IS NULL;

    IF v_orphans_after > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Aun quedan vehiculos huerfanos despues del saneamiento';
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

CALL migrar_huesped_vehiculos_huerfanos_archivo()$$

DROP PROCEDURE IF EXISTS migrar_huesped_vehiculos_huerfanos_archivo$$

DELIMITER ;

-- Rollback manual:
-- 1) Restaurar registros archivados por esta migracion:
--    INSERT IGNORE INTO huesped_vehiculos
--        (id, hotel_id, huesped_id, marca, modelo, placas, color, estacionamiento,
--         datos_extra_json, activo, created_at, updated_at)
--    SELECT vehiculo_id, hotel_id, huesped_id, marca, modelo, placas, color, estacionamiento,
--           datos_extra_json, activo, created_at, updated_at
--    FROM huesped_vehiculos_orfanos_archivo
--    WHERE migration_name = '20260624_007_archivar_huesped_vehiculos_huerfanos.sql';
-- 2) Quitar registro de migracion:
--    DELETE FROM migrations WHERE nombre = '20260624_007_archivar_huesped_vehiculos_huerfanos.sql';
