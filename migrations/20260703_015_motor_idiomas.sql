-- Bloque motor_idiomas - V1: motor de reservas publico en ingles.
-- Alcance:
-- - Registra el bloque motor_idiomas ($99, opt-in: sin activacion retroactiva).
-- - NO crea tablas: con el bloque activo, la pagina publica del motor muestra
--   el selector ES/EN y toda la interfaz (incluidos mensajes del navegador)
--   se sirve en ingles con ?lang=en. Sin el bloque, todo sigue solo en espanol.
-- - No toca el flujo de pago ni datos.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('motor_idiomas', 'Motor en ingles', 'Tu pagina publica de reservas en espanol e ingles, con selector de idioma para huespedes extranjeros.', 'canales', 0, 99.00, 1, 122, 'language', NULL)
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
INNER JOIN modulos m ON m.clave = 'motor_idiomas'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_015_motor_idiomas.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
