-- Bloque checkin_digital - V1: pre-registro del huesped antes de llegar.
-- Alcance:
-- - Registra el bloque checkin_digital ($199, opt-in: sin activacion retroactiva).
-- - Crea checkin_digital_links: un link con token por reservacion; el huesped
--   llena datos y sube su ID antes de llegar.
-- - No toca datos operativos ni el flujo de check-in existente.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('checkin_digital', 'Check-in digital', 'El huesped llena sus datos y sube su identificacion antes de llegar; recepcion hace check-in en un minuto.', 'operacion', 0, 199.00, 1, 32, 'id-badge', '/checkin-digital')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;

-- Preset: premium lo incluye.
INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave = 'checkin_digital'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS checkin_digital_links (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    reservacion_id INT NOT NULL,
    token CHAR(32) NOT NULL,
    estado ENUM('pendiente','completado','expirado') NOT NULL DEFAULT 'pendiente',
    datos_json JSON DEFAULT NULL,
    id_documento_path VARCHAR(255) DEFAULT NULL,
    completado_at TIMESTAMP NULL DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    creado_por INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_checkin_token (token),
    UNIQUE KEY uk_checkin_reservacion (hotel_id, reservacion_id),
    KEY idx_checkin_hotel_estado (hotel_id, estado),
    CONSTRAINT fk_checkin_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_checkin_reservacion
        FOREIGN KEY (reservacion_id) REFERENCES reservaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_checkin_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_002_checkin_digital.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
