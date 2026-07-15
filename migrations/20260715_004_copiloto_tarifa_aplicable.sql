-- Copiloto IA - Fase 2: el consejo de tarifa pasa de texto a accion aplicable.
-- Alcance:
-- - incrementos_tarifas gana trazabilidad del origen: NULL = manual (todo lo
--   existente), 'copiloto' = creado desde una sugerencia del consejo IA que un
--   humano confirmo. consejo_ref apunta al cache copiloto_ia_generaciones y
--   aprobado_por al usuario que confirmo.
-- - NO cambia el motor de precios: los registros del copiloto son incrementos
--   estandar que se editan/borran como cualquier otro (esa es la reversibilidad).
-- - Idempotente: cada columna se agrega solo si no existe.

SET @migration_name := '20260715_004_copiloto_tarifa_aplicable.sql';

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incrementos_tarifas' AND COLUMN_NAME = 'origen') = 0,
    'ALTER TABLE incrementos_tarifas ADD COLUMN origen VARCHAR(20) NULL DEFAULT NULL COMMENT ''NULL=manual; copiloto=sugerencia IA confirmada por un humano'' AFTER usuario_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incrementos_tarifas' AND COLUMN_NAME = 'consejo_ref') = 0,
    'ALTER TABLE incrementos_tarifas ADD COLUMN consejo_ref BIGINT NULL DEFAULT NULL COMMENT ''id de copiloto_ia_generaciones del consejo que origino el ajuste'' AFTER origen',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incrementos_tarifas' AND COLUMN_NAME = 'aprobado_por') = 0,
    'ALTER TABLE incrementos_tarifas ADD COLUMN aprobado_por INT NULL DEFAULT NULL COMMENT ''usuario que confirmo la sugerencia del copiloto'' AFTER consejo_ref',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
