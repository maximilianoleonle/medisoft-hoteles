-- Habitaciones y areas F1: entidad "area" del hotel (alberca, lobby, restaurante...)
-- Migracion aditiva, idempotente y multi-hotel.
--
-- Alcance:
--   - Crea areas_hotel: zonas del hotel que no son habitacion pero reciben
--     limpieza, mantenimiento y bloqueos (estado espejo del de habitaciones,
--     sin 'ocupada' porque las areas no se reservan).
--   - Liga area_id (NULLABLE) en tareas_operativas, mantenimientos_habitaciones
--     y activos_hotel: una tarea/mantenimiento/activo vive en una habitacion O
--     en un area O en ninguna (general).
--   - tipo es VARCHAR validado por catalogo en PHP (gotcha: enum MySQL no
--     truena con valores invalidos).
--   - No toca Caja ni reservaciones.
--
-- Rollback manual seguro (solo con autorizacion explicita y sin areas creadas):
--   ALTER TABLE tareas_operativas DROP FOREIGN KEY fk_tareas_area, DROP COLUMN area_id;
--   ALTER TABLE mantenimientos_habitaciones DROP FOREIGN KEY fk_mantenimientos_area, DROP COLUMN area_id;
--   ALTER TABLE activos_hotel DROP FOREIGN KEY fk_activos_area, DROP COLUMN area_id;
--   DROP TABLE areas_hotel;
--   DELETE FROM migrations WHERE nombre = '20260716_001_areas_hotel.sql';

SET @migration_name := '20260716_001_areas_hotel.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS areas_hotel (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    tipo VARCHAR(40) NOT NULL DEFAULT 'otra',
    piso INT NULL,
    descripcion TEXT NULL,
    estado ENUM('disponible','limpieza','mantenimiento','cerrada') NOT NULL DEFAULT 'disponible',
    foto_url VARCHAR(255) NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_areas_hotel_nombre (hotel_id, nombre),
    KEY idx_areas_hotel_estado (hotel_id, estado),
    KEY idx_areas_hotel_activa (hotel_id, activa),
    CONSTRAINT fk_areas_hotel_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Areas del hotel (alberca, lobby...) con limpieza y mantenimiento';

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tareas_operativas' AND COLUMN_NAME = 'area_id');
SET @ddl := IF(@col = 0,
    'ALTER TABLE tareas_operativas ADD COLUMN area_id INT NULL DEFAULT NULL AFTER habitacion_id, ADD KEY idx_tareas_area (area_id), ADD CONSTRAINT fk_tareas_area FOREIGN KEY (area_id) REFERENCES areas_hotel (id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT ''OK: tareas_operativas.area_id ya existe'' AS resultado');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mantenimientos_habitaciones' AND COLUMN_NAME = 'area_id');
SET @ddl := IF(@col = 0,
    'ALTER TABLE mantenimientos_habitaciones ADD COLUMN area_id INT NULL DEFAULT NULL AFTER activo_id, ADD KEY idx_mant_area (hotel_id, area_id), ADD CONSTRAINT fk_mantenimientos_area FOREIGN KEY (area_id) REFERENCES areas_hotel (id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT ''OK: mantenimientos_habitaciones.area_id ya existe'' AS resultado');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activos_hotel' AND COLUMN_NAME = 'area_id');
SET @ddl := IF(@col = 0,
    'ALTER TABLE activos_hotel ADD COLUMN area_id INT NULL DEFAULT NULL AFTER habitacion_id, ADD KEY idx_activos_area (area_id), ADD CONSTRAINT fk_activos_area FOREIGN KEY (area_id) REFERENCES areas_hotel (id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT ''OK: activos_hotel.area_id ya existe'' AS resultado');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
