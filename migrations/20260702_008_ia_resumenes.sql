-- IA Ejecutiva - Fase 1: cache de resumenes generados por IA.
-- Alcance:
-- - Crea ia_resumenes: un resumen por hotel/fecha/tipo para no re-generar
--   (y no re-pagar tokens) en cada vista. Regenerar lo reemplaza.
-- - El bloque ia_ejecutiva ($499) ya existe en el catalogo desde 20260526_011.
-- - No toca datos operativos.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS ia_resumenes (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    tipo VARCHAR(40) NOT NULL DEFAULT 'gerencial_diario',
    fecha DATE NOT NULL,
    contenido MEDIUMTEXT NOT NULL,
    modelo VARCHAR(60) DEFAULT NULL,
    tokens_entrada INT DEFAULT NULL,
    tokens_salida INT DEFAULT NULL,
    generado_por INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_ia_resumenes_hotel_tipo_fecha (hotel_id, tipo, fecha),
    KEY idx_ia_resumenes_hotel_fecha (hotel_id, fecha),
    CONSTRAINT fk_ia_resumenes_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_ia_resumenes_usuario
        FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260702_008_ia_resumenes.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
