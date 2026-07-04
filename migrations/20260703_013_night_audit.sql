-- Bloque night_audit - V1: cierre nocturno automatico por hotel.
-- Alcance:
-- - Registra el bloque night_audit ($199, opt-in: sin activacion retroactiva).
-- - Crea night_audit_cierres: UN cierre por hotel y fecha operativa con el
--   snapshot de hallazgos (no-shows, checkouts vencidos, cortes abiertos,
--   conteos del dia). Idempotente por (hotel_id, fecha).
-- - El cierre SOLO LEE la operacion (reservaciones, cortes, motor) y escribe
--   en su propia tabla; avisa via notificaciones y correo. No modifica
--   reservaciones, checkouts ni Caja: detecta y avisa, la correccion es humana.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('night_audit', 'Night audit automatico', 'Cierre nocturno del dia: detecta no-shows, checkouts vencidos y cortes de caja abiertos, y manda el resumen al gerente cada madrugada.', 'operacion', 0, 199.00, 1, 36, 'moon', '/night-audit')
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
INNER JOIN modulos m ON m.clave = 'night_audit'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS night_audit_cierres (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    fecha DATE NOT NULL,
    hallazgos_json JSON DEFAULT NULL,
    no_shows INT NOT NULL DEFAULT 0,
    checkouts_vencidos INT NOT NULL DEFAULT 0,
    cortes_abiertos INT NOT NULL DEFAULT 0,
    llegadas INT NOT NULL DEFAULT 0,
    salidas INT NOT NULL DEFAULT 0,
    ocupadas_noche INT NOT NULL DEFAULT 0,
    pagos_online INT NOT NULL DEFAULT 0,
    monto_online DECIMAL(10,2) NOT NULL DEFAULT 0,
    correo_enviado_at DATETIME DEFAULT NULL,
    ejecutado_por INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_night_audit_fecha (hotel_id, fecha),
    KEY idx_night_audit_hotel (hotel_id, fecha),
    CONSTRAINT fk_night_audit_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_night_audit_ejecutor
        FOREIGN KEY (ejecutado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_013_night_audit.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
