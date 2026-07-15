-- Mantenimiento Plus F1: evidencia fotografica de incidencias
-- Migracion aditiva, idempotente y multi-hotel.
--
-- Alcance:
--   - Crea mantenimiento_fotos: evidencia del problema (momento 'reporte')
--     y del arreglo (momento 'resuelto') por mantenimiento.
--   - No toca mantenimientos_habitaciones ni Caja.
--
-- Rollback manual seguro (solo con autorizacion explicita y tabla vacia):
--   SELECT COUNT(*) FROM mantenimiento_fotos;
--   DROP TABLE mantenimiento_fotos;
--   DELETE FROM migrations WHERE nombre = '20260715_004_mantenimiento_fotos.sql';

SET @migration_name := '20260715_004_mantenimiento_fotos.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS mantenimiento_fotos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    mantenimiento_id INT NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    momento ENUM('reporte', 'resuelto') NOT NULL DEFAULT 'reporte',
    subido_por INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mant_fotos_hotel_mant (hotel_id, mantenimiento_id, momento),
    CONSTRAINT fk_mantenimiento_fotos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mantenimiento_fotos_mantenimiento
        FOREIGN KEY (mantenimiento_id) REFERENCES mantenimientos_habitaciones (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Evidencia fotografica antes/despues por mantenimiento';

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
