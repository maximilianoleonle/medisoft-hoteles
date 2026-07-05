-- Bloque forecast - V1: proyeccion de ocupacion y pickup report.
-- Alcance:
-- - Registra el bloque forecast ($199, opt-in: sin activacion retroactiva).
-- - NO crea tablas: es 100% lectura sobre reservaciones/habitaciones
--   existentes (proyeccion 30/60/90 dias, ritmo de reservas y comparativa
--   contra el mismo periodo del anio anterior).
-- - No toca Caja, reservaciones ni datos operativos.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('forecast', 'Forecast de ocupacion', 'Proyeccion de ocupacion a 30/60/90 dias, ritmo de reservas semana contra semana y comparativa con el anio pasado.', 'analitica', 0, 199.00, 1, 82, 'chart-line', '/forecast')
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
INNER JOIN modulos m ON m.clave = 'forecast'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_011_forecast.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
