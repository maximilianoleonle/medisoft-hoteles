-- Bloque reputacion - V1: encuestas post-estancia con link publico por token.
-- Alcance:
-- - Registra el bloque reputacion ($149, opt-in: sin activacion retroactiva).
-- - Crea reputacion_encuestas: un link con token por reservacion con checkout;
--   el huesped califica su estancia (1-5), NPS opcional y comentario.
-- - Calificacion alta -> se le invita a dejar resena en Google;
--   calificacion baja -> alerta interna (bloque notificaciones, best-effort).
-- - No toca el flujo de check-out ni datos operativos.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('reputacion', 'Reputacion y encuestas', 'El huesped califica su estancia al salir; las buenas experiencias van a Google y las malas te llegan a ti antes de hacerse publicas.', 'operacion', 0, 149.00, 1, 35, 'star', '/reputacion')
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
INNER JOIN modulos m ON m.clave = 'reputacion'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS reputacion_encuestas (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    reservacion_id INT NOT NULL,
    token CHAR(32) NOT NULL,
    estado ENUM('pendiente','enviada','respondida','expirada') NOT NULL DEFAULT 'pendiente',
    calificacion TINYINT DEFAULT NULL,
    nps TINYINT DEFAULT NULL,
    comentario TEXT DEFAULT NULL,
    canal_envio VARCHAR(20) DEFAULT NULL,
    enviada_at TIMESTAMP NULL DEFAULT NULL,
    respondida_at TIMESTAMP NULL DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    creado_por INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_reputacion_token (token),
    UNIQUE KEY uk_reputacion_reservacion (hotel_id, reservacion_id),
    KEY idx_reputacion_hotel_estado (hotel_id, estado),
    KEY idx_reputacion_hotel_respondida (hotel_id, respondida_at),
    CONSTRAINT fk_reputacion_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_reputacion_reservacion
        FOREIGN KEY (reservacion_id) REFERENCES reservaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_reputacion_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_006_reputacion.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
