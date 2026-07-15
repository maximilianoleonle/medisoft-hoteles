-- Mantenimiento Plus F3: activos del hotel y mantenimiento preventivo
-- Migracion aditiva, idempotente y multi-hotel.
--
-- Alcance:
--   - Crea activos_hotel (boiler, bomba, aire...) con periodicidad de servicio
--     y proximo_servicio calculado.
--   - Liga mantenimientos_habitaciones.activo_id al activo que origino el
--     preventivo.
--   - Hace NULLABLE mantenimientos_habitaciones.habitacion_id: un activo de
--     instalaciones generales (boiler) no vive en una habitacion. Las queries
--     que muestran mantenimientos ya usan LEFT JOIN a habitaciones.
--   - No toca Caja ni tareas.
--
-- Rollback manual seguro (solo con autorizacion explicita y sin preventivos):
--   ALTER TABLE mantenimientos_habitaciones DROP FOREIGN KEY fk_mantenimientos_activo;
--   ALTER TABLE mantenimientos_habitaciones DROP COLUMN activo_id;
--   DROP TABLE activos_hotel;
--   DELETE FROM migrations WHERE nombre = '20260715_006_activos_preventivo.sql';

SET @migration_name := '20260715_006_activos_preventivo.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS activos_hotel (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(160) NOT NULL,
    ubicacion VARCHAR(160) NULL,
    habitacion_id INT NULL,
    periodicidad_dias INT NOT NULL DEFAULT 180,
    ultimo_servicio DATE NULL,
    proximo_servicio DATE NULL,
    notas TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activos_hotel_proximo (hotel_id, activo, proximo_servicio),
    KEY idx_activos_habitacion (habitacion_id),
    CONSTRAINT fk_activos_hotel_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_activos_hotel_habitacion
        FOREIGN KEY (habitacion_id) REFERENCES habitaciones (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Activos del hotel con mantenimiento preventivo programable';

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mantenimientos_habitaciones' AND COLUMN_NAME = 'activo_id');
SET @ddl := IF(@col = 0,
    'ALTER TABLE mantenimientos_habitaciones ADD COLUMN activo_id INT NULL DEFAULT NULL AFTER habitacion_id, ADD KEY idx_mant_activo (hotel_id, activo_id), ADD CONSTRAINT fk_mantenimientos_activo FOREIGN KEY (activo_id) REFERENCES activos_hotel (id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT ''OK: activo_id ya existe'' AS resultado');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @nullable := (SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mantenimientos_habitaciones' AND COLUMN_NAME = 'habitacion_id');
SET @ddl := IF(@nullable = 'NO',
    'ALTER TABLE mantenimientos_habitaciones MODIFY COLUMN habitacion_id INT NULL',
    'SELECT ''OK: habitacion_id ya es nullable'' AS resultado');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
