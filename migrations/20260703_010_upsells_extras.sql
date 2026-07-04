-- Bloque upsells - V1: extras vendibles en el motor de reservas online.
-- Alcance:
-- - Registra el bloque upsells ($149, opt-in: sin activacion retroactiva).
--   Requiere motor_reservas activo para tener efecto (los extras viven ahi).
-- - Crea motor_extras: catalogo por hotel (desayuno, late checkout, decoracion)
--   con tipo de cobro por reserva / noche / persona / persona-noche.
-- - Los importes se calculan SIEMPRE en servidor dentro de iniciarPago y se
--   congelan en motor_pagos_online.payload_json (mismo patron que cupones).
-- - No toca Caja ni el flujo de conciliacion.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('upsells', 'Extras y upselling', 'Vende desayuno, late checkout o decoracion directo en la reserva online; el extra se suma al total de la estancia.', 'canales', 0, 149.00, 1, 120, 'gift', '/motor-reservas/extras')
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
INNER JOIN modulos m ON m.clave = 'upsells'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS motor_extras (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    precio DECIMAL(10,2) NOT NULL,
    tipo_cobro ENUM('por_reserva','por_noche','por_persona','por_persona_noche') NOT NULL DEFAULT 'por_reserva',
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_por INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_motor_extras_hotel_activo (hotel_id, activo, orden),
    CONSTRAINT fk_motor_extras_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_motor_extras_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_010_upsells_extras.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
