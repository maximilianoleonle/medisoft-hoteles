-- Reportes fase 4 - Historial de envios de links seguros por correo
--
-- Alcance:
-- - Crea una tabla nueva para auditar intentos de envio por correo.
-- - No adjunta PDFs ni modifica calculos de reportes, caja, PWA, offline o sync.
-- - No crea foreign keys estrictas para evitar bloquear datos historicos.

CREATE TABLE IF NOT EXISTS reporte_link_envios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporte_link_id INT NOT NULL,
    hotel_id INT NOT NULL,
    canal ENUM('email') NOT NULL DEFAULT 'email',
    destinatarios TEXT NOT NULL,
    asunto VARCHAR(180) NOT NULL,
    estado ENUM('enviado', 'fallido') NOT NULL,
    error_mensaje VARCHAR(500) DEFAULT NULL,
    enviado_por INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reporte_link_envios_link_created (reporte_link_id, created_at),
    KEY idx_reporte_link_envios_hotel_created (hotel_id, created_at),
    KEY idx_reporte_link_envios_estado (estado, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
SELECT
    '20260611_002_create_reporte_link_envios.sql',
    COALESCE(MAX(batch), 0) + 1,
    NULL,
    'ejecutada',
    CURRENT_TIMESTAMP
FROM migrations
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
