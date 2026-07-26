-- Retira Tareas operativas del catalogo comercial.
--
-- Decision de producto (2026-07-22): Tareas es infraestructura compartida
-- por Habitaciones, Limpieza, Mantenimiento, Areas, Personal y Copiloto.
-- La migracion elimina solamente el bloque vendible y sus asignaciones
-- comerciales por cascada. No toca tareas_operativas, tarea_eventos,
-- tarea_trabajadores ni ningun historial operativo.

SET @migration_name := '20260722_002_retirar_modulo_tareas_comercial.sql';

START TRANSACTION;

DELETE FROM modulos
WHERE clave = 'tareas';

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name,
       COALESCE(MAX(batch), 0) + 1,
       SHA2(CONCAT(@migration_name, '|v1'), 256),
       'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
