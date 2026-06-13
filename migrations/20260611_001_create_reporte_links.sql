-- Reportes fase 2 - Links seguros para PDFs guardados
--
-- Alcance:
-- - Crea una tabla nueva para historial interno y links seguros de reportes.
-- - No modifica reportes existentes, caja, cortes, movimientos, PWA, offline ni sync.
-- - No crea foreign keys estrictas para evitar bloquear datos historicos.

CREATE TABLE IF NOT EXISTS reporte_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    tipo_reporte VARCHAR(80) NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    archivo_path VARCHAR(500) NOT NULL,
    archivo_nombre VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL DEFAULT 'application/pdf',
    tamano_bytes BIGINT UNSIGNED DEFAULT NULL,
    parametros_json TEXT DEFAULT NULL,
    token_hash CHAR(64) NOT NULL,
    token_hint CHAR(8) NOT NULL,
    estado ENUM('activo', 'expirado', 'revocado') NOT NULL DEFAULT 'activo',
    expira_en DATETIME NOT NULL,
    creado_por INT DEFAULT NULL,
    primer_acceso_en DATETIME DEFAULT NULL,
    ultimo_acceso_en DATETIME DEFAULT NULL,
    accesos INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_reporte_links_token_hash (token_hash),
    KEY idx_reporte_links_hotel_created (hotel_id, created_at),
    KEY idx_reporte_links_hotel_estado (hotel_id, estado, expira_en),
    KEY idx_reporte_links_expira_estado (estado, expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
SELECT
    '20260611_001_create_reporte_links.sql',
    COALESCE(MAX(batch), 0) + 1,
    NULL,
    'ejecutada',
    CURRENT_TIMESTAMP
FROM migrations
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
