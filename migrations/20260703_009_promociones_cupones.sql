-- Bloque promociones - V1: cupones de descuento en el motor de reservas.
-- Alcance:
-- - Registra el bloque promociones ($129, opt-in: sin activacion retroactiva).
--   Requiere motor_reservas activo para tener efecto (los cupones viven ahi).
-- - Crea motor_cupones: codigos por hotel (porcentaje o monto fijo) con
--   vigencia y limite de usos. El descuento se aplica SIEMPRE en servidor
--   dentro de iniciarPago y se congela en motor_pagos_online.payload_json.
-- - No toca Caja ni el flujo de conciliacion.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('promociones', 'Cupones y promociones', 'Codigos de descuento para el motor de reservas online: por porcentaje o monto, con vigencia y limite de usos.', 'canales', 0, 129.00, 1, 119, 'tags', '/motor-reservas/cupones')
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
INNER JOIN modulos m ON m.clave = 'promociones'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS motor_cupones (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    codigo VARCHAR(30) NOT NULL,
    tipo ENUM('porcentaje','monto') NOT NULL DEFAULT 'porcentaje',
    valor DECIMAL(10,2) NOT NULL,
    vigente_desde DATE DEFAULT NULL,
    vigente_hasta DATE DEFAULT NULL,
    limite_usos INT DEFAULT NULL,
    usos INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_por INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_motor_cupones_codigo (hotel_id, codigo),
    KEY idx_motor_cupones_hotel_activo (hotel_id, activo),
    CONSTRAINT fk_motor_cupones_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_motor_cupones_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_009_promociones_cupones.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
