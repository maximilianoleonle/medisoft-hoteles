-- Bloque canal_whatsapp - Nivel 1: mensajes asistidos por links wa.me.
-- Alcance:
-- - Registra el bloque canal_whatsapp ($199, opt-in: sin activacion retroactiva).
--   Es INDEPENDIENTE del bloque 'whatsapp' (Green API / avisos automaticos del
--   motor): este bloque es la cola manual de recepcion, sin API de terceros.
-- - Crea mensajes_whatsapp: timeline por reservacion de lo que recepcion envio
--   (o descarto) por WhatsApp: confirmacion, recordatorio, anticipo y encuesta.
--   El envio es humano (link wa.me); la tabla registra el hecho, no automatiza.
-- - canal es VARCHAR pensado para que luego exista 'cloud_api' sin migrar.
-- - El mensaje de anticipo es INFORMATIVO: esta tabla jamas toca caja/abonos.
-- - No toca reservaciones, pagos ni flujo de check-in/check-out.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('canal_whatsapp', 'Mensajes WhatsApp', 'Confirmaciones, recordatorios, avisos de anticipo y encuestas listos para mandarse por WhatsApp: el sistema arma cada mensaje con los datos de la reserva y recepcion solo toca Enviar. Sin instalar nada.', 'canales', 0, 199.00, 1, 116, 'send', '/mensajes')
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
INNER JOIN modulos m ON m.clave = 'canal_whatsapp'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS mensajes_whatsapp (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    reservacion_id INT NOT NULL,
    tipo ENUM('confirmacion','recordatorio','anticipo','encuesta') NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    contenido TEXT DEFAULT NULL,
    estado ENUM('pendiente','enviado','descartado') NOT NULL DEFAULT 'pendiente',
    canal VARCHAR(20) NOT NULL DEFAULT 'manual',
    motivo VARCHAR(200) DEFAULT NULL,
    enviado_por INT DEFAULT NULL,
    enviado_en DATETIME DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_mensaje_reservacion_tipo (hotel_id, reservacion_id, tipo),
    KEY idx_mensajes_wa_hotel_estado (hotel_id, estado),
    KEY idx_mensajes_wa_hotel_enviado (hotel_id, enviado_en),
    CONSTRAINT fk_mensajes_wa_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_mensajes_wa_reservacion
        FOREIGN KEY (reservacion_id) REFERENCES reservaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_mensajes_wa_usuario
        FOREIGN KEY (enviado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260715_002_canal_whatsapp.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
