-- PWA Push fase 1
--
-- Alcance:
-- - Guarda suscripciones Web Push por hotel y usuario.
-- - No modifica caches, IndexedDB, /api/sync ni logica offline.

CREATE TABLE IF NOT EXISTS pwa_push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    endpoint_hash CHAR(64) NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    navegador VARCHAR(180) DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    last_seen_at DATETIME DEFAULT NULL,
    revoked_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pwa_push_hotel_endpoint (hotel_id, endpoint_hash),
    KEY idx_pwa_push_hotel_activo (hotel_id, activo),
    KEY idx_pwa_push_usuario_activo (usuario_id, activo)
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
    '20260611_004_create_pwa_push_subscriptions.sql',
    COALESCE(MAX(batch), 0) + 1,
    NULL,
    'ejecutada',
    CURRENT_TIMESTAMP
FROM migrations
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
