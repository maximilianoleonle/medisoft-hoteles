-- Bloque lealtad - V1: programa de huesped frecuente con cupon personal.
-- Alcance:
-- - Registra el bloque lealtad ($179, opt-in: sin activacion retroactiva).
-- - Crea lealtad_cupones: liga huesped -> cupon generado (el cupon vive en
--   motor_cupones y viaja por el flujo de dinero ya blindado del bloque
--   promociones: validacion y aplicacion 100% en servidor).
-- - La config del programa (estancias minimas, % de descuento, vigencia)
--   vive en hotel_configuracion via ConfiguracionHotelRegistry.
-- - No toca Caja, reservaciones ni el flujo de pago.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('lealtad', 'Huesped frecuente', 'Detecta a tus huespedes que regresan y les genera un cupon personal de agradecimiento para su siguiente reserva en linea.', 'canales', 0, 179.00, 1, 121, 'heart', '/lealtad')
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
INNER JOIN modulos m ON m.clave = 'lealtad'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS lealtad_cupones (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    huesped_id INT NOT NULL,
    cupon_id INT NOT NULL,
    estancias_al_generar INT NOT NULL DEFAULT 0,
    correo_enviado_at DATETIME DEFAULT NULL,
    creado_por INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lealtad_cupon (cupon_id),
    KEY idx_lealtad_huesped (hotel_id, huesped_id, created_at),
    CONSTRAINT fk_lealtad_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_lealtad_huesped
        FOREIGN KEY (huesped_id) REFERENCES huespedes(id) ON DELETE CASCADE,
    CONSTRAINT fk_lealtad_cupon
        FOREIGN KEY (cupon_id) REFERENCES motor_cupones(id) ON DELETE CASCADE,
    CONSTRAINT fk_lealtad_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_014_lealtad.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
