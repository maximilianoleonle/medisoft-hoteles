-- Centro de notificaciones fase 1
--
-- Alcance:
-- - Crea una bandeja operativa por hotel.
-- - No toca PWA, service worker, sync, reportes seguros, correo ni calculos de caja.
-- - No crea foreign keys estrictas para no bloquear datos historicos.

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    rol_destino VARCHAR(40) DEFAULT NULL,
    modulo VARCHAR(40) NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    severidad ENUM('info', 'media', 'alta', 'critica') NOT NULL DEFAULT 'info',
    titulo VARCHAR(180) NOT NULL,
    mensaje VARCHAR(500) NOT NULL,
    entidad_tipo VARCHAR(80) DEFAULT NULL,
    entidad_id INT DEFAULT NULL,
    url VARCHAR(255) DEFAULT NULL,
    estado ENUM('nueva', 'leida', 'resuelta', 'descartada') NOT NULL DEFAULT 'nueva',
    dedupe_key VARCHAR(160) DEFAULT NULL,
    leida_en DATETIME DEFAULT NULL,
    resuelta_en DATETIME DEFAULT NULL,
    descartada_en DATETIME DEFAULT NULL,
    creada_por INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notificaciones_hotel_dedupe (hotel_id, dedupe_key),
    KEY idx_notificaciones_hotel_estado_created (hotel_id, estado, created_at),
    KEY idx_notificaciones_hotel_modulo_created (hotel_id, modulo, created_at),
    KEY idx_notificaciones_hotel_severidad_created (hotel_id, severidad, created_at),
    KEY idx_notificaciones_usuario_estado (usuario_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE,
    batch INT NOT NULL DEFAULT 1,
    checksum VARCHAR(64) DEFAULT NULL,
    estado VARCHAR(40) NOT NULL DEFAULT 'pendiente',
    ejecutada_en DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
SELECT
    '20260611_003_create_notificaciones.sql',
    COALESCE(MAX(batch), 0) + 1,
    NULL,
    'ejecutada',
    CURRENT_TIMESTAMP
FROM migrations
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
