-- Bloque auditoria - V1: bitacora de acciones por hotel.
-- Alcance:
-- - Registra el bloque auditoria ($149, opt-in: sin activacion retroactiva).
-- - Crea auditoria_eventos: bitacora APPEND-ONLY de cada accion POST de un
--   usuario autenticado (cancelaciones, precios, configuracion, reversiones).
--   El registro lo hace un hook central en Controller::runAction, best-effort:
--   jamas bloquea ni modifica la accion auditada. Nunca se hace UPDATE/DELETE
--   sobre esta tabla desde la aplicacion.
-- - No toca Caja, reservaciones ni logica de negocio.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('auditoria', 'Bitacora de auditoria', 'Registro de quien hizo que y cuando: cancelaciones, precios, pagos revertidos y cambios de configuracion, con filtros por usuario y fecha.', 'seguridad', 0, 149.00, 1, 89, 'shield-halved', '/auditoria')
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
INNER JOIN modulos m ON m.clave = 'auditoria'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS auditoria_eventos (
    id BIGINT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    usuario_nombre VARCHAR(150) DEFAULT NULL,
    modulo VARCHAR(60) NOT NULL,
    accion VARCHAR(60) NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    entidad_id INT DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_auditoria_hotel_fecha (hotel_id, created_at),
    KEY idx_auditoria_hotel_modulo (hotel_id, modulo, created_at),
    KEY idx_auditoria_hotel_usuario (hotel_id, usuario_id, created_at),
    CONSTRAINT fk_auditoria_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_012_auditoria.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
