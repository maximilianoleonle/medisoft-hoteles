-- Bloque comercial: Briefing matutino del Copiloto (copiloto_briefing).
--
-- Push proactivo por la manana con el pulso del dia (llegadas, salidas,
-- habitaciones sucias con llegada hoy, caja y anomalias financieras) por la
-- cadena push PWA existente, con ruteo por rol (gerencia). La hora y el
-- on/off se configuran por hotel (hotel_configuracion: copiloto.briefing_*).
--
-- Mismo patron que el bloque copiloto (20260703_016): fila en modulos +
-- preset premium. Sin tablas nuevas: los eventos viven en notificaciones y
-- el dedupe diario lo da su dedupe_key.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('copiloto_briefing', 'Copiloto proactivo', 'Tu copiloto se adelanta: briefing matutino por notificacion push con llegadas, salidas, limpieza pendiente, caja y avisos con criterio (ocupacion baja) antes de que preguntes.', 'inteligencia', 0, 99.00, 1, 142, 'sun', NULL)
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
INNER JOIN modulos m ON m.clave = 'copiloto_briefing'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE
    incluido = VALUES(incluido),
    orden = VALUES(orden);

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260715_001_copiloto_briefing_bloque.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE estado = VALUES(estado), ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
