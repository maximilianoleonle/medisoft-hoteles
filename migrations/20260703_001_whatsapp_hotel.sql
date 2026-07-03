-- Bloque whatsapp (ya en catalogo a $299) - V1: credenciales por hotel.
-- Alcance:
-- - Crea hotel_whatsapp_credenciales (Green API por hotel; token cifrado
--   AES-256-GCM con MOTOR_PASARELA_KEY, mismo esquema que pasarela).
-- - No activa el bloque en ningun hotel (opt-in, funcionalidad nueva).
-- - No toca datos operativos.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS hotel_whatsapp_credenciales (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    id_instance VARCHAR(40) DEFAULT NULL,
    api_token_encrypted TEXT DEFAULT NULL,
    api_host VARCHAR(120) NOT NULL DEFAULT 'https://api.green-api.com',
    numero_avisos VARCHAR(20) DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_whatsapp_hotel (hotel_id),
    CONSTRAINT fk_whatsapp_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_001_whatsapp_hotel.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
